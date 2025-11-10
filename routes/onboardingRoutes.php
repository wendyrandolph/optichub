<?php

require_once base_path('app/Http/Controllers/OnboardingController.php');

// Public: set password (token-based)
$router->get('/onboarding/set-password', fn() => (new OnboardingController($pdo))->setPasswordForm());
$router->post('/onboarding/set-password', fn() => (new OnboardingController($pdo))->handleSetPassword());

// Auth required from here on
$router->get('/onboarding/company', fn() => (new OnboardingController($pdo))->companyForm());
$router->post('/onboarding/company', fn() => (new OnboardingController($pdo))->handleCompany());

$router->get('/onboarding/api-key', fn() => (new OnboardingController($pdo))->apiKeyForm());
$router->post('/onboarding/api-key', fn() => (new OnboardingController($pdo))->handleApiKey());

$router->get('/onboarding/finish', fn() => (new OnboardingController($pdo))->finish());

// Request a new activation link (public)
$router->get('/onboarding/request-link', fn() => (new OnboardingController($pdo))->requestLinkForm());
$router->post('/onboarding/request-link', fn() => (new OnboardingController($pdo))->handleRequestLink());
$router->get('/onboarding/link-sent', fn() => (new OnboardingController($pdo))->linkSent());

$router->get('/billing/portal', ['BillingController', 'portal']); // authed
