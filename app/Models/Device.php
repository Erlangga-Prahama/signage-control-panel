<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'lokasi',
        'status',
        'last_seen',
        'device_key',
        'playlist_id',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            if (empty($device->device_key)) {
                $device->device_key = Str::random(48);
            }
            if (empty($device->status)) {
                $device->status = 'offline';
            }
        });
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    /**
     * The command currently waiting to be shown (an admin-pushed override
     * that hasn't been acked by the client yet), if any.
     */
    public function pendingCommand(): HasMany
    {
        return $this->hasMany(DeviceCommand::class)->where('status', '!=', 'acked')->latest();
    }

    public function isOnline(): bool
    {
        if ($this->status !== 'online' || ! $this->last_seen) {
            return false;
        }

        $threshold = (int) config('signage.offline_threshold', 20);

        return $this->last_seen->gt(now()->subSeconds($threshold));
    }
}