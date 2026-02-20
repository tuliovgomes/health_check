<?php

namespace App\Http\Requests\Integrations;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $integration = $this->route('integration');
        
        return $integration && $this->user()->id === $integration->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => [Rule::enum(EventType::class)],
            'metadata' => ['sometimes', 'array'],
        ];

        // Adiciona regras específicas baseado no tipo existente
        $integration = $this->route('integration');
        
        if ($integration) {
            $type = $integration->type;
            
            if ($type === IntegrationType::EMAIL) {
                $rules['email'] = ['sometimes', 'email', 'max:255'];
            } elseif ($type === IntegrationType::SLACK) {
                $rules['token'] = ['sometimes', 'string', 'max:500'];
                $rules['channel_token'] = ['sometimes', 'string', 'max:500'];
                $rules['user_token'] = ['sometimes', 'nullable', 'string', 'max:500'];
            } elseif ($type === IntegrationType::DISCORD) {
                $rules['token'] = ['sometimes', 'string', 'max:500'];
                $rules['user_token'] = ['sometimes', 'string', 'max:500'];
                $rules['channel_token'] = ['sometimes', 'nullable', 'string', 'max:500'];
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'O nome deve ser um texto válido.',
            'events.array' => 'Os eventos devem ser fornecidos como uma lista.',
            'events.min' => 'Selecione pelo menos um evento para notificar.',
            'email.email' => 'Informe um e-mail válido.',
            'token.string' => 'O token deve ser um texto válido.',
            'user_token.string' => 'O token do usuário deve ser um texto válido.',
            'channel_token.string' => 'O token do canal deve ser um texto válido.',
        ];
    }
}
