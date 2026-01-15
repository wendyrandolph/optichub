<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApiKeyAuth;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'client.tenant' => \App\Http\Middleware\SetTenantFromClient::class,
            'CheckRole' => \App\Http\Middleware\CheckRole::class, // if you use CheckRole:provider,...
            'block.clients' => \App\Http\Middleware\BlockClients::class,
            'tenant.access' => \App\Http\Middleware\EnsureTenantAccess::class,
            'debug.redirs' => \App\Http\Middleware\DebugRedirects::class,
            'apikey' => ApiKeyAuth::class,
            'platform_owner' => \App\Http\Middleware\EnsurePlatformOwner::class,
            'nocache' => \App\Http\Middleware\NoCache::class,
            'workspace' => \App\Http\Middleware\EnsureWorkspaceType::class,
            'tech' => \App\Http\Middleware\EnsureTech::class,
            'not-tech' => \App\Http\Middleware\EnsureNotTech::class,
            'field-view' => \App\Http\Middleware\EnsureCanViewField::class,
            'trades.reports' => \App\Http\Middleware\EnsureCanViewTradesReports::class,
            'no-trades-reports' => \App\Http\Middleware\BlockReportsForTradesWorkspace::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
