<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\IntegrationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'is_active',
        'email',
        'token',
        'user_token',
        'channel_token',
        'events',
        'metadata',
        'last_notification_at',
    ];

    protected $casts = [
        'type' => IntegrationType::class,
        'is_active' => 'boolean',
        'events' => 'array',
        'metadata' => 'array',
        'last_notification_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
        'user_token',
        'channel_token',
    ];

    /**
     * Get the user that owns the integration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encrypt token on set.
     */
    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Encrypt user_token on set.
     */
    protected function userToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Encrypt channel_token on set.
     */
    protected function channelToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Check if integration should notify for a given event.
     */
    public function shouldNotifyFor(EventType $event): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return in_array($event->value, $this->events ?? []);
    }

    /**
     * Get parsed event types.
     */
    public function getEventTypes(): array
    {
        return collect($this->events ?? [])
            ->map(fn (string $event) => EventType::tryFrom($event))
            ->filter()
            ->all();
    }

    /**
     * Scope to get integrations by type.
     */
    public function scopeOfType($query, IntegrationType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get integrations that should notify for an event.
     */
    public function scopeForEvent($query, EventType $event)
    {
        return $query->whereJsonContains('events', $event->value);
    }

    /**
     * Update last notification timestamp.
     */
    public function markAsNotified(): void
    {
        $this->update(['last_notification_at' => now()]);
    }
}
