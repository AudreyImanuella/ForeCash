<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetTabungan extends Model
{
    protected $table = 'target_tabungan';

    protected $fillable = ['user_id', 'nama', 'target', 'terkumpul', 'tenggat', 'tercapai_pada'];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2', 'terkumpul' => 'decimal:2',
            'tenggat' => 'date', 'tercapai_pada' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function setoran(): HasMany { return $this->hasMany(SetoranTabungan::class, 'target_id'); }

    /** Persentase terkumpul, 0-100. */
    public function persen(): int
    {
        return $this->target > 0 ? (int) min(100, floor($this->terkumpul / $this->target * 100)) : 0;
    }
}
