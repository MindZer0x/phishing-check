<?php

namespace App\Providers;

use App\Check\HeuristicAnalysis;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HeuristicAnalysis::class, fn (): HeuristicAnalysis => HeuristicAnalysis::fromConfig(config('check')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
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
    }

    /**
     * Each check may later call the paid AI analysis, so limit it per client.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('checks', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
