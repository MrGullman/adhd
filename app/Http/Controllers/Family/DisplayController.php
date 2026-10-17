<?php

namespace App\Http\Controllers\Family;

use App\Display\DisplayContext;
use App\Display\Layout;
use App\Display\ScreenSchedule;
use App\Display\WidgetRegistry;
use App\Display\Widgets\DisplayWidgetType;
use App\Enums\DeviceType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DisplayController as ShowDisplay;
use App\Http\Requests\Display\StoreDisplayRequest;
use App\Http\Requests\Display\StoreDisplayWidgetRequest;
use App\Http\Requests\Display\UpdateDisplayLayoutRequest;
use App\Http\Requests\Display\UpdateScreenScheduleRequest;
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
        $this->repair($device);

        return Inertia::render('display/Edit', [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'density' => $device->settings['density'] ?? 'medium',
                'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
                'screen' => ScreenSchedule::settings($device),
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

    /**
     * When the screen is on, the Pi's restart and the TV over HDMI-CEC.
     */
    public function schedule(UpdateScreenScheduleRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $this->authorizeDisplay($current, $device);

        ScreenSchedule::save($device, $request->screen());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat. Skärmen följer de nya tiderna inom en minut.']);

        return to_route('displays.edit', $device);
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

        // Never on top of another widget: smaller if needed, or not at all.
        $spot = Layout::place($this->taken($device), $w, $h);

        if ($spot === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Skärmen är full. Gör en widget mindre eller ta bort en först.']);

            return to_route('displays.edit', $device);
        }

        ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h] = $spot;

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
     * Widgets on top of each other are moved apart, so that the editor and
     * the display always show a layout that works.
     */
    private function repair(Device $device): void
    {
        $widgets = DisplayWidget::query()->where('device_id', $device->id)->orderBy('id')->get();

        $changes = Layout::arrange(array_values($widgets->map(fn (DisplayWidget $widget) => [
            'id' => $widget->id,
            'x' => $widget->x,
            'y' => $widget->y,
            'w' => $widget->w,
            'h' => $widget->h,
        ])->all()));

        foreach ($changes as $id => $spot) {
            $widgets->firstWhere('id', $id)?->update($spot);
        }
    }

    /**
     * Where the display's widgets are now.
     *
     * @return list<array{x: int, y: int, w: int, h: int}>
     */
    private function taken(Device $device): array
    {
        return array_values(DisplayWidget::query()
            ->where('device_id', $device->id)
            ->get(['x', 'y', 'w', 'h'])
            ->map(fn (DisplayWidget $widget) => ['x' => $widget->x, 'y' => $widget->y, 'w' => $widget->w, 'h' => $widget->h])
            ->all());
    }

    private function authorizeDisplay(CurrentFamily $current, Device $device): void
    {
        Gate::authorize('manageMembers', $current->getOrFail());
        abort_unless($device->type === DeviceType::Display, 404);
    }
}
