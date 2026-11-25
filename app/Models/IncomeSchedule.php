<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeSchedule extends Model
{
    protected $fillable = ['user_id', 'sumber', 'nominal', 'tanggal_perkiraan', 'status'];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal_perkiraan' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
