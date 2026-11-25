<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    public const TIPE = ['tunai' => 'Tunai', 'bank' => 'Bank', 'ewallet' => 'E-Wallet'];

    protected $fillable = ['user_id', 'nama', 'tipe', 'saldo_awal'];

    protected function casts(): array
    {
        return ['saldo_awal' => 'decimal:2'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /** Transaksi yang keluar/masuk dari wallet ini. */
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }

    /** Transfer yang masuk ke wallet ini dari wallet lain. */
    public function transferMasuk(): HasMany { return $this->hasMany(Transaction::class, 'wallet_tujuan_id'); }

    /**
     * Saldo sekarang = saldo awal + pemasukan - pengeluaran - transfer keluar + transfer masuk.
     * Sama dengan view wallet_saldo di versi Next.js.
     */
    public function saldo(): float
    {
        $masuk = (float) $this->transactions()->where('jenis', 'pemasukan')->sum('nominal');
        $keluar = (float) $this->transactions()->whereIn('jenis', ['pengeluaran', 'transfer'])->sum('nominal');
        $transferMasuk = (float) $this->transferMasuk()->where('jenis', 'transfer')->sum('nominal');

        return (float) $this->saldo_awal + $masuk - $keluar + $transferMasuk;
    }
}
