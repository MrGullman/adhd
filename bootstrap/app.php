<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureFamilyMember;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveCurrentFamily;
use App\Http\Middleware\ResolveDisplay;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            ResolveCurrentFamily::class,
            EnsureAccountActive::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
        ]);

        // The current family must be known before route model binding runs,
        // so that BelongsToFamily scopes bound models to the family.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolveCurrentFamily::class);

        $middleware->alias([
            'family' => EnsureFamilyMember::class,
            'role' => EnsureRole::class,
            'display' => ResolveDisplay::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
