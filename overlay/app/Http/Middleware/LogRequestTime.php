<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Código "antes" e "depois" do $next: mede o tempo da requisição,
 * grava em log e devolve o valor no header X-Response-Time.
 * Registrado no grupo "api" em bootstrap/app.php.
 */
class LogRequestTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);              // antes

        $response = $next($request);           // controller e demais camadas

        $ms = round((microtime(true) - $start) * 1000, 2);   // depois
        $response->headers->set('X-Response-Time', $ms.'ms');
        Log::info('request', ['method' => $request->method(), 'path' => $request->path(), 'ms' => $ms]);

        return $response;
    }
}
