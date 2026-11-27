<?php

namespace App\Providers;

use App\Models\User;
use App\Support\WebsiteContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            $origin = config('app.url');
            URL::useOrigin($origin);
            URL::forceScheme(parse_url($origin, PHP_URL_SCHEME));
        }

        View::composer('*', fn (\Illuminate\View\View $view) => $view->with('website', new WebsiteContent));
        Gate::define('access-admin', fn (User $user) => $user->role === 'administrator');
        View::composer(['testimonials', 'components.testimonial-slider'], function (\Illuminate\View\View $view): void {
            $query = DB::table('testimonials')->whereNull('deleted_at')->where('status', 'published')
                ->where('published_at', '<=', now())->where('consent_confirmed', true);
            if ($view->name() === 'components.testimonial-slider') {
                $query->where('show_on_homepage', true);
            }

            $view->with('websiteTestimonials', $query->orderBy('sort_order')->orderBy('id')->get());
        });
        View::composer(['components.layout', 'components.services', 'components.projects', 'maintenance'], function (\Illuminate\View\View $view): void {
            $view->with('siteSettings', DB::table('site_settings')->where('type', '!=', 'encrypted')->pluck('value', 'key'));
            foreach (['websiteServices' => 'services', 'websiteProjects' => 'portfolio_projects'] as $name => $table) {
                $query = DB::table($table)->whereNull('deleted_at')->where('status', 'published')->where('published_at', '<=', now());
                if ($table === 'portfolio_projects') {
                    $query->orderByDesc('is_exclusive');
                }
                $query->orderBy('sort_order')->orderBy('id');
                $view->with($name, $query->get());
            }
        });
    }
}
