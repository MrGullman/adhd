<?php

namespace Tests\Feature\Lists;

use App\Enums\FamilyRole;
use App\Enums\ListType;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Jobs\SyncIcaListItem;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\IcaConnection;
use App\Models\ListItem;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\FamilyFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Handla → ICA: a trial an admin turns on per family. ICA is never called for
 * real here; its login and list service are faked.
 */
class IcaConnectionTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private FamilyList $shopping;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ica.client_id' => 'app-client', 'services.ica.client_secret' => 'app-secret']);
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->shopping = FamilyList::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->where('type', ListType::Shopping)->firstOrFail();
    }

    private function enable(): void
    {
        FamilyFeatures::set($this->family, FamilyFeatures::ICA, true);
    }

    private function connection(array $attributes = []): IcaConnection
    {
        return IcaConnection::factory()->create(['family_id' => $this->family->id, ...$attributes]);
    }

    /**
     * ICA's login and list service, as far as the app uses them.
     *
     * @param  list<array<string, mixed>>  $sent  What was sent to ICA's list service.
     */
    private function fakeIca(array &$sent = [], int $loginStatus = 200, int $listStatus = 200): void
    {
        Http::fake(function (Request $request) use (&$sent, $loginStatus, $listStatus) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/oauth/v2/token') && ($request['grant_type'] ?? null) === 'client_credentials' => Http::response(['access_token' => 'registration']),
                str_contains($url, '/oauth/v2/token') => Http::response(['access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'expires_in' => 2592000]),
                str_contains($url, '/register') => Http::response(['client_id' => 'own-client', 'client_secret' => 'own-secret', 'scope' => 'openid']),
                str_contains($url, '/oauth/v2/authorize') && $request->method() === 'GET' => Http::response('', 302, ['Location' => 'https://ims.icagruppen.se/authn/authenticate/IcaCustomers?x=1&state=abc']),
                str_contains($url, '/authn/authenticate/IcaCustomers') && $request->method() === 'GET' => Http::response('<form></form>'),
                str_contains($url, '/authn/authenticate/IcaCustomers') => $loginStatus === 200
                    ? Http::response('<input type="hidden" name="state" value="abc"><input type="hidden" name="token" value="formtoken">')
                    : Http::response('', $loginStatus),
                str_contains($url, '/oauth/v2/authorize') => Http::response('', 302, ['Location' => 'icacurity://app?code=xyz&state=abc']),
                str_ends_with($url, '/shoppinglists') => Http::response(['shoppingLists' => [['id' => 1, 'offlineId' => 'list-1', 'title' => 'Handla', 'rows' => []], ['id' => 2, 'offlineId' => 'list-2', 'title' => 'Helgen', 'rows' => []]]]),
                str_contains($url, '/sync') => (function () use (&$sent, $request, $listStatus) {
                    $sent[] = $request->data();

                    return Http::response([], $listStatus);
                })(),
                default => Http::response('unexpected '.$url, 500),
            };
        });
    }

    public function test_the_ica_connection_is_off_unless_an_admin_turns_it_on()
    {
        $this->actingAs($this->parent)->get(route('ica.show'))->assertNotFound();
        $this->actingAs($this->parent)->get(route('lists.show', $this->shopping))->assertInertia(fn (Assert $page) => $page->where('ica', null));

        $this->enable();

        $this->actingAs($this->parent)->get(route('ica.show'))->assertInertia(fn (Assert $page) => $page
            ->component('lists/Ica')
            ->where('available', true)
            ->where('connection', null));
    }

    public function test_a_parent_logs_in_once_and_the_password_is_never_kept()
    {
        $this->enable();
        $this->fakeIca();

        $this->actingAs($this->parent)->post(route('ica.store'), ['personal_id' => '850101-1234', 'ica_password' => 'Hemligt-123'])
            ->assertRedirect(route('ica.show'));

        $connection = IcaConnection::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->firstOrFail();
        $this->assertSame('access-1', $connection->token['access_token']);
        $this->assertSame([['id' => 'list-1', 'title' => 'Handla'], ['id' => 'list-2', 'title' => 'Helgen']], $connection->lists);

        // Tokens are encrypted in the database; the password and personal ID are nowhere.
        $raw = json_encode(DB::table('ica_connections')->first());
        $this->assertStringNotContainsString('access-1', (string) $raw);
        $this->assertStringNotContainsString('Hemligt-123', (string) $raw);
        $this->assertStringNotContainsString('198501011234', (string) $raw);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'IcaCustomers') && ($request['userName'] ?? null) === '198501011234');
    }

    public function test_a_wrong_password_says_so_calmly_and_keeps_nothing()
    {
        $this->enable();
        $this->fakeIca(loginStatus: 400);

        $this->actingAs($this->parent)->post(route('ica.store'), ['personal_id' => '198501011234', 'ica_password' => 'fel'])
            ->assertSessionHasErrors(['ica_password' => 'ICA godkände inte personnumret eller lösenordet.']);

        $this->assertDatabaseCount('ica_connections', 0);
        $this->assertNull(session()->getOldInput('ica_password'));
        $this->assertNull(session()->getOldInput('personal_id'));
    }

    public function test_choosing_a_list_sends_what_is_on_the_shopping_list()
    {
        $this->enable();
        $this->connection(['list_id' => null, 'list_title' => null]);
        app(CurrentFamily::class)->set($this->family);
        $milk = $this->shopping->items()->create(['name' => 'Mjölk']);
        $this->shopping->items()->create(['name' => 'Bröd'])->forceFill(['completed_at' => now()])->save();
        app(CurrentFamily::class)->forget();
        Queue::fake();

        $this->actingAs($this->parent)->put(route('ica.update'), ['list_id' => 'list-1'])->assertRedirect(route('ica.show'));
        $this->actingAs($this->parent)->put(route('ica.update'), ['list_id' => 'someone-elses'])->assertSessionHasErrors('list_id');

        Queue::assertPushed(SyncIcaListItem::class, 1);
        Queue::assertPushed(SyncIcaListItem::class, fn (SyncIcaListItem $job) => $job->action === 'add' && $job->itemId === $milk->id);
        $this->assertSame('Handla', IcaConnection::query()->withoutGlobalScopes()->value('list_title'));
    }

    public function test_adding_ticking_off_and_removing_follow_to_ica()
    {
        $this->enable();
        $this->connection();
        $sent = [];
        $this->fakeIca($sent);

        // The queue runs at once in tests: each change goes straight to ICA.
        $this->actingAs($this->parent)->post(route('list-items.store', $this->shopping), ['name' => 'Mjölk'])->assertRedirect();
        $item = ListItem::query()->withoutGlobalScopes()->where('name', 'Mjölk')->firstOrFail();
        $this->assertNotNull($item->ica_row_id);
        $this->assertSame('Mjölk', $sent[0]['createdRows'][0]['productName']);

        $this->actingAs($this->parent)->post(route('list-items.completion.store', [$this->shopping, $item]));
        $this->assertTrue($sent[1]['changedRows'][0]['isStrikedOver']);

        $this->actingAs($this->parent)->delete(route('list-items.destroy', [$this->shopping, $item]));
        $this->assertSame([$item->ica_row_id], $sent[2]['deletedRows']);
    }

    public function test_a_logged_out_token_is_renewed_once_and_the_change_sent_again()
    {
        $this->enable();
        $this->connection();
        $calls = 0;
        Http::fake(function (Request $request) use (&$calls) {
            return match (true) {
                str_contains($request->url(), '/oauth/v2/token') => Http::response(['access_token' => 'access-2', 'refresh_token' => 'refresh-2', 'expires_in' => 2592000]),
                str_contains($request->url(), '/sync') => ++$calls === 1 ? Http::response([], 401) : Http::response([]),
                default => Http::response('unexpected', 500),
            };
        });

        $this->actingAs($this->parent)->post(route('list-items.store', $this->shopping), ['name' => 'Mjölk'])->assertRedirect();

        $this->assertSame(2, $calls);
        $this->assertNotNull(ListItem::query()->withoutGlobalScopes()->where('name', 'Mjölk')->value('ica_row_id'));
        $this->assertSame('access-2', IcaConnection::query()->withoutGlobalScopes()->firstOrFail()->token['access_token']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/sync') && $request->hasHeader('Authorization', 'Bearer access-2'));
    }

    public function test_when_ica_fails_the_list_here_works_and_the_error_is_shown_calmly()
    {
        $this->enable();
        $this->connection();
        $sent = [];
        $this->fakeIca($sent, listStatus: 500);

        $this->actingAs($this->parent)->post(route('list-items.store', $this->shopping), ['name' => 'Mjölk'])->assertRedirect();

        $this->assertDatabaseHas('list_items', ['name' => 'Mjölk', 'ica_row_id' => null]);
        $this->actingAs($this->parent)->get(route('lists.show', $this->shopping))->assertInertia(fn (Assert $page) => $page
            ->where('items.0.name', 'Mjölk')
            ->where('ica.lastError', 'ICA svarade inte som väntat (500).'));
    }

    public function test_nothing_is_sent_when_the_trial_is_off()
    {
        $this->connection();
        Queue::fake();

        $this->actingAs($this->parent)->post(route('list-items.store', $this->shopping), ['name' => 'Mjölk']);

        Queue::assertNothingPushed();
    }

    public function test_only_parents_connect_and_another_family_is_never_reached()
    {
        $this->enable();
        $this->connection();
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)->get(route('ica.show'))->assertForbidden();
        $this->actingAs($editor)->post(route('ica.store'), ['personal_id' => '198501011234', 'ica_password' => 'x'])->assertForbidden();
        $this->actingAs($editor)->delete(route('ica.destroy'))->assertForbidden();

        $other = Family::factory()->create();
        FamilyFeatures::set($other, FamilyFeatures::ICA, true);
        $otherParent = User::factory()->inFamily($other)->create();

        $this->actingAs($otherParent)->get(route('ica.show'))->assertInertia(fn (Assert $page) => $page->where('connection', null));
        $this->actingAs($otherParent)->delete(route('ica.destroy'))->assertNotFound();
        $this->assertDatabaseCount('ica_connections', 1);
    }

    public function test_an_admin_turns_the_trial_on_and_off_for_one_family()
    {
        $this->connection();
        $other = Family::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ListFamilies::class)->callTableAction('ica', $this->family)->assertHasNoTableActionErrors();
        $this->assertTrue(FamilyFeatures::enabled($this->family->fresh() ?? $this->family, FamilyFeatures::ICA));
        $this->assertFalse(FamilyFeatures::enabled($other, FamilyFeatures::ICA));

        // Off again: the connection and ICA's tokens are removed.
        Livewire::test(ListFamilies::class)->callTableAction('ica', $this->family)->assertHasNoTableActionErrors();
        $this->assertFalse(FamilyFeatures::enabled($this->family->fresh() ?? $this->family, FamilyFeatures::ICA));
        $this->assertDatabaseCount('ica_connections', 0);
    }
}
