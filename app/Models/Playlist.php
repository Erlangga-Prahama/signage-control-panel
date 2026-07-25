<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'deskripsi'];

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('urutan');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}