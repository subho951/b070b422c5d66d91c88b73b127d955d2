<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="contact-click-url" content="{{ route('contact.click') }}">
    <meta name="contact-click-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subhomoy Samanta | Software Solutions Architect &amp; Founder</title>
    <meta name="description" content="Subhomoy Samanta is a software solutions architect, AI application developer and Founder &amp; CEO of Howvanta Technologies in Howrah, India.">
    <meta property="og:title" content="Subhomoy Samanta | Software Solutions Architect &amp; Founder">
    <meta property="og:description" content="Explore Subhomoy Samanta’s work in software engineering, AI applications and Howvanta Technologies.">
    <meta property="og:image" content="{{ asset('brand/howvanta-social.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('founder') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('brand/howvanta-social.png') }}">
    <link rel="canonical" href="{{ route('founder') }}">
    <link rel="icon" href="{{ $website->logo('favicon') }}">
    @vite(['resources/css/app.css', 'resources/css/frontend.css', 'resources/js/app.js'])
</head>
<body class="personal" id="personal-top">
    <a href="#personal-main" class="skip-link">Skip to content</a>
    <header class="personal-header">
        <div class="personal-wrap">
            <a class="personal-brand" href="{{ route('founder') }}">
                <span class="personal-monogram" aria-hidden="true">SS</span>
                <span class="personal-brand-name"><strong>Subhomoy Samanta</strong><small>Software Architect &amp; Entrepreneur</small></span>
            </a>
            <nav aria-label="Portfolio navigation">
                <a class="personal-nav-home" href="#personal-main">Home</a>
                <a href="#about">About</a>
                <a href="#experience">Experience</a>
                <a href="#skills">Skills</a>
                <a href="#projects">Projects</a>
                <a href="#howvanta">Howvanta</a>
                <a href="#contact">Contact</a>
            </nav>
            <a class="portfolio-button portfolio-button-outline personal-header-contact" href="#contact">Let’s Work Together</a>
        </div>
    </header>

    <main id="personal-main">
        <section class="personal-hero" aria-labelledby="personal-name">
            <div class="personal-wrap personal-hero-grid">
                <div class="personal-intro">
                    <p class="personal-eyebrow">Technology Entrepreneur</p>
                    <h1 id="personal-name">Hi, I’m<br>Subhomoy Samanta.</h1>
                    <p class="personal-role">Software Solutions Architect · AI Application Developer · Founder &amp; CEO of Howvanta Technologies</p>
                    <p>I build web applications, SaaS platforms and AI-powered software that help businesses simplify how they work.</p>
                    <div class="personal-actions">
                        <a class="portfolio-button" href="#contact">Work With Me</a>
                        <a class="portfolio-button portfolio-button-outline" href="#projects">View My Projects</a>
                    </div>
                    <dl class="personal-facts">
                        <div><dt>15+ years</dt><dd>Software engineering</dd></div>
                        <div><dt>International</dt><dd>Based in Howrah, India</dd></div>
                        <div><dt>Founder &amp; CEO</dt><dd>Howvanta Technologies</dd></div>
                    </dl>
                </div>
                <div class="personal-portrait">
                    <img src="{{ asset('brand/subhomoy-portfolio-reference.png') }}" alt="Subhomoy Samanta in an office" fetchpriority="high">
                </div>
            </div>
        </section>

        <section id="howvanta" class="personal-wrap personal-company" aria-labelledby="howvanta-name">
            <div class="personal-company-brand">
                <img src="{{ asset('brand/HV-symbol-light.svg') }}" width="42" height="42" alt="">
                <div><h2 id="howvanta-name">Howvanta Technologies</h2><p>Founder &amp; CEO</p></div>
            </div>
            <p>We build custom software, web applications and business automation tools for organisations in India and international markets.</p>
            <a class="personal-company-tagline" href="{{ route('home') }}" aria-label="Explore Howvanta Technologies">Engineering What’s Next.</a>
        </section>

        <section class="personal-wrap personal-expertise" aria-labelledby="skills">
            <h2 id="skills">Core expertise</h2>
            <dl class="personal-skills">
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="web"/></span>Web development</dt><dd>PHP, Laravel, CodeIgniter, JavaScript</dd></div>
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="python"/></span>Python &amp; automation</dt><dd>Python, scripts, workflow automation</dd></div>
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="database"/></span>Database engineering</dt><dd>MySQL, PostgreSQL, MongoDB</dd></div>
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="ai"/></span>AI &amp; LLMs</dt><dd>RAG, vector search, AI agents</dd></div>
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="cloud"/></span>Cloud &amp; infrastructure</dt><dd>AWS, Docker, VPS and cloud deployment</dd></div>
                <div><dt><span class="personal-icon-tile"><x-personal-icon name="products"/></span>Products &amp; integrations</dt><dd>SaaS, ERP, CRM, REST APIs, payment gateways</dd></div>
            </dl>
        </section>

        <section class="personal-wrap personal-section personal-split personal-about" aria-labelledby="about">
            <div>
                <p class="personal-eyebrow">About Me</p>
                <h2 id="about">Software shaped around the problem</h2>
                <p>I’m a software engineer and entrepreneur based in Howrah, West Bengal. I founded Howvanta Technologies to help startups, growing businesses and established organisations turn their requirements into reliable software.</p>
                <p>I start by understanding the people and workflows behind a project, then design, build and support a solution that fits their day-to-day work.</p>
            </div>
            <div class="personal-note">
                <span class="personal-icon-tile"><x-personal-icon name="globe"/></span>
                <h3>Working internationally</h3>
                <p>I’m available for remote engagements across India, the US, the UK, the UAE and Kuwait.</p>
                <p><strong>Engineering What’s Next.</strong></p>
            </div>
        </section>

        <section class="personal-tint" aria-labelledby="experience">
            <div class="personal-wrap personal-section">
                <p class="personal-eyebrow">Experience</p>
                <h2 id="experience">15+ years in software engineering</h2>
                <div class="personal-detail-grid">
                    <article><span class="personal-icon-tile"><x-personal-icon name="database"/></span><h3>Backend and architecture</h3><p>Application architecture, database design, API integrations and deployment for software that needs to grow.</p></article>
                    <article><span class="personal-icon-tile"><x-personal-icon name="products"/></span><h3>Business applications</h3><p>SaaS platforms, e-commerce, management systems, payments and workflow automation.</p></article>
                    <article><span class="personal-icon-tile"><x-personal-icon name="ai"/></span><h3>AI applications</h3><p>Building on that foundation with Python, large language models, retrieval-augmented generation, vector search and AI agents.</p></article>
                </div>
            </div>
        </section>

        <section class="personal-work" aria-labelledby="projects">
            <div class="personal-wrap personal-section">
                <div class="personal-section-heading">
                    <div><p class="personal-eyebrow">Selected Work</p><h2 id="projects">View My Projects</h2></div>
                    <a class="portfolio-button portfolio-button-outline" href="{{ route('portfolio') }}">Browse the Howvanta portfolio</a>
                </div>
                @if($projects->isNotEmpty())
                    <div class="personal-projects">
                        @foreach($projects as $project)
                            <article class="personal-project">
                                <x-media-image :id="$project->featured_image_id" image-class="personal-project-image"/>
                                <div class="personal-project-copy">
                                    <p class="personal-eyebrow">{{ $project->category }}</p>
                                    <h3>{{ $project->title }}</h3>
                                    <p>{{ $project->description }}</p>
                                    <a class="portfolio-button portfolio-button-outline" href="{{ route('portfolio.show', $project->slug) }}">View Case Study<span class="sr-only"> for {{ $project->title }}</span></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="personal-wrap personal-section" aria-labelledby="contact">
            <div class="personal-contact">
                <div><h2 id="contact">Contact Me</h2><p>Tell me what you’re building or which workflow you’d like to improve.</p></div>
                <div class="personal-actions">
                    <a class="portfolio-button" href="{{ route('contact', ['intent' => 'consultation']) }}#enquiry">Request a Consultation</a>
                    <a class="portfolio-button portfolio-button-outline" href="{{ config('howvanta.contact_links.WhatsApp Business') }}" data-contact-action="whatsapp">WhatsApp</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="personal-footer">
        <div class="personal-wrap"><span>Subhomoy Samanta · Howrah, West Bengal, India</span><nav aria-label="Footer navigation"><a href="#personal-top">Back to top</a><a href="{{ route('privacy') }}">Privacy policy</a></nav></div>
    </footer>
</body>
</html>
