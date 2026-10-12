<?php

namespace App\Http\Controllers\Lists;

use App\Enums\ListType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\StoreListRequest;
use App\Http\Requests\Lists\UpdateListRequest;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $lists = FamilyList::query()
            ->withCount(['items as open_count' => fn ($query) => $query->whereNull('completed_at')])
            ->orderByRaw('type = ? desc', [ListType::Shopping->value])
            ->orderBy('name')
            ->get();

        return Inertia::render('lists/Index', [
            'lists' => $lists->map(fn (FamilyList $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'isShopping' => $list->isShopping(),
                'openCount' => $list->getAttribute('open_count'),
            ]),
            'canCreate' => $user->can('create', FamilyList::class),
        ]);
    }

    public function store(StoreListRequest $request): RedirectResponse
    {
        $list = FamilyList::create([
            'type' => ListType::General,
            'name' => $request->string('name')->toString(),
            'created_by' => $request->user()?->id,
        ]);

        return to_route('lists.show', $list);
    }

    public function show(Request $request, FamilyList $list): Response
    {
        Gate::authorize('view', $list);

        /** @var User $user */
        $user = $request->user();

        $items = $list->items()
            ->with(['picture', 'adder:id,name'])
            ->orderByRaw('completed_at is not null')
            ->orderByDesc('completed_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('lists/Show', [
            'list' => [
                'id' => $list->id,
                'name' => $list->name,
                'isShopping' => $list->isShopping(),
            ],
            'items' => $items->map(fn (ListItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'picture' => $item->picture?->toPicker(),
                'addedBy' => $item->adder?->name,
                'completed' => $item->isCompleted(),
            ]),
            'can' => [
                'addItems' => $user->can('addItem', $list),
                'update' => $user->can('update', $list),
                'delete' => $user->can('delete', $list),
            ],
        ]);
    }

    public function update(UpdateListRequest $request, FamilyList $list): RedirectResponse
    {
        $list->update(['name' => $request->string('name')->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Namnet är sparat.']);

        return to_route('lists.show', $list);
    }

    public function destroy(FamilyList $list): RedirectResponse
    {
        Gate::authorize('delete', $list);

        $list->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$list->name} är borttagen."]);

        return to_route('lists.index');
    }
}
