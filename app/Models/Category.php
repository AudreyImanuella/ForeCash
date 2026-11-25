<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['user_id', 'nama', 'jenis'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /** Kategori bawaan (user_id kosong) + kategori buatan user tersebut. */
    public function scopeMilik(Builder $query, int $userId): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId));
    }
}
