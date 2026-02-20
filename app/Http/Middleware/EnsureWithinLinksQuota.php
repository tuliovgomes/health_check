<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureWithinLinksQuota
{
    /**
     * Block link creation when user reached quota.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('EnsureWithinLinksQuota middleware triggered', ['ip' => $request->ip(), 'user_id' => $request->user()?->id ?? null, 'cookies' => $request->cookies->all()]);
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $quota = $user->links_quota;

        // null quota => unlimited
        if (is_null($quota)) {
            return $next($request);
        }

        $count = $user->links()->count();

        if ($count >= $quota) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached your link creation limit for your current plan.',
            ], 403);
        }

        return $next($request);
    }
}
