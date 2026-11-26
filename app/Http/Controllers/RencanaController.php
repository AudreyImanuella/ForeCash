<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\RencanaPengeluaran;
use App\Models\TargetTabungan;
use App\Services\Forecast;
use App\Support\Format;
use App\Support\Rencana;
use App\Support\Saldo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Rencana: anggaran per kategori, target tabungan, pengeluaran yang direncanakan (port dari app/(app)/rencana). */
class RencanaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hari = Format::hariIni();
        $bulan = substr($hari, 0, 7);
        $dari = Carbon::parse($bulan . '-01');

        $wallets = $user->wallets()->orderBy('id')->get()->each(fn ($w) => $w->saldo_sekarang = $w->saldo());
        $f = Forecast::untuk($user, (float) $wallets->sum('saldo_sekarang'));

        $kategori = Category::milik($user->id)->where('jenis', 'pengeluaran')->orderBy('id')->get(['id', 'nama']);
        $namaKat = $kategori->pluck('nama', 'id');

        // pengeluaran bulan ini per kategori
        $terpakai = $user->transactions()->where('jenis', 'pengeluaran')
            ->where('tanggal', '>=', $dari->toDateString())->where('tanggal', '<', $dari->copy()->addMonth()->toDateString())
            ->whereNotNull('category_id')
            ->groupBy('category_id')->selectRaw('category_id, SUM(nominal) AS total')
            ->pluck('total', 'category_id')->map(fn ($v) => (float) $v)->all();

        $baris = Rencana::ringkasAnggaran(
            $user->anggaran()->get()->filter(fn ($a) => $namaKat->has($a->category_id))
                ->map(fn ($a) => ['category_id' => $a->category_id, 'nama' => $namaKat[$a->category_id], 'batas' => (float) $a->batas])
                ->values()->all(),
            $terpakai,
        );
        $punya = array_column($baris, 'category_id');
        $kategoriKosong = $kategori->reject(fn ($k) => in_array($k->id, $punya));
        $total = ['batas' => array_sum(array_column($baris, 'batas')), 'terpakai' => array_sum(array_column($baris, 'terpakai'))];

        $targets = $user->targetTabungan()
            ->orderByRaw('tercapai_pada IS NOT NULL')->orderByDesc('tercapai_pada')->orderBy('created_at')
            ->get()
            ->each(fn ($t) => $t->info = Rencana::infoTarget((float) $t->target, (float) $t->terkumpul, $t->tenggat?->toDateString(), $hari));

        $rencana = $user->rencana()->with('category')->where('status', 'rencana')->orderBy('tanggal')->get();

        return view('rencana.index', [
            'baris' => $baris, 'kategori' => $kategori, 'kategoriKosong' => $kategoriKosong, 'total' => $total,
            'namaBulan' => Format::namaBulan($bulan), 'targets' => $targets, 'rencana' => $rencana,
            'wallets' => $wallets, 'f' => $f, 'hari' => $hari,
        ]);
    }

    // ---------- anggaran ----------

    /** Simpan batas anggaran satu kategori. Batas 0 / kosong = anggaran kategori itu dihapus. */
    public function anggaran(Request $request): RedirectResponse
    {
        $user = $request->user();
        $categoryId = (int) $request->input('category_id');
        $batas = max(0, Format::bacaNominal($request->input('batas')));

        $boleh = Category::milik($user->id)->where('jenis', 'pengeluaran')->whereKey($categoryId)->exists();
        if (! $boleh) {
            return back()->with('error', 'Pilih kategori.');
        }
        if ($batas == 0) {
            $user->anggaran()->where('category_id', $categoryId)->delete();

            return back()->with('sukses', 'Anggaran kategori ini dihapus.');
        }
        $user->anggaran()->updateOrCreate(['category_id' => $categoryId], ['batas' => $batas]);

        return back()->with('sukses', 'Anggaran ' . Format::rupiah($batas) . ' per bulan disimpan.');
    }

    // ---------- target tabungan ----------

    public function tambahTarget(Request $request): RedirectResponse
    {
        $request->merge(['target' => Format::bacaNominal($request->input('target'))]);
        $data = $request->validateWithBag('target', [
            'nama' => ['required', 'string', 'max:80'],
            'target' => ['numeric', 'gt:0', 'max:999999999999'],
            'tenggat' => ['nullable', 'date_format:Y-m-d', 'after:' . Format::hariIni()],
        ], [
            'nama.required' => 'Nama target wajib diisi.',
            'target.gt' => 'Jumlah target wajib diisi.',
            'target.max' => 'Jumlah target terlalu besar.',
            'tenggat.after' => 'Tenggat harus setelah hari ini.',
            'tenggat.date_format' => 'Tenggat tidak valid.',
        ]);

        $request->user()->targetTabungan()->create([
            'nama' => trim($data['nama']), 'target' => $data['target'], 'tenggat' => $data['tenggat'] ?? null,
        ]);

        return back()->with('sukses', 'Target "' . trim($data['nama']) . '" dibuat. Semangat menabung!');
    }

    /** Tambah setoran ke target. Kalau terkumpul sudah mencapai target, tanggal tercapai dicatat. */
    public function setor(Request $request, TargetTabungan $target): RedirectResponse
    {
        $this->pastikanMilik($request, $target->user_id);
        $nominal = Format::bacaNominal($request->input('nominal'));
        if ($nominal <= 0) {
            return back()->with('error', 'Isi jumlah setoran.');
        }

        $baruTercapai = DB::transaction(function () use ($request, $target, $nominal) {
            $t = TargetTabungan::whereKey($target->id)->lockForUpdate()->first();
            $terkumpul = (float) $t->terkumpul + $nominal;
            $baru = ! $t->tercapai_pada && $terkumpul >= (float) $t->target;

            $t->setoran()->create(['user_id' => $request->user()->id, 'nominal' => $nominal, 'tanggal' => Format::hariIni()]);
            $t->update(['terkumpul' => $terkumpul] + ($baru ? ['tercapai_pada' => Format::hariIni()] : []));

            return $baru;
        });

        return back()->with('sukses', $baruTercapai
            ? 'Target "' . $target->nama . '" TERCAPAI! Hebat!'
            : 'Setoran ' . Format::rupiah($nominal) . ' masuk ke "' . $target->nama . '".');
    }

    public function hapusTarget(Request $request, TargetTabungan $target): RedirectResponse
    {
        $this->pastikanMilik($request, $target->user_id);
        $target->delete();

        return back()->with('sukses', 'Target dihapus.');
    }

    // ---------- rencana pengeluaran ----------

    public function tambahRencana(Request $request): RedirectResponse
    {
        $uid = $request->user()->id;
        $request->merge(['nominal' => Format::bacaNominal($request->input('nominal'))]);
        $data = $request->validateWithBag('rencana', [
            'nama' => ['required', 'string', 'max:100'],
            'nominal' => ['numeric', 'gt:0', 'max:999999999999'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')
                ->where('jenis', 'pengeluaran')->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $uid))],
            'berulang' => ['nullable'],
        ], [
            'nama.required' => 'Nama rencana wajib diisi.',
            'nominal.gt' => 'Nominal wajib diisi.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'category_id.exists' => 'Kategori tidak valid.',
        ]);

        $request->user()->rencana()->create([
            'nama' => trim($data['nama']), 'nominal' => $data['nominal'], 'tanggal' => $data['tanggal'],
            'category_id' => $data['category_id'] ?? null, 'berulang' => $request->boolean('berulang'),
        ]);

        return back()->with('sukses', 'Rencana disimpan dan ikut dihitung di Prediksi.');
    }

    /** Rencana sudah dibayar: dicatat sebagai pengeluaran. Rencana bulanan pindah ke bulan depan. */
    public function bayar(Request $request, RencanaPengeluaran $rencana): RedirectResponse
    {
        $user = $request->user();
        $this->pastikanMilik($request, $rencana->user_id);
        $walletId = (int) $request->input('wallet_id');

        $hasil = DB::transaction(function () use ($user, $rencana, $walletId) {
            $r = RencanaPengeluaran::whereKey($rencana->id)->lockForUpdate()->first();
            if (! $r || $r->status !== 'rencana') {
                return ['error' => 'Rencana tidak ditemukan atau sudah dibayar.'];
            }
            if (! $user->wallets()->whereKey($walletId)->exists()) {
                return ['error' => 'Pilih wallet untuk pembayaran.'];
            }
            $tx = ['jenis' => 'pengeluaran', 'nominal' => (float) $r->nominal, 'wallet_id' => $walletId, 'wallet_tujuan_id' => null];
            $user->wallets()->lockForUpdate()->get();
            if ($pesan = Saldo::cek($user, $tx)) {
                return ['error' => $pesan];
            }

            $user->transactions()->create($tx + [
                'category_id' => $r->category_id, 'keterangan' => mb_substr($r->nama, 0, 150), 'tanggal' => Format::hariIni(),
            ]);
            $berikut = $r->berulang ? Format::tambahBulan($r->tanggal->toDateString()) : null;
            $r->update($berikut ? ['tanggal' => $berikut] : ['status' => 'selesai']);

            return ['berikut' => $berikut];
        });

        if (isset($hasil['error'])) {
            return back()->with('error', $hasil['error']);
        }

        return back()->with('sukses', $hasil['berikut']
            ? 'Dicatat sebagai pengeluaran. Rencana berikutnya ' . Format::tgl($hasil['berikut']) . '.'
            : 'Dicatat sebagai pengeluaran.');
    }

    public function hapusRencana(Request $request, RencanaPengeluaran $rencana): RedirectResponse
    {
        $this->pastikanMilik($request, $rencana->user_id);
        $rencana->delete();

        return back()->with('sukses', 'Rencana dihapus.');
    }

    private function pastikanMilik(Request $request, int|string $userId): void
    {
        abort_unless((int) $userId === (int) $request->user()->id, 404);
    }
}
