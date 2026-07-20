<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Support\ServiceProvider;

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
        FilamentIcon::register([
            PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM => 'gmdi-dashboard-o',
        ]);
    }
}
