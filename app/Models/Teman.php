<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teman extends Model
{
    // Tanpa baris ini Laravel menebak nama tabel "temen" (aturan jamak bahasa Inggris: man -> men).
    protected $table = 'teman';

    protected $fillable = ['user_id', 'teman_id'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function teman(): BelongsTo { return $this->belongsTo(User::class, 'teman_id'); }
}
