<x-layout :title="$pageContent->seo_title ?: 'Software, Web & Mobile Case Studies'" :description="$pageContent->seo_description ?: $pageContent->description">
    <x-page-intro :page-content="$pageContent" label="SELECTED PROJECTS" class="portfolio-intro"/>
    <section class="section section-tight portfolio-listing">
        <div class="container">
            <form class="portfolio-filter" method="get" action="{{ route('portfolio') }}">
                <div><label for="industry">Filter by industry</label>
                    <select id="industry" name="industry">
                        <option value="">All industries</option>
                        @foreach($industries as $industry)<option value="{{ $industry }}" @selected($activeIndustry === $industry)>{{ $industry }}</option>@endforeach
                    </select>
                </div>
                <button class="button button-small" type="submit">Apply filter</button>
                @if($activeIndustry)<a class="text-link" href="{{ route('portfolio') }}">Clear filter</a>@endif
                <p>{{ $projectCount }} {{ \Illuminate\Support\Str::plural('project', $projectCount) }}</p>
            </form>
            @if($featuredProjects->isNotEmpty())
                <h2>Featured work</h2>
                <x-projects :projects="$featuredProjects"/>
            @endif
            @if($otherProjects->isNotEmpty())
                <h2 class="portfolio-more-heading">{{ $featuredProjects->isNotEmpty() ? 'More projects' : 'Projects' }}</h2>
                @foreach($otherProjects as $industry => $projects)
                    <h3 class="portfolio-industry-heading">{{ $industry }}</h3>
                    <x-projects :projects="$projects"/>
                @endforeach
            @elseif($featuredProjects->isEmpty())
                <x-projects :projects="collect()"/>
            @endif
        </div>
    </section>
    <x-cta/>
</x-layout>
