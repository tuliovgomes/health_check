<?php

namespace App\Http\Controllers;

use App\Enums\Plan;
use App\Models\Link;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{

    public function __construct()
    {
        $this->middleware('ensure.link.belongs')->only(['getLinkChecks']);
    }

    /**
     * Display the dashboard with user statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $planEnum = Plan::tryFrom($user->plan) ?? Plan::FREE;
        
        $linksCount = $user->links()->count();
        $linksQuota = $user->links_quota;
        
        $integrationsCount = $user->integrations()->count();
        $integrationsQuota = $planEnum->integrationsQuota();
        
        $logsRetentionDays = $planEnum->logsRetentionDays();
        $logsCount = \App\Models\LinkCheck::whereHas('link', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('created_at', '>=', now()->subDays($logsRetentionDays))
        ->count();
        
        $userLinks = $user->links()
            ->select('id', 'title', 'url')
            ->get()
            ->map(fn($link) => [
                'id' => $link->id,
                'title' => $link->title,
                'url' => $link->url,
            ]);
        
        return Inertia::render('Dashboard', [
            'stats' => [
                'links' => [
                    'current' => $linksCount,
                    'quota' => $linksQuota,
                    'percentage' => $linksQuota ? round(($linksCount / $linksQuota) * 100) : 0,
                ],
                'integrations' => [
                    'current' => $integrationsCount,
                    'quota' => $integrationsQuota,
                    'percentage' => $integrationsQuota ? round(($integrationsCount / $integrationsQuota) * 100) : 0,
                ],
                'logs' => [
                    'count' => $logsCount,
                    'retention_days' => $logsRetentionDays,
                ],
            ],
            'plan' => [
                'name' => $planEnum->displayName(),
                'key' => $planEnum->value,
            ],
            'userLinks' => $userLinks,
        ]);
    }
    
    /**
     * Get the latest link checks for a specific link.
     */
    public function getLinkChecks(Request $request, Link $link)
    {
        $checks = $link->checks()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($check) => [
                'id' => $check->id,
                'status' => $check->status->value,
                'http_status' => $check->http_status,
                'response_time_ms' => $check->response_time_ms,
                'error' => $check->error,
                'created_at' => $check->created_at->diffForHumans(),
            ])
            ->reverse()
            ->values();
        
        return response()->json($checks);
    }
}
