<?php

namespace App\Http\Controllers;

use App\Support\GoogleReviews;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function page(string $page = 'home'): View
    {
        abort_unless(in_array($page, ['home', 'about', 'services', 'portfolio', 'testimonials', 'contact', 'engagement', 'privacy', 'terms'], true), 404);
        $pageContent = $this->published('pages')->where('slug', $page)->first();
        abort_unless($pageContent, 404);

        return view($page, compact('pageContent') + ($page === 'testimonials' ? ['googleReviews' => app(GoogleReviews::class)->snapshot()] : []));
    }

    public function service(string $slug): View
    {
        $record = $this->published('services')->where('slug', $slug)->first();
        abort_unless($record, 404);

        return view('service', ['title' => $record->title, 'description' => $record->description, 'record' => $record]);
    }

    public function project(string $slug): View
    {
        $record = $this->published('portfolio_projects')->where('slug', $slug)->first();
        abort_unless($record, 404);

        return view('project', ['title' => $record->title, 'record' => $record]);
    }

    public function sitemap(): Response
    {
        $urls = collect();
        foreach ($this->published('pages')->pluck('slug') as $slug) {
            $urls->push(route($slug));
        }
        foreach (['services' => 'services.show', 'portfolio_projects' => 'portfolio.show'] as $table => $route) {
            foreach ($this->published($table)->pluck('slug') as $slug) {
                $urls->push(route($route, $slug));
            }
        }

        return response()->view('sitemap', compact('urls'))->header('Content-Type', 'application/xml');
    }

    private function published(string $table): Builder
    {
        return DB::table($table)->whereNull('deleted_at')->where('status', 'published')->where('published_at', '<=', now());
    }
}
