<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    protected $fillable = [
        'user_id', 'nama_teman', 'keterangan', 'nominal', 'arah', 'status',
        'jatuh_tempo', 'teman_id', 'pasangan_id', 'perlu_dicatat',
    ];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'jatuh_tempo' => 'date', 'perlu_dicatat' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /** Akun ForeCash teman (kalau tagihan dari split bill antar akun). */
    public function temanAkun(): BelongsTo { return $this->belongsTo(User::class, 'teman_id'); }

    /** Catatan pasangannya di akun teman (piutang di sini = utang di sana). */
    public function pasangan(): BelongsTo { return $this->belongsTo(Debt::class, 'pasangan_id'); }
}
