<?php

namespace App\Http\Controllers;

use App\Enums\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsPageController extends Controller
{
    /**
     * Display the integrations page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userPlan = Plan::tryFrom($user->plan) ?? Plan::FREE;
        
        $planConfig = config("plans.plans.{$userPlan->value}");
        
        $allowedTypes = [];
        if ($planConfig['notifications']['channels']['email'] ?? false) {
            $allowedTypes[] = 'email';
        }
        if ($planConfig['notifications']['channels']['slack'] ?? false) {
            $allowedTypes[] = 'slack';
        }
        if ($planConfig['notifications']['channels']['discord'] ?? false) {
            $allowedTypes[] = 'discord';
        }
        
        // Eventos permitidos
        $allowedEvents = [];
        foreach ($planConfig['notifications']['events'] ?? [] as $event => $enabled) {
            if ($enabled) {
                $allowedEvents[] = $event;
            }
        }
        
        $planLimits = [
            'plan' => $userPlan->value,
            'plan_name' => $userPlan->displayName(),
            'max_integrations' => $userPlan->integrationsQuota(),
            'allowed_types' => $allowedTypes,
            'allowed_events' => $allowedEvents,
            'current_count' => $user->integrations()->count(),
        ];

        return Inertia::render('Integrations/Index', [
            'planLimits' => $planLimits,
        ]);
    }
}
