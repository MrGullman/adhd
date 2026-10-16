<?php

namespace App\Http\Controllers;

use App\Display\DisplayContext;
use App\Display\DisplayView;
use App\Display\WidgetRegistry;
use App\Models\DisplayWidget;
use App\Support\CurrentFamily;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The home display: read-only, no menu, no buttons. What it shows can be
 * changed from an adult's phone (DisplayView).
 */
class DisplayController extends Controller
{
    public function show(CurrentFamily $current): Response
    {
        $device = $current->device();
        abort_if($device === null, 404);

        $context = new DisplayContext($current->getOrFail());
        $view = DisplayView::current($device, $context);

        return Inertia::render('display/Index', [
            // Asked for every few seconds: when it changes, the display loads the new view.
            'viewKey' => $view->key(),
            'view' => $view->present(),
            ...self::header($context),
            'density' => $device->settings['density'] ?? 'medium',
            'widgets' => fn () => WidgetRegistry::render($context, match ($view->mode) {
                'family' => DisplayWidget::query()->where('device_id', $device->id)->orderBy('y')->orderBy('x')->get(),
                'member' => $view->widgets($context),
                default => [],
            }),
        ]);
    }

    /**
     * Date, week and part of the day for the top of the display.
     *
     * @return array<string, mixed>
     */
    public static function header(DisplayContext $context): array
    {
        return [
            'date' => $context->today->toDateString(),
            'week' => $context->today->isoWeek(),
            'dayPart' => ['value' => $context->dayPart()->value, 'label' => $context->dayPart()->label()],
            'evening' => $context->isEvening(),
        ];
    }
}
