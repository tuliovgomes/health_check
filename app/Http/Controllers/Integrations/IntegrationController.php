<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\StoreIntegrationRequest;
use App\Http\Requests\Integrations\UpdateIntegrationRequest;
use App\Models\Integration;
use App\Services\Integrations\IntegrationNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('ensure.integration.belongs')->only(['show', 'update', 'destroy', 'test']);
    }

    /**
     * Display a listing of the user's integrations.
     */
    public function index(Request $request): JsonResponse
    {
        $integrations = $request->user()
            ->integrations()
            ->latest()
            ->get();

        return response()->json([
            'data' => $integrations->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'name' => $integration->name,
                    'type' => $integration->type->value,
                    'type_label' => $integration->type->label(),
                    'email' => $integration->email,
                    'events' => $integration->events,
                    'event_labels' => collect($integration->getEventTypes())
                        ->map(fn ($event) => $event->label())
                        ->values()
                        ->all(),
                    'last_notification_at' => $integration->last_notification_at,
                    'created_at' => $integration->created_at,
                    'updated_at' => $integration->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created integration.
     */
    public function store(StoreIntegrationRequest $request): JsonResponse
    {
        $integration = Integration::create($request->validated());

        return response()->json([
            'message' => 'Integração criada com sucesso.',
            'data' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'type' => $integration->type->value,
                'type_label' => $integration->type->label(),
                'email' => $integration->email,
                'events' => $integration->events,
                'event_labels' => collect($integration->getEventTypes())
                    ->map(fn ($event) => $event->label())
                    ->values()
                    ->all(),
                'created_at' => $integration->created_at,
            ],
        ], 201);
    }

    /**
     * Display the specified integration.
     */
    public function show(Request $request, Integration $integration): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'type' => $integration->type->value,
                'type_label' => $integration->type->label(),
                'email' => $integration->email,
                'events' => $integration->events,
                'event_labels' => collect($integration->getEventTypes())
                    ->map(fn ($event) => $event->label())
                    ->values()
                    ->all(),
                'metadata' => $integration->metadata,
                'last_notification_at' => $integration->last_notification_at,
                'created_at' => $integration->created_at,
                'updated_at' => $integration->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified integration.
     */
    public function update(UpdateIntegrationRequest $request, Integration $integration): JsonResponse
    {
        $integration->update($request->validated());

        return response()->json([
            'message' => 'Integração atualizada com sucesso.',
            'data' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'type' => $integration->type->value,
                'type_label' => $integration->type->label(),
                'email' => $integration->email,
                'events' => $integration->events,
                'event_labels' => collect($integration->getEventTypes())
                    ->map(fn ($event) => $event->label())
                    ->values()
                    ->all(),
                'updated_at' => $integration->updated_at,
            ],
        ]);
    }

    /**
     * Remove the specified integration.
     */
    public function destroy(Request $request, Integration $integration): JsonResponse
    {
        $integration->delete();

        return response()->json([
            'message' => 'Integração removida com sucesso.',
        ]);
    }

    /**
     * Test the integration.
     */
    public function test(Request $request, Integration $integration): JsonResponse
    {
        try {
            $service = app(IntegrationNotificationService::class);
            $service->sendTestNotification($integration);

            return response()->json([
                'message' => 'Notificação de teste enviada com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar notificação de teste: ' . $e->getMessage(),
            ], 500);
        }
    }
}
