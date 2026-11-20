<x-layout :title="$pageContent->title" :description="$pageContent->description">
    <x-page-intro :page-content="$pageContent" label="SERVICES & SOLUTIONS" class="services-intro"/>

    <section class="section section-tight services-catalog" aria-label="Available services">
        <div class="container">
            <div class="services-chooser">
                <p>Start with the work you need. We can combine application development, infrastructure, and ongoing support in one scope.</p>
                <div class="button-row"><a class="button button-small" href="{{ route('contact') }}#enquiry">Start a Project</a><a class="text-link" href="{{ route('engagement') }}">Compare engagement models</a></div>
            </div>
            <x-services :grouped="true"/>
        </div>
    </section>

    <x-cta/>
</x-layout>
