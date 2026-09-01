<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The views use Bootstrap 5, which is not the framework default.
        Paginator::useBootstrapFive();

        // Catch N+1 queries in the test suite, but stay permissive at runtime
        // so a missed eager-load degrades into extra queries rather than a 500.
        Model::preventLazyLoading($this->app->runningUnitTests());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
