<?php
// File: public_html/core/Router.php

// If Auth.php is inside public_html/app (your case), this path is correct:
require_once __DIR__ . '/../app/helpers/Auth.php';

class Router
{
  private $routes = [];

  public function get($path, $handler)
  {
    $this->routes['GET'][$path]    = $handler;
  }
  public function post($path, $handler)
  {
    $this->routes['POST'][$path]   = $handler;
  }
  public function put($path, $handler)
  {
    $this->routes['PUT'][$path]    = $handler;
  }
  public function delete($path, $handler)
  {
    $this->routes['DELETE'][$path] = $handler;
  }
  // Optional: handy if you add CORS later
  public function options($path, $handler)
  {
    $this->routes['OPTIONS'][$path] = $handler;
  }
  public function head($path, $handler)
  {
    $this->routes['HEAD'][$path]   = $handler;
  }

  public function dispatch($path, $method)
  {
    $path = ($path === '/') ? $path : rtrim($path, '/');

    $isApi = (strpos($path, '/api/') === 0);

    $publicPaths = [
      '/login',
      '/logout',
      '/register',
      '/password/forgot',
      '/password/reset',
      '/clients/form-thank-you',
      '/welcome',
      '/onboarding/set-password',   // ← add this
      '/onboarding/request-link',   // ← add this
      '/',
      '/pricing',
      '/features',
      '/faq',
      '/about',
      '/contact',
      '/marketing',
      '/marketing/*',           // wildcard for any marketing subpages
      '/stripe/webhook',
      '/trial',
      '/trial/start',
      '/trial/start/',

    ];

    $isPublic = function (string $path) use ($publicPaths): bool {
      foreach ($publicPaths as $p) {
        if ($p === $path) return true;
        if (substr($p, -2) === '/*') {
          $prefix = rtrim($p, '/*');
          if (strpos($path, $prefix) === 0) return true;
        }
      }
      return false;
    };
    // ---- Session auth for web UI ONLY (not for /api/*) ----
    if (!$isApi && !in_array($path, $publicPaths, true) && !Auth::check()) {
      header('Location: /login');
      exit();
    }

    // ---- Route matching ----
    if (!empty($this->routes[$method])) {
      foreach ($this->routes[$method] as $routePattern => $handler) {
        $routePattern = ($routePattern === '/') ? $routePattern : rtrim($routePattern, '/');

        // 1) Static match
        if ($routePattern === $path) {
          call_user_func($handler);
          return;
        }

        // 2) Dynamic {param} match
        $regexPattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $routePattern);
        $regexPattern = '#^' . $regexPattern . '$#';

        if (preg_match($regexPattern, $path, $matches)) {
          array_shift($matches);
          call_user_func_array($handler, $matches);
          return;
        }
      }
    }

    http_response_code(404);
    echo '404 Not Found';
    exit();
  }
}
