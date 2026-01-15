<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (app('router')->getRoutes() as $route) {
    $action = $route->getAction();
    if (isset($action['controller'])) {
        [$controller] = explode('@', $action['controller']);
        try {
            app()->make($controller);
        } catch (Throwable $e) {
            echo "Instantiate fail: {$route->uri()} => {$controller}\n";
            echo $e->getMessage(),"\n";
        }
    }
}
