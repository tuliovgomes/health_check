<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status' => \App\Enums\LinkStatus::class,
    ];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}