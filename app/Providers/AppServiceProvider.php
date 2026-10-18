<?php

namespace App\Providers;

use App\Meals\MatildaMealProvider;
use App\Meals\MealProvider;
use App\Support\CurrentFamily;
use App\Support\RetryingDatabaseSessionHandler;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentFamily::class);

        // School meals come from Matilda's public menu (docs/ARCHITECTURE.md, Skolmat).
        $this->app->bind(MealProvider::class, MatildaMealProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureSessions();
    }

    /**
     * Database sessions that are saved again when two requests collide (deadlock).
     */
    protected function configureSessions(): void
    {
        Session::extend('database', fn ($app) => new RetryingDatabaseSessionHandler(
            DB::connection(config('session.connection')),
            (string) config('session.table'),
            (int) config('session.lifetime'),
            $app,
        ));
    }

    /**
     * Limit children's code attempts per trusted device, on top of the per-child
     * limit in ChildSessionController.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('child-login', function (Request $request) {
            $device = $request->cookie('npf_device');

            return Limit::perMinute(20)->by((is_string($device) ? $device : '').'|'.$request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
