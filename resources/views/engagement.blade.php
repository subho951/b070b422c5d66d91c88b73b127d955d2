<x-layout :title="$pageContent->title" :description="$pageContent->description">
<x-page-intro :page-content="$pageContent"/>
@foreach($website->sections('engagement') as $sectionRecord)
<x-website-section :section-key="$sectionRecord->key"/>
@endforeach
<section class="section section-tight" aria-labelledby="engagement-comparison">
    <div class="container">
        <h2 id="engagement-comparison">Choose an engagement model</h2>
        <div class="engagement-grid">
            <article><h3>Fixed scope</h3><p>For a project with defined deliverables and acceptance criteria. We agree scope, milestones, and payment terms before work begins.</p></article>
            <article><h3>Dedicated developers</h3><p>For teams with an ongoing backlog and a person who can review the work. We agree roles, time-zone overlap, capacity, and reporting.</p></article>
            <article><h3>Ongoing partnership</h3><p>For a live product with maintenance and improvement needs. We agree coverage and review priorities as the system evolves.</p></article>
        </div>
    </div>
</section>
<section class="section" id="monthly-support">
    <div class="container prose">
        <p class="eyebrow">MAINTENANCE &amp; SUPPORT</p>
        <h2>Monthly software support</h2>
        <p>Keep an existing website or application running with ongoing maintenance tailored to your systems. We cover bug fixes, approved software updates, monitoring and backups according to the needs of your system.</p>
        <p>We agree on coverage, response times and responsibilities in writing for each engagement. New features, major upgrades and migrations are scoped and quoted separately.</p>
        <p><a class="button" href="{{ route('contact', ['intent' => 'support']) }}#enquiry">Ask About Support</a></p>
    </div>
</section>
<x-cta/></x-layout>
