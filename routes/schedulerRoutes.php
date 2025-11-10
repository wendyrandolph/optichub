<?php

//Scheduler Routes 
$router->get('/scheduler', fn() => adminOnly(fn() => (new SchedulerController($pdo))->index()));
$router->post('/scheduler/add-rule', fn() => adminOnly(fn() => (new SchedulerController($pdo))->addRule()));
$router->post('/scheduler/book', fn() => (new SchedulerController($pdo))->book());
$router->post('/scheduler/add-date-availability', fn() => adminOnly(fn() => (new SchedulerController($pdo))->addDateAvailability()));

//Routes for Time 
$router->get('/time', fn() => adminOnly(fn() => (new TimeEntryController($pdo))->index()));
$router->get('/time/create', fn() => adminOnly(fn() => (new TimeEntryController($pdo))->createForm()));
$router->post('/time/create', fn() => adminOnly(fn() => (new TimeEntryController($pdo))->create()));
$router->get('/time/edit/{id}', fn($id) => adminOnly(fn() => (new TimeEntryController($pdo))->editForm($id)));
$router->post('/time/update/{id}', fn($id) => adminOnly(fn() => (new TimeEntryController($pdo))->update($id)));
$router->post('/time/delete/{id}', fn($id) => adminOnly(fn() => (new TimeEntryController($pdo))->delete($id)));
