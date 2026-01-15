<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
  /**
   * A list of the exception types that are not reported.
   *
   * @var array<int, class-string<Throwable>>
   */
  protected $dontReport = [];

  /**
   * A list of the inputs that are never flashed to the session on validation exceptions.
   *
   * @var array<int, string>
   */
  protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
  ];

  /**
   * Render an exception into an HTTP response.
    */
  public function render($request, Throwable $e)
  {
    if ($e instanceof TokenMismatchException) {
      // Graceful CSRF expiry handling: keep session, refresh token, and guide the user
      if ($request->hasSession()) {
        $request->session()->regenerateToken();
      }

      // For POST/PUT/etc: send back with a friendly message instead of forcing logout
      if (!$request->isMethod('get')) {
        return back()
          ->withInput($request->except('_token'))
          ->with('error', 'Session expired — please try again. We refreshed your session.');
      }

      // For GET: nudge to login if needed
      return redirect()
        ->guest(route('login'))
        ->with('error', 'Session expired — please sign in again.');
    }

    return parent::render($request, $e);
  }

  /**
   * Handle unauthenticated users.
   */
  protected function unauthenticated($request, AuthenticationException $exception)
  {
    return redirect()->guest(route('login'));
  }
}
