<?php

namespace App\Http\Controllers;

use App\Enums\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with user statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get user's current plan
        $planEnum = Plan::tryFrom($user->plan) ?? Plan::FREE;
        
        // Links statistics
        $linksCount = $user->links()->count();
        $linksQuota = $user->links_quota;
        
        // Integrations statistics
        $integrationsCount = $user->integrations()->count();
        $integrationsQuota = $planEnum->integrationsQuota();
        
        // Logs statistics (LinkCheck records)
        $logsRetentionDays = $planEnum->logsRetentionDays();
        $logsCount = \App\Models\LinkCheck::whereHas('link', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('created_at', '>=', now()->subDays($logsRetentionDays))
        ->count();
        
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
        ]);
    }
}
