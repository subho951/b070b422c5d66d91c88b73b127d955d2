<?php

return [
    'custom-software' => [
        'seo_title' => 'Custom Software Development',
        'outcome' => 'Bring records, approvals and reporting together in a system built around your team’s work.',
        'uses' => ['Replace disconnected spreadsheets with an internal management system.', 'Connect inventory, billing, reporting or approval workflows.'],
        'deliverables' => ['Workflow map and scope', 'Application modules and integrations', 'Deployment and handover documentation'],
        'technology' => 'PHP, Laravel, MySQL or PostgreSQL and REST APIs, selected for the project.',
        'process' => ['Map current workflows and approvals.', 'Agree on data migration and integration boundaries.', 'Deliver a working module first and validate the remaining workflows with your team.'],
        'faq' => [
            'Can you migrate our existing records?' => 'We can assess the source data, agree what to transfer and rehearse the migration before cutover.',
            'Can it connect to our existing tools?' => 'We assess the available APIs and permissions before committing to any integration.',
        ],
    ],
    'web-applications' => [
        'seo_title' => 'Web Design and Application Development',
        'outcome' => 'Give customers a clear website or app for finding information, making purchases or completing tasks.',
        'uses' => ['Launch a public business website or e-commerce storefront.', 'Build a portal for accounts, requests or learning resources.'],
        'deliverables' => ['Responsive design', 'Pages, content tools or app workflows', 'Testing and launch handover'],
        'technology' => 'HTML, CSS, JavaScript, PHP and Laravel where a custom backend is needed.',
        'process' => ['Decide whether the scope is a website or a web application.', 'Prototype the key pages or customer journeys.', 'Implement, test on supported devices and agree how content will be maintained.'],
        'faq' => [
            'Can we edit content ourselves?' => 'We agree which areas need editing and include a suitable content-management approach in the scope.',
            'Can you replace our current site?' => 'We can review current pages, content, redirects and integrations before planning the release.',
        ],
    ],
    'hosting-domains' => [
        'outcome' => 'Keep your website, domain and email organised with managed setup and agreed maintenance.',
        'uses' => ['Move a website or email setup to new hosting.', 'Connect a domain, configure DNS and enable HTTPS.'],
        'deliverables' => ['Hosting and domain setup', 'DNS and certificate checks', 'Migration and handover notes'],
        'technology' => 'DNS, TLS, web hosting, VPS and provider control panels appropriate to the site.',
        'process' => ['Inventory the site, domains and email.', 'Agree on hosting, backup and migration responsibilities.', 'Check DNS, HTTPS and email before handover.'],
        'faq' => [
            'Can you help transfer a domain?' => 'Yes, provided the current account holder can approve the transfer and the domain is eligible.',
            'Are backups included?' => 'We agree backup coverage, frequency and restore responsibilities in the hosting scope.',
        ],
    ],
    'mobile-apps' => [
        'seo_title' => 'Mobile App Development',
        'outcome' => 'Give customers or field teams a focused mobile experience for tasks they need on the move.',
        'uses' => ['Build an app for member profiles, events or account access.', 'Create a mobile workflow for field staff and operational updates.'],
        'deliverables' => ['Mobile journey and screen designs', 'App and agreed backend integrations', 'Device testing and release preparation'],
        'technology' => 'Android and cross-platform development. Platform and framework choices, including iOS, are confirmed during scoping.',
        'process' => ['Agree on platforms and device requirements.', 'Prototype the main workflows and permissions.', 'Test on supported devices and prepare the agreed release.'],
        'faq' => [
            'Can the app support iOS as well as Android?' => 'ALFA Network has Android and iOS download links. We confirm iOS scope, delivery and testing for each engagement.',
            'Can you help with app store submission?' => 'Submission support can be scoped, with developer accounts and approvals agreed upfront.',
        ],
    ],
    'ai-applications' => [
        'outcome' => 'Reduce repetitive work with a focused AI pilot that your team can test against real examples.',
        'uses' => ['Help staff find answers across approved policy or product documents.', 'Route routine enquiries or extract information for staff to review.'],
        'deliverables' => ['Pilot scope and evaluation examples', 'Working integration or prototype', 'Review of errors, safeguards and handover needs'],
        'technology' => 'Python, LLM integrations, retrieval-augmented generation and vector search where appropriate.',
        'process' => ['Choose a single workflow and agree what success looks like.', 'Confirm data access and build a small pilot.', 'Evaluate responses and agree what needs human review before expansion.'],
        'faq' => [
            'Can we use sensitive documents?' => 'Discuss permissions, provider terms and data handling first. The pilot can start with approved or sample documents.',
            'How do you check whether it works?' => 'We agree evaluation examples upfront and review both useful outputs and failure cases with your team.',
        ],
    ],
    'cloud-engineering' => [
        'outcome' => 'Make releases and infrastructure easier to operate, monitor and recover.',
        'uses' => ['Plan a cloud migration or safer release process.', 'Review deployment, monitoring and backup responsibilities.'],
        'deliverables' => ['Infrastructure and deployment plan', 'Agreed migration or pipeline work', 'Operational handover and recovery notes'],
        'technology' => 'AWS, Docker and VPS environments where appropriate, with platform choices agreed during discovery.',
        'process' => ['Review the current environment and access.', 'Plan deployment, cutover and rollback.', 'Validate the release, monitoring and operational handover.'],
        'faq' => [
            'How is this different from hosting setup?' => 'Hosting setup covers the site, domain and runtime. Cloud engineering focuses on application infrastructure, releases and operations.',
            'Can you work in our cloud accounts?' => 'We can discuss client-owned accounts and scoped access during discovery.',
        ],
    ],
    'saas-products' => [
        'outcome' => 'Launch a SaaS product around the workflows your first customers need.',
        'uses' => ['Build an MVP with onboarding and an administrator workspace.', 'Add subscriptions, tenant boundaries or operational reporting to an existing product.'],
        'deliverables' => ['MVP scope and user journeys', 'Application and agreed account or billing integrations', 'Release and ownership handover'],
        'technology' => 'Laravel, PHP, relational databases and REST APIs where appropriate. Billing and identity providers are chosen with you.',
        'process' => ['Identify the first customer and essential workflow.', 'Design onboarding, tenant boundaries and billing.', 'Ship and test an MVP before extending the feature set.'],
        'faq' => [
            'How do you scope an MVP?' => 'We choose the smallest feature set that lets the intended customer complete a real workflow.',
            'Who owns the product and accounts?' => 'Ownership, code handover, accounts and any licensed components are agreed in writing.',
        ],
    ],
    'software-testing' => [
        'outcome' => 'Find release risks early and give your developers clear issues they can reproduce.',
        'uses' => ['Check critical customer journeys before a release.', 'Add regression or compatibility checks around a changing application.'],
        'deliverables' => ['Test scope and scenarios', 'Defect report with reproduction steps', 'Retest or release summary'],
        'technology' => 'Browser and device testing with project-specific automation where useful. Tools are agreed after reviewing your stack.',
        'process' => ['Agree on supported browsers, devices and business-critical flows.', 'Run the agreed checks and document findings.', 'Retest fixes and summarise remaining risks.'],
        'faq' => [
            'Can this be a standalone engagement?' => 'Yes. We agree scope, access, environments and reporting before work begins.',
            'Can you automate regression checks?' => 'We can assess which stable workflows are worth automating and agree how the suite will be maintained.',
        ],
    ],
    'dedicated-teams' => [
        'outcome' => 'Add engineering capacity for a backlog or existing product with a clear way to review progress.',
        'uses' => ['Extend your team for feature development or maintenance.', 'Bring in support for a backlog that needs regular delivery.'],
        'deliverables' => ['Onboarding and access plan', 'Agreed backlog and reporting rhythm', 'Reviewed work and documented handover'],
        'technology' => 'We review your existing stack and match the required PHP, Laravel, Python or integration skills to the engagement.',
        'process' => ['Review your backlog, stack and access needs.', 'Agree responsibilities, time-zone overlap and review rhythm.', 'Start with a defined work item and review progress together.'],
        'faq' => [
            'How is the team sized?' => 'We agree roles and availability around your backlog, existing team and review capacity.',
            'Can we work across time zones?' => 'Yes. We agree overlap and communication expectations before onboarding.',
        ],
    ],
];
