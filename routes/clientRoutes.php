<?php

// Client Routes 

$router->get('/client/portal', function () use ($pdo) {
  error_log("Router: /client/portal route hit. Calling clientOnly().");
  clientOnly(function () use ($pdo) {
    error_log("AuthHelpers: clientOnly callback executed. User status: logged_in=" . (Auth::check() ? 'true' : 'false') . ", is_client=" . (Auth::isClient() ? 'true' : 'false') . ", role=" . (Auth::getRole() ?? 'N/A') . ", org_type=" . (Auth::getOrganizationType() ?? 'N/A'));

    try {
      // Reintroduce the actual controller call here
      (new ClientController($pdo))->portal();
      error_log("SUCCESS: ClientController::portal() call completed without throwing an exception.");
    } catch (Throwable $e) {
      error_log("FATAL ERROR: Exception caught during ClientController::portal() execution: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile());
      http_response_code(500);
      echo "An internal server error occurred: " . $e->getMessage(); // Display error in dev
      exit();
    }
  });
});


// --- NEW PROJECT DETAILS ROUTE ---
$router->get('/client/portal/project/{id}', function ($projectId) use ($pdo) {
  error_log("Router: /client/portal/project/{$projectId} route hit.");
  clientOnly(function () use ($pdo, $projectId) {
    // This calls a new method in your ClientController to display the project details
    (new ClientController($pdo))->viewProjectDetails($projectId);
    error_log("SUCCESS: ClientController::viewProjectDetails({$projectId}) completed.");
  });
});


//Task Comments 
$router->get('/clients/task/{task_id}', function ($taskId) use ($pdo) {
  (new ClientController($pdo))->viewTaskComments($taskId);
});
$router->post('/clients/task/{task_id}/comment', function ($taskId) use ($pdo) {
  (new TaskController($pdo))->addClientComment($taskId);
});
$router->get('/clients/upload/{task_id}', function ($taskId) use ($pdo) {
  (new TaskController($pdo))->showUploadForm($taskId);
});
$router->post('/clients/upload/{task_id}', function ($taskId) use ($pdo) {
  (new TaskController($pdo))->handleUpload($taskId);
});
$router->get('/clients/view/{id}', fn($id) => adminOnly(fn() => (new ClientController($pdo))->show($id)));
$router->post('/clients/resend-login-email/{id}', fn($id) => adminOnly(fn() => (new ClientController($pdo))->resendLoginEmail($id)));
$router->get('/clients/form-thank-you', function () {
  (new ClientController($pdo))->formThankYou();
});
