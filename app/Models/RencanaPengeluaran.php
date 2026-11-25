<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RencanaPengeluaran extends Model
{
    protected $table = 'rencana_pengeluaran';

    protected $fillable = ['user_id', 'nama', 'nominal', 'tanggal', 'category_id', 'berulang', 'status'];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal' => 'date', 'berulang' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
