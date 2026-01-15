<?php

namespace App\Providers;

use App\Http\Middleware\ResolveTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Tenant;
use App\Models\Lead;

use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
  public const HOME = '/admin/dashboard';

  public function boot(): void
  {
    parent::boot();

    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('portal-magic-link', function (Request $request) {
      $email = strtolower((string) $request->input('email', ''));
      $ipKey = $request->ip();
      $emailKey = $email !== '' ? $email : 'unknown';

      return [
        Limit::perMinute(6)->by($ipKey),
        Limit::perMinute(3)->by($ipKey . '|' . $emailKey),
      ];
    });

    RateLimiter::for('lead-inbox', function (Request $request) {
      return Limit::perMinute(20)->by($request->ip());
    });

    Route::aliasMiddleware('tenant', ResolveTenant::class);


    //Route::pattern('tenant', '[A-Za-z0-9-]+');

    Route::bind('tenant', function ($value) {
      return Tenant::findOrFail((int) $value);
    });

    $this->routes(function () {
      Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/api.php'));

      Route::middleware('web')
        ->group(base_path('routes/web.php'));
    });
  }
}
