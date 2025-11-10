<?php
// core/BaseController.php
require_once base_path('app/helpers/Format.php');

class BaseController
{
  protected $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  // Example: Inside BaseController.php

  protected function jsonResponse(array $data, int $statusCode = 200): void
  {
    // Set the HTTP response code
    http_response_code($statusCode);

    // Set the Content-Type header
    header('Content-Type: application/json');

    // Output the JSON encoded data and stop script execution
    echo json_encode($data);
    exit;
  }
  /** Safe redirect even if output started */
  protected function redirect(string $url, int $code = 302): void
  {
    if (!headers_sent()) {
      header("Location: {$url}", true, $code);
      exit;
    }
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo "<script>location.replace('{$safe}');</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url={$safe}'></noscript>";
    exit;
  }

  /** Buffer the layout so exceptions don’t flush partial HTML */
  public function view(string $viewFile, array $data = [], array $layoutData = []): void
  {
    if (!empty($layoutData)) {
      $data = array_merge($layoutData, $data);
    }

    $bladeView = str_replace('/', '.', ltrim($viewFile, '/'));
    if (function_exists('view') && view()->exists($bladeView)) {
      echo view($bladeView, $data)->render();
      return;
    }

    extract($data, EXTR_SKIP);

    $viewPath = base_path('resources/views/' . ltrim($viewFile, '/') . '.php');
    $layoutPath = (strpos($viewFile, 'auth/login') !== false)
      ? base_path('resources/views/layouts/login.php')
      : base_path('resources/views/layouts/main.php');
    if (!is_file($viewPath)) {
      throw new \InvalidArgumentException("View not found: {$viewFile}");
    }
    if (!is_file($layoutPath)) {
      throw new \InvalidArgumentException("Layout not found for view: {$viewFile}");
    }
    $viewIncludePath = $viewPath;

    ob_start();
    try {
      // Layout should include `$viewPath` internally.
      require $layoutPath;
      echo ob_get_clean();
    } catch (\Throwable $e) {
      ob_end_clean();            // discard any partial output
      throw $e;                  // let controller catch & redirect
    }
  }

  protected function csrfVerify($token): void
  {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
      die('CSRF token validation failed.');
    }
  }

  protected function sessionStart(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }
}
