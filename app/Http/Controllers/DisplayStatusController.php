<?php

namespace App\Http\Controllers;

use App\Display\DisplayContext;
use App\Display\DisplayView;
use App\Display\ScreenSchedule;
use App\Http\Requests\Display\DisplayStatusRequest;
use App\Support\CurrentFamily;
use Illuminate\Http\JsonResponse;

/**
 * Asked by the Raspberry Pi every minute (docs/DRIFT.md): should the screen
 * be on, should the TV be told over HDMI-CEC, and when to restart. The Pi
 * says what it did, which the app shows as the display's status.
 */
class DisplayStatusController extends Controller
{
    public function show(DisplayStatusRequest $request, CurrentFamily $current): JsonResponse
    {
        $device = $current->device();
        abort_if($device === null, 404);

        $now = now()->toImmutable();
        $screen = ScreenSchedule::settings($device);
        $view = DisplayView::current($device, new DisplayContext($current->getOrFail()));

        $device->forceFill(['settings' => [...($device->settings ?? []), 'pi' => [
            'seen_at' => $now->toIso8601String(),
            'power' => $request->input('power'),
            'uptime' => $request->filled('uptime') ? $request->integer('uptime') : null,
        ]]])->saveQuietly();

        return response()->json([
            'power' => ScreenSchedule::isOn($device, $view, $now) ? 'on' : 'off',
            'cec' => $screen['cec'],
            'reboot' => $screen['reboot']['enabled'] ? $screen['reboot']['time'] : null,
        ]);
    }
}
