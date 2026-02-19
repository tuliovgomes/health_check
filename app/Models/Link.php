<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checks()
    {
        return $this->hasMany(\App\Models\LinkCheck::class)->latest();
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
