<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'wallet_id', 'wallet_tujuan_id', 'category_id',
        'jenis', 'nominal', 'keterangan', 'tanggal', 'bukti',
    ];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function walletTujuan(): BelongsTo { return $this->belongsTo(Wallet::class, 'wallet_tujuan_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
