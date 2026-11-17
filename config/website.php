<?php

$website = [
    'branding' => [
        'site_name' => 'Howvanta Technologies',
        'tagline' => 'Engineering What\'s Next.',
        'header_button_label' => 'Let\'s talk',
        'header_button_url' => '/contact',
        'menu_label' => 'Menu',
        'footer_location_note' => 'Building for businesses worldwide.',
        'footer_explore_heading' => 'Explore Howvanta',
        'footer_services_heading' => 'What we build',
        'footer_contact_heading' => 'Start a conversation',
        'footer_contact_label' => 'Let\'s build what\'s next.',
        'footer_contact_url' => '/contact',
        'copyright' => 'Howvanta Technologies. All rights reserved.',
        'service_button_label' => 'Discuss your requirements',
        'service_back_label' => 'All services',
        'service_eyebrow' => 'HOWVANTA / ENGINEERING CAPABILITIES',
        'project_back_label' => 'All work',
        'portfolio_concept_stamp' => 'CONCEPT EXPLORATION',
        'portfolio_project_stamp' => 'SELECTED PROJECT',
        'portfolio_demo_label' => 'DEMONSTRATION',
        'service_explore_label' => 'Explore service',
    ],
    'sections' => [
        'maintenance.screen' => [
            'label' => 'Maintenance page', 'scope' => 'maintenance', 'variant' => 'screen',
            'fields' => [
                'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'A LITTLE MAINTENANCE. MORE POSSIBILITIES.'],
                'title' => ['label' => 'Headline', 'type' => 'textarea', 'default' => 'Building what comes next.'],
                'button_label' => ['label' => 'Email button label', 'type' => 'text', 'default' => 'Get in touch'],
                'image_id' => ['label' => 'Image', 'type' => 'image', 'default' => null],
            ],
        ],
        'shared.empty_states' => [
            'label' => 'Collection empty-state copy', 'scope' => 'shared', 'variant' => 'labels',
            'fields' => [
                'services_message' => ['label' => 'No services message', 'type' => 'textarea', 'default' => 'Our service catalogue is being updated.'],
                'services_link_label' => ['label' => 'Contact link label', 'type' => 'text', 'default' => 'Tell us what you need.'],
                'portfolio_message' => ['label' => 'No projects message', 'type' => 'textarea', 'default' => 'Our portfolio is being updated. Let us discuss what we can build together.'],
            ],
        ],

        'home.hero' => [
            'label' => 'Hero details & buttons',
            'scope' => 'home',
            'variant' => 'hero',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'YOUR SOFTWARE ENGINEERING PARTNER',
                ],
                'note' => [
                    'label' => 'Note',
                    'type' => 'textarea',
                    'default' => 'Technology that works. Explained clearly. Delivered reliably.',
                ],
                'bottom_text' => [
                    'label' => 'Bottom text',
                    'type' => 'text',
                    'default' => 'BASED IN HOWRAH, INDIA. BUILDING FOR THE WORLD.',
                ],
                'discover_label' => [
                    'label' => 'Discover label',
                    'type' => 'text',
                    'default' => 'Discover the possibilities',
                ],
                'discover_url' => [
                    'label' => 'Discover url',
                    'type' => 'link',
                    'default' => '/#expertise',
                ],
                'graphic_label' => [
                    'label' => 'Graphic label',
                    'type' => 'text',
                    'default' => 'DESIGN / BUILD / EVOLVE',
                ],
                'graphic_caption' => [
                    'label' => 'Graphic caption',
                    'type' => 'text',
                    'default' => 'STRUCTURE. CONNECTION. FORWARD MOVEMENT.',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Let\'s build what\'s next',
                        'text' => '',
                        'url' => '/contact',
                    ], [
                        'title' => 'Explore services',
                        'text' => '',
                        'url' => '/services',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'home.capabilities' => [
            'label' => 'Capabilities strip',
            'scope' => 'home',
            'variant' => 'strip',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'BUILT AROUND YOUR BUSINESS',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Software',
                        'text' => '',
                        'url' => '',
                    ], [
                        'title' => 'Web & mobile',
                        'text' => '',
                        'url' => '',
                    ], [
                        'title' => 'AI',
                        'text' => '',
                        'url' => '',
                    ], [
                        'title' => 'Cloud',
                        'text' => '',
                        'url' => '',
                    ], [
                        'title' => 'SaaS',
                        'text' => '',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'home.expertise' => [
            'label' => 'Expertise',
            'scope' => 'home',
            'variant' => 'services',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'WHAT WE DO',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Real challenges.
Practical solutions.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'One partner across your product\'s lifecycle. The right technology, chosen for the problem you need to solve.',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'home.why' => [
            'label' => 'Why Howvanta',
            'scope' => 'home',
            'variant' => 'principles',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'WHY HOWVANTA',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Clear thinking.
Considered craft.
Lasting partnership.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Good software starts with understanding your business. We combine the clarity of a consultant with the craft of an engineering team.',
                ],
                'button_label' => [
                    'label' => 'Button label',
                    'type' => 'text',
                    'default' => 'Meet Howvanta',
                ],
                'button_url' => [
                    'label' => 'Button url',
                    'type' => 'link',
                    'default' => '/about',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Your goals come first',
                        'text' => 'We listen before we build. Your users, workflows and ambitions shape the solution.',
                        'url' => '',
                    ], [
                        'title' => 'Progress you can see',
                        'text' => 'Clear milestones, regular demonstrations and decisions explained in plain language.',
                        'url' => '',
                    ], [
                        'title' => 'Built for what comes after',
                        'text' => 'Maintainable code, thoughtful architecture and support beyond the first release.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'home.portfolio' => [
            'label' => 'Portfolio introduction',
            'scope' => 'home',
            'variant' => 'projects',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'SELECTED PROJECTS',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'See what we have
built for our clients.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Explore practical digital products built to connect people, simplify operations and support business growth.',
                ],
                'button_label' => [
                    'label' => 'Button label',
                    'type' => 'text',
                    'default' => 'Explore the portfolio',
                ],
                'button_url' => [
                    'label' => 'Button url',
                    'type' => 'link',
                    'default' => '/portfolio',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'home.process' => [
            'label' => 'Process steps',
            'scope' => 'home',
            'variant' => 'process',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'HOW WE WORK',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'A clear path forward.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Six considered steps. Shared progress. From your first idea to the next improvement.',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Discover',
                        'text' => 'Goals, users and risks.',
                        'url' => '',
                    ], [
                        'title' => 'Define',
                        'text' => 'Scope, milestones and success criteria.',
                        'url' => '',
                    ], [
                        'title' => 'Design',
                        'text' => 'Experience and architecture.',
                        'url' => '',
                    ], [
                        'title' => 'Build',
                        'text' => 'Working increments and regular demos.',
                        'url' => '',
                    ], [
                        'title' => 'Validate',
                        'text' => 'Test, refine and prepare for release.',
                        'url' => '',
                    ], [
                        'title' => 'Improve',
                        'text' => 'Feedback guides what comes next.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'about.values' => [
            'label' => 'About philosophy & values',
            'scope' => 'about',
            'variant' => 'principles',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'OUR POINT OF VIEW',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Technology should
make things possible.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'We combine business understanding with practical engineering. Whether you\'re shaping a new product or improving an established system, our focus is the same: software that serves its users and lasts beyond its launch.',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Clarity',
                        'text' => 'Honest conversations, visible progress, and decisions you can understand.',
                        'url' => '',
                    ], [
                        'title' => 'Craft',
                        'text' => 'Care for the details, from accessible interfaces to maintainable architecture.',
                        'url' => '',
                    ], [
                        'title' => 'Partnership',
                        'text' => 'Shared ownership of the outcome and respect for your time and ambitions.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'engagement.models' => [
            'label' => 'Engagement models',
            'scope' => 'engagement',
            'variant' => 'cards',
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'A partnership that fits.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => '',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Fixed-scope projects',
                        'text' => 'Best when your requirements are clear. We agree deliverables, milestones, acceptance criteria, and a project price after discovery.',
                        'url' => '',
                    ], [
                        'title' => 'Dedicated developers',
                        'text' => 'Best when you need ongoing engineering capacity. Developers collaborate with your team through an agreed working schedule and delivery rhythm.',
                        'url' => '',
                    ], [
                        'title' => 'Ongoing partnerships',
                        'text' => 'Best for evolving products. We plan priorities together and agree capacity for development, maintenance, and technical improvements.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'shared.cta' => [
            'label' => 'Site-wide call to action',
            'scope' => 'shared',
            'variant' => 'cta',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'LET\'S BUILD WHAT\'S NEXT.',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Your next chapter
starts with a conversation.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'A product idea. A workflow to improve. A practical next step.',
                ],
                'button_label' => [
                    'label' => 'Button label',
                    'type' => 'text',
                    'default' => 'Tell us what you have in mind',
                ],
                'button_url' => [
                    'label' => 'Button url',
                    'type' => 'link',
                    'default' => '/contact',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'service.approach' => [
            'label' => 'Service detail approach',
            'scope' => 'service',
            'variant' => 'principles',
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Grounded in your goals.
Built to move you forward.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'We work with you to understand the problem, choose the right tools, and turn a clear plan into dependable software.',
                ],
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Discovery & technical direction',
                        'text' => 'Define the scope, constraints, architecture, and delivery milestones together.',
                        'url' => '',
                    ], [
                        'title' => 'Iterative delivery',
                        'text' => 'Review working software regularly, with clear progress and room for feedback.',
                        'url' => '',
                    ], [
                        'title' => 'Quality & continuity',
                        'text' => 'Testing, documentation, deployment planning, and an agreed support handover.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'contact.aside' => [
            'label' => 'Contact introduction & labels',
            'scope' => 'contact',
            'variant' => 'aside',
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Good things start
with a hello.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Tell us about your business and what you have in mind. We\'ll review your enquiry and discuss a practical next step.',
                ],
                'location_label' => [
                    'label' => 'Location label',
                    'type' => 'text',
                    'default' => 'BASED IN',
                ],
                'email_label' => [
                    'label' => 'Email label',
                    'type' => 'text',
                    'default' => 'EMAIL US',
                ],
                'phone_label' => [
                    'label' => 'Phone label',
                    'type' => 'text',
                    'default' => 'CALL / WHATSAPP',
                ],
                'note' => [
                    'label' => 'Note',
                    'type' => 'textarea',
                    'default' => 'Your information stays private and is used to respond to your enquiry.',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'testimonials.empty' => [
            'label' => 'Testimonials empty state',
            'scope' => 'testimonials',
            'variant' => 'empty',
            'fields' => [
                'eyebrow' => [
                    'label' => 'Eyebrow',
                    'type' => 'text',
                    'default' => 'PARTNERSHIPS BUILT ON CLARITY',
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'default' => 'Every story starts
with a conversation.',
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'default' => 'We\'ll share client feedback here when it is approved for publication. In the meantime, explore our approach and tell us what you have in mind.',
                ],
                'button_label' => [
                    'label' => 'Button label',
                    'type' => 'text',
                    'default' => 'How we work',
                ],
                'button_url' => [
                    'label' => 'Button url',
                    'type' => 'link',
                    'default' => '/engagement',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'project.labels' => [
            'label' => 'Portfolio detail labels & fallback',
            'scope' => 'portfolio',
            'variant' => 'labels',
            'fields' => [
                'demonstration_label' => [
                    'label' => 'Demonstration label',
                    'type' => 'text',
                    'default' => 'ILLUSTRATIVE CONCEPT / DEMONSTRATION',
                ],
                'project_label' => [
                    'label' => 'Project label',
                    'type' => 'text',
                    'default' => 'PORTFOLIO / SELECTED PROJECT',
                ],
                'disclaimer' => [
                    'label' => 'Disclaimer',
                    'type' => 'textarea',
                    'default' => 'This is a demonstration concept, not a completed client engagement. No business results are claimed.',
                ],
                'challenge_heading' => [
                    'label' => 'Challenge heading',
                    'type' => 'text',
                    'default' => 'The challenge',
                ],
                'solution_heading' => [
                    'label' => 'Solution heading',
                    'type' => 'text',
                    'default' => 'The approach',
                ],
                'outcome_heading' => [
                    'label' => 'Outcome heading',
                    'type' => 'text',
                    'default' => 'The outcome',
                ],
                'fallback_title' => [
                    'label' => 'Fallback title',
                    'type' => 'text',
                    'default' => 'Start with a clear understanding.',
                ],
                'fallback_description' => [
                    'label' => 'Fallback description',
                    'type' => 'text',
                    'default' => 'Explore the users, workflows and technical requirements. Prototype the core journeys, validate the approach and build in considered increments.',
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'shared.header_nav' => [
            'label' => 'Header navigation',
            'scope' => 'navigation',
            'variant' => 'menu',
            'fields' => [
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Home',
                        'text' => '',
                        'url' => '/',
                    ], [
                        'title' => 'Services',
                        'text' => '',
                        'url' => '/services',
                    ], [
                        'title' => 'Portfolio',
                        'text' => '',
                        'url' => '/portfolio',
                    ], [
                        'title' => 'About us',
                        'text' => '',
                        'url' => '/about',
                    ], [
                        'title' => 'Testimonials',
                        'text' => '',
                        'url' => '/testimonials',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'shared.footer_nav' => [
            'label' => 'Footer navigation',
            'scope' => 'navigation',
            'variant' => 'menu',
            'fields' => [
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'About us',
                        'text' => '',
                        'url' => '/about',
                    ], [
                        'title' => 'Services & solutions',
                        'text' => '',
                        'url' => '/services',
                    ], [
                        'title' => 'Portfolio',
                        'text' => '',
                        'url' => '/portfolio',
                    ], [
                        'title' => 'Testimonials',
                        'text' => '',
                        'url' => '/testimonials',
                    ], [
                        'title' => 'How we work',
                        'text' => '',
                        'url' => '/engagement',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'shared.legal_nav' => [
            'label' => 'Footer legal links',
            'scope' => 'navigation',
            'variant' => 'menu',
            'fields' => [
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Privacy',
                        'text' => '',
                        'url' => '/privacy',
                    ], [
                        'title' => 'Terms',
                        'text' => '',
                        'url' => '/terms',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'privacy.body' => [
            'label' => 'Privacy content',
            'scope' => 'privacy',
            'variant' => 'prose',
            'fields' => [
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Information you share',
                        'text' => 'When you submit a project enquiry, Howvanta Technologies receives your name, business email, company, country, phone number, project preferences, and description. Please do not include passwords, financial account details, or other sensitive information in your enquiry.',
                        'url' => '',
                    ], [
                        'title' => 'How we use it',
                        'text' => 'We use this information to respond to you, understand your requirements, and manage the resulting business conversation. Access is restricted to authorized personnel. We do not sell enquiry information.',
                        'url' => '',
                    ], [
                        'title' => 'Cookies and service providers',
                        'text' => 'This website uses essential session and security cookies for forms and team authentication. Hosting and email providers may process data to operate the service. When enabled, Google reCAPTCHA v3 assesses browser interactions to help protect forms and account access from automated abuse. Google may process technical information for this security check. See the Google Privacy Policy (https://policies.google.com/privacy) and Terms of Service (https://policies.google.com/terms).',
                        'url' => '',
                    ], [
                        'title' => 'Retention and your choices',
                        'text' => 'We retain enquiry information while needed for the business conversation and applicable recordkeeping obligations. You may request access, correction, or deletion by contacting us through the contact page. Applicable legal obligations may affect deletion requests.',
                        'url' => '',
                    ], [
                        'title' => 'Contact',
                        'text' => 'Howvanta Technologies, Howrah, West Bengal, India. Use our contact page for privacy questions.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
        'terms.body' => [
            'label' => 'Terms content',
            'scope' => 'terms',
            'variant' => 'prose',
            'fields' => [
                'items' => [
                    'label' => 'Items',
                    'type' => 'items',
                    'default' => [[
                        'title' => 'Using this website',
                        'text' => 'This website introduces Howvanta Technologies and our services. Use it lawfully and do not attempt unauthorized access, interfere with the service, or submit malicious content.',
                        'url' => '',
                    ], [
                        'title' => 'Project engagements',
                        'text' => 'Submitting an enquiry does not create a service agreement. Scope, pricing, delivery, confidentiality, intellectual property, and support obligations are governed by a separately agreed written contract.',
                        'url' => '',
                    ], [
                        'title' => 'Demonstration content',
                        'text' => 'Portfolio concepts marked as demonstrations are illustrative. They do not represent client engagements, endorsements, or achieved business results.',
                        'url' => '',
                    ], [
                        'title' => 'Website availability',
                        'text' => 'We aim to keep information useful and accurate but may update it or interrupt availability. Nothing on this website overrides rights or obligations that apply under applicable law.',
                        'url' => '',
                    ], [
                        'title' => 'Questions',
                        'text' => 'Please contact us about these terms or a proposed engagement.',
                        'url' => '',
                    ]],
                ],
                'image_id' => [
                    'label' => 'Image id',
                    'type' => 'image',
                    'default' => null,
                ],
            ],
        ],
    ],
    'page_labels' => [
        'home' => 'YOUR SOFTWARE ENGINEERING PARTNER',
        'about' => 'ABOUT HOWVANTA',
        'services' => 'SERVICES & SOLUTIONS',
        'portfolio' => 'SELECTED PROJECTS',
        'testimonials' => 'OUR PARTNERSHIPS',
        'contact' => 'START A CONVERSATION',
        'engagement' => 'FLEXIBLE BY DESIGN',
        'privacy' => 'YOUR INFORMATION',
        'terms' => 'WEBSITE TERMS',
    ],
];

$companyContent = require __DIR__.'/company-content.php';
$proseSection = static fn (array $section): array => [
    'label' => $section['label'], 'scope' => $section['scope'], 'variant' => 'prose',
    'fields' => [
        'items' => ['label' => 'Items', 'type' => 'items', 'default' => $section['items']],
        'image_id' => ['label' => 'Image id', 'type' => 'image', 'default' => null],
    ],
];
$sections = [];
foreach ($website['sections'] as $key => $definition) {
    if ($key === 'about.values') {
        $sections['about.story'] = $proseSection($companyContent['sections']['about.story']);
    }
    $sections[$key] = isset($companyContent['sections'][$key]) ? $proseSection($companyContent['sections'][$key]) : $definition;
}
$website['sections'] = $sections;

return $website;
