<?php

namespace App\Http\Controllers\Lists;

use App\Enums\ListType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\ChooseIcaListRequest;
use App\Http\Requests\Lists\ConnectIcaRequest;
use App\Ica\IcaClient;
use App\Ica\IcaException;
use App\Ica\IcaSync;
use App\Jobs\SyncIcaListItem;
use App\Models\FamilyList;
use App\Models\IcaConnection;
use App\Models\ListItem;
use App\Support\CurrentFamily;
use App\Support\FamilyFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handla → ICA: parents connect the shopping list to one of their ICA lists.
 * A trial an admin turns on per family; ICA's API is unofficial, so the
 * family's own list always works on its own. ICA is only called when a
 * parent asks (connect, fetch lists) and from the queue, never to show a page.
 */
class IcaConnectionController extends Controller
{
    public function show(CurrentFamily $current): Response
    {
        $this->authorizeFeature($current);
        Gate::authorize('manage', IcaConnection::class);

        $connection = IcaConnection::query()->with('user:id,name')->first();

        return Inertia::render('lists/Ica', [
            'available' => IcaClient::available(),
            'connection' => $connection === null ? null : [
                'connectedBy' => $connection->user?->name,
                'lists' => $connection->lists ?? [],
                'listId' => $connection->list_id,
                'listTitle' => $connection->list_title,
                'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
                'lastError' => $connection->last_error,
            ],
        ]);
    }

    /**
     * Log in to ICA once and fetch the lists. The password is forgotten afterwards.
     */
    public function store(ConnectIcaRequest $request, CurrentFamily $current, IcaClient $client): RedirectResponse
    {
        $this->authorizeFeature($current);
        abort_unless(IcaClient::available(), 404);

        try {
            $login = $client->login($request->personalId(), $request->string('ica_password')->toString());
            $lists = $client->lists((string) ($login['token']['access_token'] ?? ''));
        } catch (IcaException $exception) {
            return back()->withErrors(['ica_password' => $exception->getMessage()]);
        }

        $connection = IcaConnection::query()->firstOrNew();
        $connection->forceFill([
            'user_id' => $request->user()?->id,
            'client' => $login['client'],
            'token' => $login['token'],
            'token_expires_at' => now()->addSeconds((int) ($login['token']['expires_in'] ?? 2_592_000)),
            'lists' => $lists,
            'last_error' => null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kopplad till ICA. Välj vilken ICA-lista som ska användas.']);

        return to_route('ica.show');
    }

    /**
     * Fetch the ICA lists again, e.g. after making a new one in ICA's app.
     */
    public function lists(CurrentFamily $current, IcaSync $sync, IcaClient $client): RedirectResponse
    {
        $this->authorizeFeature($current);
        $connection = IcaConnection::query()->firstOrFail();
        Gate::authorize('update', $connection);

        try {
            $connection->forceFill(['lists' => $client->lists($sync->accessToken($connection)), 'last_error' => null])->save();
        } catch (IcaException $exception) {
            $connection->forceFill(['last_error' => $exception->getMessage()])->save();
            Inertia::flash('toast', ['type' => 'info', 'message' => $exception->getMessage()]);
        }

        return to_route('ica.show');
    }

    /**
     * The ICA list to use. What is on the shopping list now is sent there.
     */
    public function update(ChooseIcaListRequest $request, CurrentFamily $current): RedirectResponse
    {
        $this->authorizeFeature($current);
        $connection = IcaConnection::query()->firstOrFail();
        $list = collect($connection->lists ?? [])->firstWhere('id', $request->string('list_id')->toString());

        $connection->forceFill(['list_id' => $list['id'] ?? null, 'list_title' => $list['title'] ?? null])->save();

        $shopping = FamilyList::query()->where('type', ListType::Shopping)->first();
        $shopping?->items()->whereNull('completed_at')->get()->each(function (ListItem $item) use ($current): void {
            $item->forceFill(['ica_row_id' => null])->saveQuietly();
            SyncIcaListItem::dispatch((int) $current->id(), 'add', $item->id);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Handlingslistan skickas nu till {$connection->list_title}."]);

        return to_route('ica.show');
    }

    public function destroy(CurrentFamily $current): RedirectResponse
    {
        $this->authorizeFeature($current);
        $connection = IcaConnection::query()->firstOrFail();
        Gate::authorize('delete', $connection);

        $connection->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ICA är bortkopplad. Handlingslistan fungerar som vanligt.']);

        return to_route('ica.show');
    }

    private function authorizeFeature(CurrentFamily $current): void
    {
        abort_unless(FamilyFeatures::enabled($current->getOrFail(), FamilyFeatures::ICA), 404);
    }
}
