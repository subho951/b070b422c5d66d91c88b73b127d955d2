<x-layout :title="$pageContent->title" :description="$pageContent->description">
<x-page-intro :page-content="$pageContent"/>
<section class="section section-tight" id="founder">
    <div class="container prose">
        <p class="eyebrow">FOUNDER &amp; CEO</p>
        <h2>Subhomoy Samanta</h2>
        <p><strong>Technology Entrepreneur &amp; Software Solutions Architect</strong></p>
        <p>Subhomoy founded Howvanta Technologies in Howrah, West Bengal, to help startups, small and medium-sized businesses, and established organisations build reliable software. He has more than 15 years of hands-on experience in backend engineering, application architecture and database design.</p>
        <p>His work spans SaaS, e-commerce, enterprise software, business management systems, payment integrations and workflow automation. He works with PHP, Laravel, CodeIgniter, Python, MySQL, PostgreSQL, MongoDB and REST APIs, and has experience with AWS, Docker and cloud deployments.</p>
        <p><a class="text-link" href="{{ route('founder') }}">Visit Subhomoy’s portfolio</a></p>
        <p>He is extending that engineering work into AI-powered applications using large language models, retrieval-augmented generation, vector search and AI agents. His focus is on software that simplifies how businesses work and can grow with their needs.</p>
    </div>
</section>
@foreach($website->sections('about') as $sectionRecord)
<x-website-section :section-key="$sectionRecord->key"/>
@endforeach
<section class="section section-tight">
    <div class="container prose">
        <h2>Meet Howvanta’s engineering team</h2>
        <p>Based in Howrah, West Bengal, we build websites, web and mobile applications, and software for day-to-day operations. Our work includes a mobile networking experience for ALFA Network and a school administration platform for E School.</p>
        <p><a class="text-link" href="{{ route('portfolio') }}">View our work</a> · <a class="text-link" href="{{ route('testimonials') }}">Read client feedback</a></p>
    </div>
</section>
<section class="section section-tight">
    <div class="container audit-proof">
        <div><h2>Working from Howrah</h2><p>We work with businesses in India and welcome international remote engagements. Project hours and meeting overlap are agreed during scoping.</p></div>
        <div><h3>Technology and delivery</h3><p>Our founder’s experience includes PHP, Laravel, Python, relational databases and cloud deployment. We choose the stack to suit the work and your team.</p><a class="text-link" href="{{ route('engagement') }}">See how we work</a></div>
    </div>
</section>
<x-cta/></x-layout>
