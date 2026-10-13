<?php

namespace App\Http\Controllers;

use App\Http\Requests\Journal\StoreJournalEntryRequest;
use App\Http\Requests\Journal\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dagboken: the parents' notes about the children, newest first.
 */
class JournalController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response
    {
        Gate::authorize('viewAny', JournalEntry::class);

        /** @var User $user */
        $user = $request->user();
        $childId = $request->integer('barn') ?: null;
        $search = mb_strtolower(trim($request->string('sok')->toString()));

        $entries = JournalEntry::query()
            ->with(['author:id,name', 'children:id,name'])
            ->when($childId !== null, fn ($query) => $query->whereHas('children', fn ($query) => $query->whereKey($childId)))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            // The content is encrypted, so it is searched after it is read.
            ->when($search !== '', fn ($entries) => $entries->filter(fn (JournalEntry $entry) => str_contains(mb_strtolower($entry->content), $search)))
            ->values();

        return Inertia::render('journal/Index', [
            'entries' => $entries->map(fn (JournalEntry $entry) => [
                'id' => $entry->id,
                'date' => $entry->date->toDateString(),
                'content' => $entry->content,
                'author' => $entry->author?->name,
                'isYours' => $entry->author_id === $user->id,
                'children' => $entry->children->map(fn (User $child) => ['id' => $child->id, 'name' => $child->name])->values(),
            ]),
            'children' => $current->getOrFail()->children()->with('picture')->orderBy('name')->get()->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
            ]),
            'filter' => ['child' => $childId, 'search' => $request->string('sok')->toString()],
            'today' => LocalTime::today(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entry = new JournalEntry([
            'date' => $request->string('date')->toString(),
            'content' => $request->string('content')->toString(),
        ]);
        $entry->author_id = $request->user()?->id;
        $entry->save();
        $entry->children()->sync($request->children());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anteckningen är sparad.']);

        return to_route('journal.index');
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $entry): RedirectResponse
    {
        $entry->update([
            'date' => $request->string('date')->toString(),
            'content' => $request->string('content')->toString(),
        ]);
        $entry->children()->sync($request->children());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anteckningen är sparad.']);

        return back();
    }

    public function destroy(JournalEntry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anteckningen är borttagen.']);

        return back();
    }
}
