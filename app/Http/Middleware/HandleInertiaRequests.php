<?php

namespace App\Http\Middleware;

use App\Models\Family;
use App\Models\Membership;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $current = app(CurrentFamily::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'family' => fn () => $current->get() === null ? null : [
                'id' => $current->get()->id,
                'name' => $current->get()->name,
                'role' => $current->role()?->value,
                'roleLabel' => $current->role()?->label(),
                'canEdit' => $current->role()?->canEdit() ?? false,
                'canManage' => $current->role()?->canManage() ?? false,
            ],
            'families' => fn () => $this->families($request, $current),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The families an adult can switch between.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function families(Request $request, CurrentFamily $current): array
    {
        $user = $request->user();

        if (! $user instanceof User || ! ($current->role()?->isAdult() ?? false)) {
            return [];
        }

        return $user->families()
            ->orderBy('name')
            ->get()
            ->filter(function (Family $family): bool {
                $membership = $family->getRelation('pivot');

                return $membership instanceof Membership && $membership->role->isAdult();
            })
            ->map(fn (Family $family) => ['id' => $family->id, 'name' => $family->name])
            ->values()
            ->all();
    }
}
