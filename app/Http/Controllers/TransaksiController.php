<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransaksiRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Format;
use App\Support\Saldo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Halaman Transaksi mahasiswa (port dari app/(app)/transaksi versi Next.js). */
class TransaksiController extends Controller
{
    /** Foto struk disimpan di disk private (storage/app/private), bukan folder public. */
    private const DISK = 'local';

    /** Daftar transaksi per bulan + form catat baru. */
    public function index(Request $request): View
    {
        $user = $request->user();

        // ?bulan=YYYY-MM, kalau kosong/aneh pakai bulan ini
        $bulan = $request->query('bulan');
        if (! is_string($bulan) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $bulan = Carbon::now(Format::ZONA)->format('Y-m');
        }
        $dari = Carbon::parse($bulan . '-01')->startOfMonth();
        $sampai = $dari->copy()->addMonth();

        // ?jenis= dari tombol "Mau catat apa?" di Beranda
        $jenisAwal = in_array($request->query('jenis'), ['pemasukan', 'pengeluaran', 'transfer'], true)
            ? $request->query('jenis') : 'pengeluaran';

        $transaksi = $user->transactions()
            ->with(['category', 'wallet', 'walletTujuan'])
            ->where('tanggal', '>=', $dari->toDateString())
            ->where('tanggal', '<', $sampai->toDateString())
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get();

        [$wallets, $kategori] = $this->opsiForm($user);

        // untuk kartu "Batas Pengeluaran Harian"
        $keluarHariIni = (float) $user->transactions()
            ->where('jenis', 'pengeluaran')->whereDate('tanggal', Format::hariIni())->sum('nominal');

        return view('transaksi.index', compact('transaksi', 'bulan', 'jenisAwal', 'wallets', 'kategori', 'keluarHariIni', 'user'));
    }

    /** Simpan batas pengeluaran harian (0 = tidak dibatasi). */
    public function batasHarian(Request $request): RedirectResponse
    {
        $batas = Format::bacaNominal($request->input('batas_harian'));
        if ($batas < 0 || $batas > 999999999999) {
            return back()->with('error', 'Batas harian tidak valid.');
        }
        $request->user()->update(['batas_harian' => $batas]);

        return back()->with('sukses', 'Batas harian diperbarui.');
    }

    /**
     * Ekspor transaksi satu bulan ke CSV (port dari transaksi/export/route.ts).
     * Pemisah titik koma (;) dan BOM UTF-8 supaya langsung rapi dibuka di Excel versi Indonesia.
     */
    public function export(Request $request): StreamedResponse
    {
        $bulan = $request->query('bulan');
        if (! is_string($bulan) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            $bulan = Carbon::now(Format::ZONA)->format('Y-m');
        }
        $dari = Carbon::parse($bulan . '-01');

        $data = $request->user()->transactions()
            ->with(['category', 'wallet', 'walletTujuan'])
            ->where('tanggal', '>=', $dari->toDateString())
            ->where('tanggal', '<', $dari->copy()->addMonth()->toDateString())
            ->orderBy('tanggal')->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Jenis', 'Keterangan', 'Kategori', 'Wallet', 'Ke Wallet', 'Nominal'], ';', '"', '', "\r\n");
            foreach ($data as $t) {
                fputcsv($out, [
                    $t->tanggal->toDateString(), $t->jenis, $t->keterangan, $t->category?->nama ?? '-',
                    $t->wallet?->nama ?? '', $t->walletTujuan?->nama ?? '', (string) (float) $t->nominal,
                ], ';', '"', '', "\r\n");
            }
            fclose($out);
        }, "forecash-{$bulan}.csv", ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function store(TransaksiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->dataTransaksi();

        $error = DB::transaction(function () use ($request, $user, $data) {
            $this->kunciWallet($user);
            if ($pesan = Saldo::cek($user, $data)) {
                return $pesan;
            }

            $user->transactions()->create($data + ['bukti' => $this->simpanStruk($request)]);

            return null;
        });

        if ($error) {
            return back()->withInput()->withErrors(['nominal' => $error]);
        }

        // tampilkan bulan transaksi yang baru dicatat
        return redirect()->route('transaksi.index', ['bulan' => substr($data['tanggal'], 0, 7)])
            ->with('sukses', 'Transaksi tersimpan.');
    }

