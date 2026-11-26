<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Debt;
use App\Models\User;
use App\Support\Format;
use App\Support\Saldo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Utang & Patungan (port dari app/(app)/utang versi Next.js).
 * Fungsi database Supabase (kirim_tagihan, sinkron_lunas, tambah_teman, ...) dipindah ke method di sini.
 */
class UtangController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $belum = $user->debts()->where('status', 'belum')
            ->orderByRaw('jatuh_tempo IS NULL')->orderBy('jatuh_tempo')->orderByDesc('created_at')
            ->get();
        $riwayat = $user->debts()->where('status', 'lunas')->orderByDesc('updated_at')->limit(10)->get();
        $pelunasan = $user->debts()->where('perlu_dicatat', true)->get();
        $wallets = $user->wallets()->orderBy('id')->get(['id', 'nama']);
        $teman = $user->teman()->orderBy('name')->get(['users.id', 'users.name', 'users.avatar']);

        return view('utang.index', compact('user', 'belum', 'riwayat', 'pelunasan', 'wallets', 'teman'));
    }

    /** Catat satu utang/piutang. Kalau dipilih teman ForeCash, tagihannya dikirim ke akun teman. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['nominal' => Format::bacaNominal($request->input('nominal'))]);
        $data = $request->validateWithBag('catat', [
            'teman_fc' => ['nullable', 'integer'],
            'nama_teman' => ['nullable', 'required_without:teman_fc', 'string', 'max:100'],
            'keterangan' => ['required', 'string', 'max:150'],
            'nominal' => ['numeric', 'gt:0', 'max:999999999999'],
            'arah' => ['required', Rule::in(['piutang', 'utang'])],
            'jatuh_tempo' => ['nullable', 'date_format:Y-m-d'],
        ], $this->pesan());

        if (! empty($data['teman_fc'])) {
            if ($data['arah'] !== 'piutang') {
                return back()->withInput()->withErrors(['arah' => 'Untuk teman ForeCash, catat "dia utang ke saya". Utangmu ke dia dicatat oleh temanmu.'], 'catat');
            }
            $dia = $this->temanSaya($user, (int) $data['teman_fc']);
            if (! $dia) {
                return back()->withInput()->withErrors(['teman_fc' => 'Kalian belum berteman di ForeCash.'], 'catat');
            }
            $this->kirimTagihan($user, $dia, $data['keterangan'], (float) $data['nominal'], $data['jatuh_tempo'] ?? null);

            return back()->with('sukses', 'Tagihan terkirim ke akun ' . $dia->name . '.');
        }

        $user->debts()->create([
            'nama_teman' => trim($data['nama_teman']),
            'keterangan' => trim($data['keterangan']),
            'nominal' => $data['nominal'],
            'arah' => $data['arah'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
        ]);

        return back()->with('sukses', 'Catatan disimpan.');
    }

    /** Split bill: bagi total rata. Teman ForeCash dikirimi tagihan, teman lain dicatat sebagai piutang biasa. */
    public function split(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['total' => Format::bacaNominal($request->input('total'))]);
        $data = $request->validateWithBag('split', [
            'judul' => ['nullable', 'string', 'max:150'],
            'total' => ['numeric', 'gt:0', 'max:999999999999'],
            'teman' => ['nullable', 'string', 'max:1000'],
            'teman_fc' => ['nullable', 'array'],
            'teman_fc.*' => ['integer'],
            'termasuk_saya' => ['nullable'],
            'jatuh_tempo' => ['nullable', 'date_format:Y-m-d'],
        ], ['total.gt' => 'Isi total tagihan.'] + $this->pesan());

        $judul = trim($data['judul'] ?? '') ?: 'Split bill';
        $namaLain = self::daftarTeman($data['teman'] ?? '');
        $temanFc = collect($data['teman_fc'] ?? [])->unique()
            ->map(fn ($id) => $this->temanSaya($user, (int) $id))->filter()->values();
        $jumlah = count($namaLain) + $temanFc->count();
        if ($jumlah === 0) {
            return back()->withInput()->withErrors(['teman' => 'Pilih minimal satu teman.'], 'split');
        }

        $per = self::bagianSplit((float) $data['total'], $jumlah, $request->boolean('termasuk_saya'));
        $tempo = $data['jatuh_tempo'] ?? null;

        DB::transaction(function () use ($user, $temanFc, $namaLain, $judul, $per, $tempo) {
            foreach ($temanFc as $dia) {
                $this->kirimTagihan($user, $dia, 'Split bill: ' . $judul, $per, $tempo);
            }
            foreach ($namaLain as $nama) {
                $user->debts()->create([
                    'nama_teman' => $nama, 'keterangan' => $judul, 'nominal' => $per,
                    'arah' => 'piutang', 'jatuh_tempo' => $tempo,
                ]);
            }
        });

        $kirim = $temanFc->count() ? " {$temanFc->count()} tagihan terkirim ke akun teman ForeCash." : '';

        return back()->with('sukses', 'Split bill dibuat: ' . Format::rupiah($per) . " per orang, {$jumlah} piutang tercatat.{$kirim}");
    }

    /**
     * Tandai lunas, lalu otomatis dicatat sebagai transaksi:
     * pelunasan piutang = uang masuk (pemasukan), pelunasan utang = uang keluar (pengeluaran).
     */
    public function lunas(Request $request, Debt $utang): RedirectResponse
    {
        $user = $request->user();
        $this->pastikanMilik($request, $utang);

        $error = DB::transaction(function () use ($request, $user, $utang) {
            // kunci baris supaya klik dua kali tidak mencatat transaksi dua kali
            $d = Debt::whereKey($utang->id)->lockForUpdate()->first();
            if (! $d || $d->status !== 'belum') {
                return 'Catatan tidak ditemukan atau sudah lunas.';
            }
            if ($pesan = $this->catatTransaksi($user, $d, (int) $request->input('wallet_id'))) {
                return $pesan;
            }
            $d->update(['status' => 'lunas']);

            // tagihan dengan teman ForeCash: catatan di akun teman ikut lunas, teman diminta mencatat transaksinya
            if ($d->pasangan_id) {
                Debt::whereKey($d->pasangan_id)
                    ->where('pasangan_id', $d->id)->where('user_id', $d->teman_id)->where('status', 'belum')
                    ->update(['status' => 'lunas', 'perlu_dicatat' => true]);
            }

            return null;
        });

        return $error
            ? back()->with('error', $error)
            : back()->with('sukses', 'Ditandai lunas dan dicatat sebagai ' . ($utang->arah === 'piutang' ? 'pemasukan' : 'pengeluaran') . '.');
    }

    /** Teman menandai lunas: catat transaksinya ke wallet saya, atau abaikan. */
    public function catatPelunasan(Request $request, Debt $utang): RedirectResponse
    {
        $user = $request->user();
        $this->pastikanMilik($request, $utang);

        $error = DB::transaction(function () use ($request, $user, $utang) {
            $d = Debt::whereKey($utang->id)->lockForUpdate()->first();
            if (! $d || ! $d->perlu_dicatat) {
                return 'Catatan tidak ditemukan.';
            }
            if (! $request->boolean('abaikan') && ($pesan = $this->catatTransaksi($user, $d, (int) $request->input('wallet_id')))) {
                return $pesan;
            }
            $d->update(['perlu_dicatat' => false]);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('sukses', $request->boolean('abaikan')
            ? 'Oke, tidak dicatat sebagai transaksi.'
            : 'Dicatat sebagai ' . ($utang->arah === 'piutang' ? 'pemasukan' : 'pengeluaran') . '.');
    }

    public function destroy(Request $request, Debt $utang): RedirectResponse
    {
        $this->pastikanMilik($request, $utang);
        $utang->delete();

        return back()->with('sukses', 'Catatan dihapus.');
    }

    // ---------- Teman ForeCash ----------

    public function tambahTeman(Request $request): RedirectResponse
    {
        $user = $request->user();
        $kode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $request->input('kode')));
        if (strlen($kode) !== 6) {
            return back()->withErrors(['kode' => 'Kode teman terdiri dari 6 huruf/angka.'], 'teman');
        }

        $dia = User::where('kode_teman', $kode)->where('role', 'mahasiswa')->first();
        if (! $dia) {
            return back()->withErrors(['kode' => 'Kode teman tidak ditemukan.'], 'teman');
        }
        if ($dia->id === $user->id) {
            return back()->withErrors(['kode' => 'Itu kode temanmu sendiri.'], 'teman');
        }

        // pertemanan disimpan dua arah
        $user->teman()->syncWithoutDetaching([$dia->id]);
        $dia->teman()->syncWithoutDetaching([$user->id]);

        return back()->with('sukses', 'Sekarang kamu berteman dengan ' . $dia->name . '.');
    }

    public function hapusTeman(Request $request, User $teman): RedirectResponse
    {
        $user = $request->user();
        $user->teman()->detach($teman->id);
        $teman->teman()->detach($user->id);

        return back()->with('sukses', 'Pertemanan diputus. Catatan utang yang sudah ada tetap tersimpan.');
    }

    // ---------- logika kecil (port dari lib/utang.ts) ----------

    /** "Budi, Siti , ,Bima" -> ['Budi', 'Siti', 'Bima'] */
    public static function daftarTeman(string $teks): array
    {
        return array_values(array_map(
            fn ($s) => mb_substr($s, 0, 100),
            array_filter(array_map('trim', explode(',', $teks)), fn ($s) => $s !== '')
        ));
    }

    /** Bagian per orang = round(total / jumlah orang). */
    public static function bagianSplit(float $total, int $jumlahTeman, bool $sayaIkut): float
    {
        $orang = $jumlahTeman + ($sayaIkut ? 1 : 0);

        return $orang > 0 ? round($total / $orang) : 0;
    }

    /** Link WhatsApp berisi pesan pengingat. Penerimanya dipilih sendiri di aplikasi WhatsApp. */
    public static function linkPengingatWA(string $nama, string $keterangan, float $nominal): string
    {
        $pesan = "Halo {$nama}, sekadar mengingatkan untuk {$keterangan} sebesar " . Format::rupiah($nominal) . '. Terima kasih!';

        return 'https://wa.me/?text=' . rawurlencode($pesan);
    }

    // ---------- pembantu ----------

    /** Piutang di akun saya + utang di akun teman, saling terhubung lewat pasangan_id. */
    private function kirimTagihan(User $saya, User $dia, string $keterangan, float $nominal, ?string $tempo): void
    {
        DB::transaction(function () use ($saya, $dia, $keterangan, $nominal, $tempo) {
            $ket = mb_substr(trim($keterangan), 0, 150);
            $punyaSaya = $saya->debts()->create([
                'nama_teman' => $dia->name, 'keterangan' => $ket, 'nominal' => $nominal,
                'arah' => 'piutang', 'jatuh_tempo' => $tempo, 'teman_id' => $dia->id,
            ]);
            $punyaDia = $dia->debts()->create([
                'nama_teman' => $saya->name, 'keterangan' => $ket, 'nominal' => $nominal,
                'arah' => 'utang', 'jatuh_tempo' => $tempo, 'teman_id' => $saya->id, 'pasangan_id' => $punyaSaya->id,
            ]);
            $punyaSaya->update(['pasangan_id' => $punyaDia->id]);
        });
    }

    /** Catat pelunasan sebagai transaksi di wallet terpilih. Mengembalikan pesan error atau null. */
    private function catatTransaksi(User $user, Debt $d, int $walletId): ?string
    {
        if (! $user->wallets()->whereKey($walletId)->exists()) {
            return 'Pilih wallet untuk pelunasan.';
        }
        $jenis = $d->arah === 'piutang' ? 'pemasukan' : 'pengeluaran';
        $tx = ['jenis' => $jenis, 'nominal' => (float) $d->nominal, 'wallet_id' => $walletId, 'wallet_tujuan_id' => null];

        $user->wallets()->lockForUpdate()->get();
        if ($pesan = Saldo::cek($user, $tx)) {
            return $pesan;
        }

        $kategori = Category::whereNull('user_id')->where('nama', 'Pelunasan Utang/Piutang')->where('jenis', $jenis)->value('id');
        $user->transactions()->create($tx + [
            'category_id' => $kategori,
            'keterangan' => mb_substr("Pelunasan: {$d->nama_teman} - {$d->keterangan}", 0, 150),
            'tanggal' => Format::hariIni(),
        ]);

        return null;
    }

    private function temanSaya(User $user, int $id): ?User
    {
        return $user->teman()->where('users.id', $id)->first();
    }

    private function pastikanMilik(Request $request, Debt $utang): void
    {
        abort_unless((int) $utang->user_id === (int) $request->user()->id, 404);
    }

    private function pesan(): array
    {
        return [
            'nama_teman.required_without' => 'Nama teman wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'nominal.gt' => 'Nominal harus lebih dari 0.',
            'nominal.max' => 'Nominal terlalu besar.',
            'total.max' => 'Total terlalu besar.',
            'jatuh_tempo.date_format' => 'Tanggal jatuh tempo tidak valid.',
        ];
    }
}
