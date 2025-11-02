<?php

namespace App\Providers;

use App\Models\Watch;
use App\Models\WatchSimilarity;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.navigation', function ($view) {
            $view->with('watchCount', Watch::count());
            $view->with('brandCount', Watch::distinct('brand')->count('brand'));
            $view->with('imageCount', Watch::where('image_url', 'like', '%imagekit%')->count());
            $view->with('similarityCount', WatchSimilarity::count());
        });
    }
}
