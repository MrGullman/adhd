<?php

namespace App\Http\Controllers\Family;

use App\Display\DisplayContext;
use App\Display\WidgetRegistry;
use App\Display\Widgets\DisplayWidgetType;
use App\Enums\DeviceType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DisplayController as ShowDisplay;
use App\Http\Requests\Display\StoreDisplayRequest;
use App\Http\Requests\Display\StoreDisplayWidgetRequest;
use App\Http\Requests\Display\UpdateDisplayLayoutRequest;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parents set up home displays: which widgets, where and how big.
 */
class DisplayController extends Controller
{
    public function store(StoreDisplayRequest $request): RedirectResponse
    {
        [$device, $token] = Device::issue(DeviceType::Display, $request->string('name')->toString());
        $device->forceFill(['settings' => ['density' => 'medium']])->save();

        Inertia::flash('displayLink', route('display.show', $token));

        return to_route('displays.edit', $device);
    }

    /**
     * The editor, with a preview that looks like the display.
     */
    public function edit(CurrentFamily $current, Device $device): Response
    {
        $this->authorizeDisplay($current, $device);
        $family = $current->getOrFail();
        $context = new DisplayContext($family);

        return Inertia::render('display/Edit', [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'density' => $device->settings['density'] ?? 'medium',
                'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
            ],
            ...ShowDisplay::header($context),
            'widgets' => WidgetRegistry::render($context, DisplayWidget::query()->where('device_id', $device->id)->orderBy('y')->orderBy('x')->get()),
            'widgetTypes' => array_map(fn (DisplayWidgetType $type) => [
                'type' => $type->type(),
                'label' => $type->label(),
                'setting' => $type->setting(),
            ], WidgetRegistry::all()),
            'children' => $context->children()->map(fn (User $child) => DisplayContext::member($child))->values(),
            'members' => $family->members()->with('picture')->orderBy('name')->get()->map(fn (User $member) => DisplayContext::member($member))->values(),
        ]);
    }

    public function update(StoreDisplayRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);

        $device->forceFill([
            'name' => $request->string('name')->toString(),
            'settings' => [...($device->settings ?? []), 'density' => $request->input('density', 'medium')],
        ])->save();

        return to_route('displays.edit', $device);
    }

    /**
     * A new link, e.g. when the old one is lost. The old link stops working.
     */
    public function link(CurrentFamily $current, Device $device): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);

        $token = DB::transaction(function () use ($device): string {
            $token = bin2hex(random_bytes(32));
            $device->forceFill(['token_hash' => hash('sha256', $token)])->save();

            return $token;
        });

        Inertia::flash('displayLink', route('display.show', $token));

        return to_route('displays.edit', $device);
    }

    public function addWidget(StoreDisplayWidgetRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);

        $type = WidgetRegistry::find($request->string('type')->toString());
        [$w, $h] = $type?->defaultSize() ?? [4, 2];

        [$x, $y] = $this->freeSpot($device, $w, $h);

        $widget = new DisplayWidget([
            'type' => $request->string('type')->toString(),
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h,
            'settings' => $request->settings(),
        ]);
        $widget->device_id = $device->id;
        $widget->save();

        return to_route('displays.edit', $device);
    }

    public function layout(UpdateDisplayLayoutRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);

        DB::transaction(function () use ($request, $device): void {
            foreach ((array) $request->input('widgets') as $position) {
                DisplayWidget::query()
                    ->where('device_id', $device->id)
                    ->whereKey((int) $position['id'])
                    ->update([
                        'x' => (int) $position['x'],
                        'y' => (int) $position['y'],
                        'w' => (int) $position['w'],
                        'h' => (int) $position['h'],
                    ]);
            }
        });

        return to_route('displays.edit', $device);
    }

    public function removeWidget(CurrentFamily $current, Device $device, DisplayWidget $widget): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);
        abort_unless($widget->device_id === $device->id, 404);

        $widget->delete();

        return to_route('displays.edit', $device);
    }

    /**
     * The first place, row by row, where a widget of this size fits without
     * covering another. On a full display it goes top left and is moved there.
     *
     * @return array{0: int, 1: int}
     */
    private function freeSpot(Device $device, int $w, int $h): array
    {
        $taken = DisplayWidget::query()->where('device_id', $device->id)->get(['x', 'y', 'w', 'h']);

        for ($y = 0; $y + $h <= DisplayWidget::ROWS; $y++) {
            for ($x = 0; $x + $w <= DisplayWidget::COLUMNS; $x++) {
                $free = $taken->every(fn (DisplayWidget $other) => $x + $w <= $other->x
                    || $other->x + $other->w <= $x
                    || $y + $h <= $other->y
                    || $other->y + $other->h <= $y);

                if ($free) {
                    return [$x, $y];
                }
            }
        }

        return [0, 0];
    }

    private function authorizeDisplay(CurrentFamily $current, Device $device): void
    {
        Gate::authorize('manageMembers', $current->getOrFail());
        abort_unless($device->type === DeviceType::Display, 404);
    }
}
