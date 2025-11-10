<?php

// app/routes/proposalRoutes.php

require_once base_path('app/Http/Controllers/ProposalController.php');

// Internal CRM routes (require authentication)
$router->get('/proposals', function () use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->index();
});

$router->get('/proposals/create', function () use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->create();
});

$router->get('/proposals/create_manual', function () use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->create();
});

$router->post('/proposals/store', function () use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->store();
});

$router->get('/proposals/view/{id}', function ($id) use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->show($id);
});

// The public routes should not have the isAdmin() check
$router->post('/proposals/accept/{token}', function ($token) use ($pdo) {
  (new ProposalController($pdo))->accept($token);
});

$router->post('/proposals/reject/{token}', function ($token) use ($pdo) {
  (new ProposalController($pdo))->reject($token);
});

// The public-facing view route
$router->get('/p/{token}', function ($token) use ($pdo) {
  (new ProposalController($pdo))->show($token);
});

// You will also need to add edit and delete routes when you implement those methods
$router->get('/proposals/edit/{id}', function ($id) use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->edit($id);
});

$router->post('/proposals/delete/{id}', function ($id) use ($pdo) {
  if (!Auth::isAdmin()) {
    header("Location: /client/portal");
    exit;
  }
  (new ProposalController($pdo))->delete($id);
});
