<?php

namespace App\Providers;

use App\Models\User;
use App\Support\WebsiteContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', fn (\Illuminate\View\View $view) => $view->with('website', new WebsiteContent));
        Gate::define('access-admin', fn (User $user) => $user->role === 'administrator');
        View::composer(['components.layout', 'components.services', 'components.projects', 'testimonials', 'maintenance'], function (\Illuminate\View\View $view): void {
            $view->with('siteSettings', DB::table('site_settings')->where('type', '!=', 'encrypted')->pluck('value', 'key'));
            foreach (['websiteServices' => 'services', 'websiteProjects' => 'portfolio_projects', 'websiteTestimonials' => 'testimonials'] as $name => $table) {
                $query = DB::table($table)->whereNull('deleted_at')->where('status', 'published')->where('published_at', '<=', now());
                if ($table === 'portfolio_projects') {
                    $query->orderByDesc('is_exclusive');
                }
                $query->orderBy('sort_order')->orderBy('id');
                if ($table === 'testimonials') {
                    $query->where('consent_confirmed', true);
                }
                $view->with($name, $query->get());
            }
        });
    }
}
