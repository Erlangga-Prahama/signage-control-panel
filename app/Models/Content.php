<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'tipe',
        'payload',
    ];

    public function playlistItems(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    /**
     * Absolute URL the client can render, whether payload is a stored
     * file path, an external URL, or raw text.
     */
    public function getResolvedUrlAttribute(): ?string
    {
        if (in_array($this->tipe, ['image', 'video'])) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->payload);
        }

        if ($this->tipe === 'url') {
            return $this->payload;
        }

        return null;
    }
}