<?php

namespace App\Providers;

use App\Models\Setting;
use App\Policies\SettingPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Setting::class, SettingPolicy::class);

        // Hand-written, class-light pagination markup (no CSS framework).
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // Site settings are available in every view as $site.
        View::composer(['layouts.*', 'partials.*', 'components.*'], function ($view) {
            $view->with('site', Setting::allValues());
        });
    }
}
