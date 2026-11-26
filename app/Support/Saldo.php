<?php

namespace App\Support;

use App\Models\User;

/**
 * Penjaga saldo: transaksi tidak boleh membuat saldo wallet jadi minus.
 * Port dari lib/saldo.ts versi Next.js.
 *
 * Transaksi ditulis sebagai array: ['jenis', 'nominal', 'wallet_id', 'wallet_tujuan_id'].
 * Dari model cukup pakai $transaksi->only(Saldo::KOLOM).
 */
class Saldo
{
    public const KOLOM = ['jenis', 'nominal', 'wallet_id', 'wallet_tujuan_id'];

    /** Pengaruh satu transaksi ke saldo tiap wallet: [wallet_id => perubahan]. */
    public static function efek(?array $tx): array
    {
        $efek = [];
        if (! $tx) {
            return $efek;
        }

        $nominal = (float) $tx['nominal'];
        $asal = (int) $tx['wallet_id'];

        // pemasukan menambah saldo, pengeluaran & transfer mengurangi wallet asal
        $efek[$asal] = ($efek[$asal] ?? 0) + ($tx['jenis'] === 'pemasukan' ? $nominal : -$nominal);

        // transfer menambah saldo wallet tujuan
        if ($tx['jenis'] === 'transfer' && ! empty($tx['wallet_tujuan_id'])) {
            $tujuan = (int) $tx['wallet_tujuan_id'];
            $efek[$tujuan] = ($efek[$tujuan] ?? 0) + $nominal;
        }

        return $efek;
    }

    /**
     * Cek apakah mengganti transaksi $lama dengan $baru membuat saldo wallet mana pun minus.
     * Tambah: $lama = null. Hapus: $baru = null.
     * Mengembalikan pesan error, atau null kalau aman.
     */
    public static function cek(User $user, ?array $baru, ?array $lama = null): ?string
    {
        $efekBaru = self::efek($baru);
        $efekLama = self::efek($lama);

        // cukup periksa wallet yang terpengaruh, dan hanya milik user ini
        $idTerpengaruh = array_unique(array_merge(array_keys($efekBaru), array_keys($efekLama)));
        $wallets = $user->wallets()->whereIn('id', $idTerpengaruh)->get();

        foreach ($wallets as $w) {
            $saldo = $w->saldo();
            $tanpaLama = $saldo - ($efekLama[$w->id] ?? 0);
            $sesudah = round($tanpaLama + ($efekBaru[$w->id] ?? 0), 2);

            // hanya tolak kalau transaksi ini MEMPERBURUK saldo
            // (wallet yang sudah terlanjur minus tetap boleh diperbaiki)
            if ($sesudah < 0 && $sesudah < $saldo) {
                return "Saldo {$w->nama} tidak cukup. Tersedia "
                    . Format::rupiah(max(0, $tanpaLama))
                    . ', saldo tidak boleh minus.';
            }
        }

        return null;
    }
}