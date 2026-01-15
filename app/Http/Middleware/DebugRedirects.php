<?php

// app/Http/Middleware/DebugRedirects.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class DebugRedirects
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        $info = [
            'path'     => $request->path(),
            'route'    => optional($request->route())->getName(),
            'intended' => session('url.intended'),
            'admin'    => auth('admin')->check(),
            'client'   => auth('client')->check(),
            'web'      => auth()->check(),
            'referer'  => $request->headers->get('referer'),
        ];

        $response = $next($request);

        $info['status'] = $response->getStatusCode();
        if (method_exists($response, 'getTargetUrl')) {
            $info['redirect_to'] = $response->getTargetUrl();
        }
        $info['ms'] = round((microtime(true) - $start) * 1000);

        Log::channel('stack')->info('[redir-debug]', $info);

        return $response;
    }
}
