<?php

// config/seo.php

return [
  'defaults' => [
    'title' => 'Optic Hub – Clarity for Creatives',
    'description' => 'A clean, intuitive CRM for creatives. Organize clients, projects, and billing in one calm place.',
    'bodyClass' => 'theme-light marketing',
  ],
  'marketing' => [
    'home' => [
      'title' => 'Optic Hub – Clarity for creatives who juggle clients.',
      'description' => 'Keep projects, timelines, and invoices in one calm place—so you can focus on the work you love.',
    ],
    'features' => [
      'title' => 'Features – Optic Hub | Tools that bring clarity to your day',
      'description' => 'Clients, projects, calendar, invoices, a client portal, and templates—organized in one calm hub.',
      'canonical' => '/features',
      'image' => '/og/features.jpg',
    ],
    // ... include pricing, faq, about, etc., here
  ],
];
