<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'url',
        'code',
        'check_interval',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'check_interval' => 'integer',
    ];

    /**
     * Get the user that owns the link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the link checks.
     */
    public function checks(): HasMany
    {
        return $this->hasMany(LinkCheck::class)->latest();
    }

    /**
     * Returns when the link is due for the next check (Carbon) or null if unknown.
     */
    public function nextCheckAt(): ?\Illuminate\Support\Carbon
    {
        return $this->last_checked_at
            ? $this->last_checked_at->addMinutes($this->check_interval)
            : null;
    }

    public function isDueForCheck(): bool
    {
        if (is_null($this->last_checked_at)) {
            return true;
        }

        return $this->last_checked_at->diffInMinutes(now()) >= $this->check_interval;
    }
}
