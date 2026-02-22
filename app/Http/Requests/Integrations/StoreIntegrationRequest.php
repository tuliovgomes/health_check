<?php

namespace App\Http\Requests\Integrations;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use App\Enums\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(IntegrationType::class)],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::enum(EventType::class)],
            'metadata' => ['sometimes', 'array'],
        ];

        // Adiciona regras específicas baseado no tipo
        $type = $this->input('type');
        
        if ($type === IntegrationType::EMAIL->value) {
            $rules['email'] = ['required', 'email', 'max:255'];
        } elseif ($type === IntegrationType::SLACK->value) {
            $rules['token'] = ['required', 'string', 'max:500'];
            $rules['channel_token'] = ['required', 'string', 'max:255'];
        } elseif ($type === IntegrationType::DISCORD->value) {
            $rules['token'] = ['required', 'string', 'max:500'];
            $rules['user_token'] = ['required', 'string', 'max:500'];
            $rules['channel_token'] = ['sometimes', 'nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $userPlan = Plan::tryFrom($user->plan) ?? Plan::FREE;
            $type = IntegrationType::tryFrom($this->input('type'));
            $events = $this->input('events', []);

            // Obtém configurações do plano
            $planConfig = config("plans.plans.{$userPlan->value}");
            $maxIntegrations = $userPlan->integrationsQuota();

            // Verifica limite de integrações
            if ($maxIntegrations !== null) {
                $existingCount = $user->integrations()->count();
                if ($existingCount >= $maxIntegrations) {
                    $validator->errors()->add('plan', "Plano {$userPlan->displayName()} permite até {$maxIntegrations} integração(ões). Faça upgrade para adicionar mais.");
                }
            }

            // Verifica se o tipo de integração é permitido
            $allowedChannels = $planConfig['notifications']['channels'] ?? [];
            $typeMapping = [
                IntegrationType::EMAIL->value => 'email',
                IntegrationType::SLACK->value => 'slack',
                IntegrationType::DISCORD->value => 'discord',
            ];
            
            if ($type && isset($typeMapping[$type->value])) {
                $channelKey = $typeMapping[$type->value];
                if (!($allowedChannels[$channelKey] ?? false)) {
                    $validator->errors()->add('type', "Plano {$userPlan->displayName()} não permite integrações do tipo {$type->label()}. Faça upgrade.");
                }
            }

            // Verifica se os eventos são permitidos
            $allowedEventsConfig = $planConfig['notifications']['events'] ?? [];
            $allowedEvents = [];
            foreach ($allowedEventsConfig as $event => $enabled) {
                if ($enabled) {
                    $allowedEvents[] = $event;
                }
            }

            foreach ($events as $event) {
                if (!in_array($event, $allowedEvents)) {
                    $eventObj = EventType::tryFrom($event);
                    $eventLabel = $eventObj ? $eventObj->label() : $event;
                    $validator->errors()->add('events', "Plano {$userPlan->displayName()} não permite o evento: {$eventLabel}.");
                    break;
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da integração é obrigatório.',
            'type.required' => 'O tipo da integração é obrigatório.',
            'events.required' => 'Selecione pelo menos um evento para notificar.',
            'events.min' => 'Selecione pelo menos um evento para notificar.',
            'email.required' => 'O e-mail é obrigatório para integrações de e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'token.required' => 'O token do bot é obrigatório para integrações Slack.',
            'user_token.required' => 'O token do usuário é obrigatório para este tipo de integração.',
            'channel_token.required' => 'O ID do canal é obrigatório para integrações Slack.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Garante que user_id seja do usuário autenticado
        $user = Auth::user();
        if ($user) {
            $this->merge([
                'user_id' => $user->id,
            ]);
        }
    }
}
