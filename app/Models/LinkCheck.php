<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LinkStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_id',
        'status',
        'http_status',
        'response_time_ms',
        'error',
    ];

    protected $casts = [
        'status' => LinkStatus::class,
    ];

    /**
     * Get the link that owns the check.
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}