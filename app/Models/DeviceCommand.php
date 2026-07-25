<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'content_id',
        'command',
        'status',
        'delivered_at',
        'acked_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'acked_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}