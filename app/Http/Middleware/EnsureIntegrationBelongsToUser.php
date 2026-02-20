<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Integration;

class EnsureIntegrationBelongsToUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $integration = $request->route('integration');

        if ($integration instanceof Integration) {
            $user = $request->user();
            if (!$user || $integration->user_id !== $user->id) {
                abort(403, 'Você não tem permissão para acessar esta integração.');
            }
        }

        return $next($request);
    }
}
