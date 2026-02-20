<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Link;

class EnsureLinkBelongsToUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $link = $request->route('link');

        if ($link instanceof Link) {
            $user = $request->user();
            if (!$user || $link->user_id !== $user->id) {
                abort(403);
            }
        }

        return $next($request);
    }
}
