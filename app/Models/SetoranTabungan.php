<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranTabungan extends Model
{
    protected $table = 'setoran_tabungan';

    protected $fillable = ['user_id', 'target_id', 'nominal', 'tanggal'];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal' => 'date'];
    }

    public function target(): BelongsTo { return $this->belongsTo(TargetTabungan::class, 'target_id'); }
}