    public function edit(Request $request, Transaction $transaksi): View
    {
        $this->pastikanMilik($request, $transaksi);
        [$wallets, $kategori] = $this->opsiForm($request->user());

        return view('transaksi.edit', compact('transaksi', 'wallets', 'kategori'));
    }

    public function update(TransaksiRequest $request, Transaction $transaksi): RedirectResponse
    {
        $this->pastikanMilik($request, $transaksi);
        $user = $request->user();
        $data = $request->dataTransaksi();
        $lama = $transaksi->only(Saldo::KOLOM);
        $buktiLama = $transaksi->bukti;

        $error = DB::transaction(function () use ($request, $user, $transaksi, $data, $lama, $buktiLama) {
            $this->kunciWallet($user);
            // pengaruh transaksi lama "dibatalkan" dulu, baru yang baru dihitung
            if ($pesan = Saldo::cek($user, $data, $lama)) {
                return $pesan;
            }

            // foto baru menggantikan foto lama; centang "hapus" menghapus foto tanpa pengganti
            $bukti = $buktiLama;
            if ($request->hasFile('bukti')) {
                $bukti = $this->simpanStruk($request);
            } elseif ($request->boolean('hapus_bukti')) {
                $bukti = null;
            }

            $transaksi->update($data + ['bukti' => $bukti]);

            return null;
        });

        if ($error) {
            return back()->withInput()->withErrors(['nominal' => $error]);
        }

        // file lama dihapus setelah data berhasil disimpan
        if ($buktiLama && $buktiLama !== $transaksi->bukti) {
            Storage::disk(self::DISK)->delete($buktiLama);
        }

        return redirect()->route('transaksi.index', ['bulan' => substr($data['tanggal'], 0, 7)])
            ->with('sukses', 'Transaksi diperbarui.');
    }

    public function destroy(Request $request, Transaction $transaksi): RedirectResponse
    {
        $this->pastikanMilik($request, $transaksi);
        $user = $request->user();

        $error = DB::transaction(function () use ($user, $transaksi) {
            $this->kunciWallet($user);
            // menghapus pemasukan yang uangnya sudah terpakai akan membuat saldo minus
            if ($pesan = Saldo::cek($user, null, $transaksi->only(Saldo::KOLOM))) {
                return 'Tidak bisa dihapus. ' . $pesan;
            }
            $transaksi->delete();

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        if ($transaksi->bukti) {
            Storage::disk(self::DISK)->delete($transaksi->bukti);
        }

        return back()->with('sukses', 'Transaksi dihapus.');
    }

    /** Tampilkan foto struk, hanya untuk pemilik transaksinya. */
    public function struk(Request $request, Transaction $transaksi): StreamedResponse
    {
        $this->pastikanMilik($request, $transaksi);
        abort_unless($transaksi->bukti && Storage::disk(self::DISK)->exists($transaksi->bukti), 404);

        return Storage::disk(self::DISK)->response($transaksi->bukti);
    }

    // ---------- pembantu ----------

    /** Transaksi milik user lain dianggap tidak ada (404), supaya id orang lain tidak bisa ditebak. */
    private function pastikanMilik(Request $request, Transaction $transaksi): void
    {
        abort_unless((int) $transaksi->user_id === (int) $request->user()->id, 404);
    }

    /** Wallet (beserta saldo sekarang) dan kategori untuk pilihan di form. */
    private function opsiForm(User $user): array
    {
        $wallets = $user->wallets()->orderBy('id')->get()
            ->each(fn ($w) => $w->saldo_sekarang = $w->saldo());
        $kategori = Category::milik($user->id)->orderBy('jenis')->orderBy('nama')->get();

        return [$wallets, $kategori];
    }

    /**
     * Kunci baris wallet milik user sampai DB::transaction selesai, supaya dua kiriman form
     * yang bersamaan (misal tombol simpan diklik dua kali) tidak sama-sama lolos cek saldo.
     */
    private function kunciWallet(User $user): void
    {
        $user->wallets()->lockForUpdate()->get();
    }

    /** Simpan foto struk ke storage/app/private/struk/{user_id}/..., kembalikan path-nya (atau null). */
    private function simpanStruk(Request $request): ?string
    {
        return $request->file('bukti')?->store('struk/' . $request->user()->id, self::DISK);
    }
}