<?php

return [
    'email' => env('COMPANY_EMAIL', 'info@howvanta.com'),
    'notification_email' => env('ENQUIRY_NOTIFICATION_EMAIL'),
    'services' => [
        'custom-software' => ['Custom Software', 'Operations & ERP, finance, HR and industry software built around your business.'],
        'web-applications' => ['Web Design & Development', 'Business websites, e-commerce, learning platforms, redesign and ongoing maintenance.'],
        'hosting-domains' => ['Web Hosting & Domain', 'Web, VPS, cloud and email hosting, domain registration, transfers and SSL.'],
        'mobile-apps' => ['Mobile Apps', 'Android and cross-platform applications for customers and field teams.'],
        'ai-applications' => ['AI Solutions', 'Assistants, document intelligence, predictive analytics and workflow automation.'],
        'cloud-engineering' => ['Cloud Engineering', 'Cloud architecture, repeatable deployments and reliable operations.'],
        'saas-products' => ['SaaS Products', 'MVPs, multi-tenant platforms, subscriptions, admin tools and maintenance.'],
        'software-testing' => ['Software Testing', 'Functional, regression, compatibility, load and usability testing.'],
        'dedicated-teams' => ['Dedicated Teams', 'Feature development, offshore product maintenance and ongoing support.'],
    ],
];
