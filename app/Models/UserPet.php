<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPet extends Model
{
    protected $fillable = ['user_id', 'pet_id', 'menetas_pada', 'dipakai'];

    protected function casts(): array
    {
        return ['menetas_pada' => 'date', 'dipakai' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
