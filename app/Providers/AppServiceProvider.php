<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Share the favicon URL with your root Inertia view
        View::composer('app', function ($view) {
            $faviconUrl = Cache::rememberForever('favicon_url', function () {
                $setting = SystemSetting::first();
                
                // Ensure lgu_logo_path exists, otherwise fallback to default public/favicon.ico
                return $setting && $setting->lgu_logo_path 
                    ? asset('storage/' . $setting->lgu_logo_path) 
                    : asset('favicon.ico'); 
            });

            $view->with('faviconUrl', $faviconUrl);
        });
    }
}