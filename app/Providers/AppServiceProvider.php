<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerMiddlewareAliases();
    }

    /**
     * Register app-specific middleware aliases.
     */
    private function registerMiddlewareAliases(): void
    {
        try {
            $router = $this->app->make(\Illuminate\Routing\Router::class);
            $router->aliasMiddleware('ensure.link.belongs', \App\Http\Middleware\EnsureLinkBelongsToUser::class);
            $router->aliasMiddleware('ensure.within.links.quota', \App\Http\Middleware\EnsureWithinLinksQuota::class);
        } catch (\Throwable $e) {
            // noop - keep boot resilient in environments where router isn't available yet
        }
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
            : null
        );
    }
}
