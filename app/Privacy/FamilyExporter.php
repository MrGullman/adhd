<?php

namespace App\Privacy;

use App\Models\DataExport;
use App\Models\Family;
use App\Models\JournalEntry;
use App\Models\Picture;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * All of a family's data as a ZIP: one JSON file per part and the family's
 * own pictures. Secrets are never included: passwords, children's codes,
 * two-factor keys, link tokens and calendar links.
 */
class FamilyExporter
{
    /** Columns never exported, in any table. */
    private const SECRET = [
        'password', 'pin', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token',
        'token_hash', 'url', 'path',
    ];

    public function export(DataExport $export, Family $family): void
    {
        $disk = Storage::disk(Picture::DISK);
        $relative = "exports/{$family->id}/".bin2hex(random_bytes(16)).'.zip';
        $disk->makeDirectory(dirname($relative));
        $file = $disk->path($relative);

        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The export could not be created.');
        }

        $zip->addFromString('LÄS MIG.txt', $this->readme($family));

        foreach ($this->parts($family) as $name => $rows) {
            $zip->addFromString("{$name}.json", (string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        foreach (Picture::query()->where('family_id', $family->id)->get() as $picture) {
            if ($disk->exists($picture->path)) {
                $zip->addFile($disk->path($picture->path), 'bilder/'.$picture->id.'.'.pathinfo($picture->path, PATHINFO_EXTENSION));
            }
        }

        $zip->close();

        $export->forceFill([
            'status' => 'ready',
            'path' => $relative,
            'size' => $disk->size($relative),
            'finished_at' => now(),
            'expires_at' => now()->addDays(DataExport::DAYS_KEPT),
        ])->save();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function parts(Family $family): array
    {
        $id = $family->id;
        $own = fn (string $table) => DB::table($table)->where('family_id', $id)->orderBy('id');
        $via = fn (string $table, string $column, string $parent) => DB::table($table)->orderBy('id')
            ->whereIn($column, DB::table($parent)->where('family_id', $id)->select('id'));

        $parts = [
            'familj' => DB::table('families')->where('id', $id),
            'medlemmar' => DB::table('users')
                ->join('family_user', 'family_user.user_id', '=', 'users.id')
                ->where('family_user.family_id', $id)
                ->orderBy('users.id')
                ->select(['users.id', 'users.name', 'users.email', 'users.avatar_color', 'users.picture_id', 'users.reminders_enabled', 'users.quiet_from', 'users.quiet_until', 'family_user.role', 'family_user.school_menu_id', 'users.created_at']),
            'inbjudningar' => $own('family_invitations'),
            'listor' => $own('lists'),
            'listvaror' => $own('list_items'),
            'uppgifter' => $own('tasks'),
            'aterkommande_uppgifter' => $own('recurring_tasks'),
            'rutiner' => $own('routines'),
            'rutiner_steg' => $via('routine_steps', 'routine_id', 'routines'),
            'rutiner_barn' => $via('routine_child', 'routine_id', 'routines'),
            'rutiner_korningar' => $own('routine_runs'),
            'rutiner_bockar' => $via('routine_step_checks', 'routine_run_id', 'routine_runs'),
            'aktiviteter' => $own('activities'),
            'aktiviteter_deltagare' => $via('activity_participants', 'activity_id', 'activities'),
            'aktiviteter_packlistor' => $via('activity_items', 'activity_id', 'activities'),
            'aktiviteter_packat' => $via('activity_item_checks', 'activity_id', 'activities'),
            'aktiviteter_bekraftelser' => $via('activity_confirmations', 'activity_id', 'activities'),
            'dagbok_barn' => $via('journal_entry_child', 'journal_entry_id', 'journal_entries'),
            'poang' => $own('point_transactions'),
            'beloningar' => $own('rewards'),
            'onskningar' => $own('reward_redemptions'),
            'recept' => $own('recipes'),
            'recept_ingredienser' => $via('recipe_ingredients', 'recipe_id', 'recipes'),
            'egna_ingredienser' => $own('ingredients'),
            'skafferi' => $own('pantry_items'),
            'veckomatsedel' => $own('meal_plan_entries'),
            'skolor' => $own('school_menus'),
            'skolmat' => $via('school_meals', 'school_menu_id', 'school_menus'),
            'kalendrar' => $own('calendars'),
            'kalendrar_barn' => $via('calendar_child', 'calendar_id', 'calendars'),
            'kalenderhandelser' => $own('calendar_events'),
            'enheter' => $own('devices'),
            'hemskarmar_widgets' => $own('display_widgets'),
            'inloggningar' => $own('login_events'),
            'bilder' => $own('pictures'),
            'feedback' => $own('feedback'),
        ];

        $result = array_map(fn (Builder $query) => $this->rows($query), $parts);

        // The journal is encrypted in the database and exported readable.
        // Run with the family as the current family (ExportFamilyJob).
        $result['dagbok'] = array_values(JournalEntry::query()
            ->orderBy('date')
            ->get()
            ->map(fn (JournalEntry $entry) => [
                'id' => $entry->id,
                'author_id' => $entry->author_id,
                'date' => $entry->date->toDateString(),
                'content' => $entry->content,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])
            ->all());

        ksort($result);

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(Builder $query): array
    {
        $rows = [];

        foreach ($query->get() as $row) {
            $rows[] = array_diff_key((array) $row, array_flip(self::SECRET));
        }

        return $rows;
    }

    private function readme(Family $family): string
    {
        return <<<TXT
        Familjen {$family->name}: all data från NPF-familjen.

        Varje .json-fil är en del av appen, t.ex. uppgifter.json och dagbok.json.
        Mappen bilder innehåller familjens egna bilder. Filnamnet är bildens id
        i bilder.json.

        Tider är i UTC. Lösenord, barnens koder, kalenderlänkar och andra
        hemligheter är inte med.

        TXT;
    }
}
