<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\EnsureUserIsEmployee;
use App\Http\Middleware\EnsureUserWithinCompanyScope;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('only-company', EnsureUserIsCompany::class);
        Route::aliasMiddleware('only-employee', EnsureUserIsEmployee::class);
        Route::aliasMiddleware('scope-company', EnsureUserWithinCompanyScope::class);
    }
}
