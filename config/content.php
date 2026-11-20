<?php

return [
    'pages' => ['label' => 'Pages', 'table' => 'pages', 'fields' => ['eyebrow', 'title', 'slug', 'description', 'content', 'featured_image_id']],
    'services' => ['label' => 'Services & solutions', 'table' => 'services', 'fields' => ['title', 'slug', 'description', 'content', 'featured_image_id']],
    'portfolio' => ['label' => 'Portfolio', 'table' => 'portfolio_projects', 'fields' => ['title', 'slug', 'category', 'description', 'content', 'challenge', 'solution', 'outcome', 'show_on_homepage', 'is_exclusive', 'is_demonstration', 'featured_image_id']],
    'testimonials' => ['label' => 'Testimonials', 'table' => 'testimonials', 'fields' => ['title', 'name', 'company', 'service_label', 'description', 'rating', 'reviewed_on', 'show_on_homepage', 'consent_confirmed', 'featured_image_id']],
];
