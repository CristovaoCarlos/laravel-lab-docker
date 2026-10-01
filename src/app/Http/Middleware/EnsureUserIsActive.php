<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia usuários desativados. Alias: "active".
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            abort(403, 'Conta desativada.');   // interrompe a cadeia
        }

        return $next($request);                // segue para a próxima camada
    }
}
