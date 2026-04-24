<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
require_once app_path('Helpers/ViewHelper.php');

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
        //
    }
}
