<?php

return [
    'marketing' => [
        ['label' => 'Home', 'path' => '/'],
        ['label' => 'Features', 'path' => '/features'],
        ['label' => 'Pricing', 'path' => '/pricing'],
        ['label' => 'FAQ', 'path' => '/faq'],
        ['label' => 'About', 'path' => '/about'],
        ['label' => 'Contact', 'path' => '/contact'],
        ['label' => 'Start Trial', 'path' => '/trial'],
        ['label' => 'For Creatives', 'path' => '/for-creatives'],
    ],
    'app' => [
        ['label' => 'Tenant Dashboard', 'path' => '/app/{tenant}/dashboards'],
        ['label' => 'My Schedule', 'path' => '/app/{tenant}/schedule'],
        ['label' => 'Projects Index', 'path' => '/app/{tenant}/projects'],
        ['label' => 'Projects Show', 'path' => '/app/{tenant}/projects/{project}'],
        ['label' => 'Tasks Index', 'path' => '/app/{tenant}/tasks'],
        ['label' => 'Proposals Index', 'path' => '/app/{tenant}/proposals'],
        ['label' => 'Proposals Edit', 'path' => '/app/{tenant}/proposals/{proposal}/edit'],
        ['label' => 'Proposals Internal', 'path' => '/app/{tenant}/proposals/{proposal}'],
    ],
    'public' => [
        ['label' => 'Proposal Public', 'path' => '/proposal/{token}'],
    ],
];
