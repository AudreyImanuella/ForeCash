<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentLink extends Model
{
    protected $fillable = ['orangtua_id', 'anak_id'];

    public function orangtua(): BelongsTo { return $this->belongsTo(User::class, 'orangtua_id'); }
    public function anak(): BelongsTo { return $this->belongsTo(User::class, 'anak_id'); }
}
