<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two texts in Om NPF that promised more than the app does: they now say
 * what the app can do. Only the exact first wording is replaced, so nothing
 * an admin has changed is overwritten.
 */
return new class extends Migration
{
    /**
     * @var list<array{slug: string, column: string, from: string, to: string}>
     */
    private const CHANGES = [
        [
            'slug' => 'sa-hjalper-npf-familjen',
            'column' => 'body',
            'from' => '- **Skapa delaktighet.** Låta barn och vuxna vara med och forma sina rutiner och mål.',
            'to' => '- **Skapa delaktighet.** Barnen väljer sin egen bild, önskar belöningar och kan i läget Ungdom lägga in egna uppgifter. Gör rutinerna tillsammans med barnet, så att de känns som barnets egna.',
        ],
        [
            'slug' => 'nar-planerna-andras',
            'column' => 'in_app',
            'from' => 'Markera dagen som **Annorlunda** när den inte blir som vanligt, så att den syns tydligt för alla. Lägg in det som gäller i stället med bildstöd, så att barnet kan se den nya planen i Min dag och på **hemskärmen**.',
            'to' => 'Är det en aktivitet som är varje vecka, som simskolan, trycker du **Ställ in den här gången**. Då syns den som inställd just den dagen, utan packning och påminnelser. Lägg in det som gäller i stället med bildstöd, och markera det som **Annorlunda**, så att barnet kan se den nya planen i Min dag och på **hemskärmen**.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::CHANGES as $change) {
            $this->replace($change['slug'], $change['column'], $change['from'], $change['to']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::CHANGES as $change) {
            $this->replace($change['slug'], $change['column'], $change['to'], $change['from']);
        }
    }

    private function replace(string $slug, string $column, string $from, string $to): void
    {
        $text = DB::table('articles')->where('slug', $slug)->value($column);

        if (is_string($text) && str_contains($text, $from)) {
            DB::table('articles')->where('slug', $slug)->update([$column => str_replace($from, $to, $text), 'updated_at' => now()]);
        }
    }
};
