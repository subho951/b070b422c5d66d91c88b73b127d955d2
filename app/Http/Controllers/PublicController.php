<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PublicController extends Controller
{
    public function page(string $page = 'home'): View
    {
        abort_unless(in_array($page, ['home', 'about', 'services', 'portfolio', 'testimonials', 'contact', 'engagement', 'privacy', 'terms'], true), 404);
        $pageContent = $this->published('pages')->where('slug', $page)->first();
        abort_unless($pageContent, 404);

        $pageData = match ($page) {
            'contact' => ['enquiryServices' => $this->published('services')->orderBy('sort_order')->orderBy('id')->get(['slug', 'title'])],
            'portfolio' => $this->portfolioData(),
            default => [],
        };

        return view($page, compact('pageContent') + $pageData);
    }

    public function founder(): View
    {
        $projects = $this->published('portfolio_projects')
            ->whereIn('slug', ['alfa-network', 'e-school'])
            ->where('is_demonstration', false)
            ->orderBy('sort_order')
            ->get();

        return view('founder', compact('projects'));
    }

    public function service(string $slug): View
    {
        $record = $this->published('services')->where('slug', $slug)->first();
        abort_unless($record, 404);

        $links = config('portfolio-catalog.service_links');
        $relatedProjects = $this->published('portfolio_projects')->where('is_demonstration', false)
            ->whereIn('slug', array_keys($links, $slug, true))
            ->orderByDesc('is_exclusive')->orderBy('sort_order')->orderBy('id')->limit(3)->get();

        return view('service', [
            'title' => $record->title,
            'description' => $record->description,
            'record' => $record,
            'guide' => config('service-guides.'.$slug),
            'relatedProjects' => $relatedProjects,
        ]);
    }

    public function project(string $slug): View|RedirectResponse
    {
        $record = $this->published('portfolio_projects')->where('slug', $slug)->first();
        $replacement = config('portfolio-catalog.legacy')[$slug][0] ?? null;
        if (! $record && $replacement && $this->published('portfolio_projects')->where('slug', $replacement)->exists()) {
            return redirect()->route('portfolio.show', $replacement, 301);
        }
        abort_unless($record, 404);

        $serviceSlug = config('portfolio-catalog.service_links.'.$slug);
        $relatedService = $serviceSlug ? $this->published('services')->where('slug', $serviceSlug)->first(['slug', 'title']) : null;
        $projectLinks = $this->published('portfolio_projects')->orderBy('sort_order')->orderBy('id')->get(['id', 'slug', 'title']);
        $position = $projectLinks->search(fn (object $project): bool => $project->id === $record->id);

        return view('project', [
            'title' => $record->title, 'record' => $record, 'relatedService' => $relatedService,
            'previousProject' => $projectLinks->get($position - 1),
            'nextProject' => $projectLinks->get($position + 1),
        ]);
    }

    /** @return array<string, mixed> */
    private function portfolioData(): array
    {
        $industries = [];
        foreach (config('portfolio-catalog.legacy') as [$slug, , $industry]) {
            $industries[$slug] = $industry;
        }

        $projects = $this->published('portfolio_projects')->orderBy('sort_order')->orderBy('id')->get();
        $industryFor = static fn (object $project): string => $industries[$project->slug] ?? ($project->category ?: 'Other');
        $filters = $projects->map($industryFor)->unique()->sort()->values();
        $industry = request()->query('industry');
        $activeIndustry = is_string($industry) && $filters->containsStrict($industry) ? $industry : null;
        $filtered = $activeIndustry === null ? $projects : $projects->filter(fn (object $project): bool => $industryFor($project) === $activeIndustry);
        $bySlug = $filtered->keyBy('slug');
        $featured = collect(config('portfolio-catalog.featured'))->map(fn (string $slug): ?object => $bySlug->get($slug))->filter()->values();

        return [
            'industries' => $filters, 'activeIndustry' => $activeIndustry, 'projectCount' => $filtered->count(),
            'featuredProjects' => $featured, 'otherProjects' => $filtered->whereNotIn('slug', $featured->pluck('slug'))->groupBy($industryFor)->sortKeys(),
        ];
    }

    public function sitemap(): Response
    {
        $urls = collect([route('founder')]);
        foreach ($this->published('pages')->pluck('slug')->filter(fn (string $slug): bool => Route::has($slug)) as $slug) {
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
