<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\CheckRole;


class Kernel extends HttpKernel
{
  protected $middleware = [
    // \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
  ];

  /**
   * The application's route middleware groups.
   * NOTE: ResolveTenant has been removed from 'web' and only lives in the alias 'tenant'.
   */
  protected $middlewareGroups = [
    'web' => [
      \App\Http\Middleware\EncryptCookies::class,
      \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
      \Illuminate\Session\Middleware\StartSession::class,
      \Illuminate\View\Middleware\ShareErrorsFromSession::class,
      \App\Http\Middleware\VerifyCsrfToken::class,
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
      // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
      'throttle:api',
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
  ];

  /**
   * The route middleware aliases.
   * This is the primary place to define aliases like 'role' and 'tenant'.
   */
  protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'checkrole' => \App\Http\Middleware\CheckRole::class, // your canonical alias
    //'role'      => \App\Http\Middleware\CheckRole::class, // temporary shim
    'tenant' => \App\Http\Middleware\ResolveTenant::class,

    'trial.only' => \App\Http\Middleware\TrialOnly::class,

  ];


  /**
   * Backwards compatibility for code paths that still look at $routeMiddleware.
   * This should mirror $middlewareAliases for consistency.
   */
  protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'trial.only' => \App\Http\Middleware\TrialOnly::class,
    'tenant' => \App\Http\Middleware\ResolveTenant::class,
    'apikey' => \App\Http\Middleware\ApiKeyAuth::class,
  ];
}
