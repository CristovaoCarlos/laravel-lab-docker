<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LogRequestTime;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apelidos usados nas rotas: ->middleware('active') e ->middleware('role:admin,editor')
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => CheckRole::class,
        ]);

        // Roda em toda requisição do grupo "api".
        $middleware->appendToGroup('api', LogRequestTime::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
