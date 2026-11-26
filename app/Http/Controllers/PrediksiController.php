<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IncomeSchedule;
use App\Services\Forecast;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Prediksi & jadwal pemasukan (port dari app/(app)/prediksi versi Next.js). */
class PrediksiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallets = $user->wallets()->orderBy('id')->get()->each(fn ($w) => $w->saldo_sekarang = $w->saldo());
        $f = Forecast::untuk($user, (float) $wallets->sum('saldo_sekarang'));

        $jadwal = $user->incomeSchedules()->where('status', 'menunggu')->orderBy('tanggal_perkiraan')->get();
        $rencana = $user->rencana()->where('status', 'rencana')->get()
            ->map(fn ($r) => ['tanggal' => $r->tanggal->toDateString(), 'nominal' => (float) $r->nominal])->values();

        // pet yang sedang menemani (fitur Petualangan)
        $petDipakai = $user->pets()->where('dipakai', true)->value('pet_id');

        return view('prediksi.index', [
            'user' => $user, 'f' => $f, 'jadwal' => $jadwal, 'wallets' => $wallets,
            'rencana' => $rencana, 'hari' => Format::hariIni(), 'petDipakai' => $petDipakai,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['nominal' => Format::bacaNominal($request->input('nominal'))]);
        $data = $request->validateWithBag('jadwal', [
            'sumber' => ['required', 'string', 'max:50'],
            'nominal' => ['numeric', 'gt:0', 'max:999999999999'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ], [
            'sumber.required' => 'Sumber pemasukan wajib diisi.',
            'nominal.gt' => 'Nominal wajib diisi.',
            'tanggal.required' => 'Tanggal perkiraan wajib diisi.',
            'tanggal.date_format' => 'Tanggal tidak valid.',
        ]);

        $request->user()->incomeSchedules()->create([
            'sumber' => trim($data['sumber']), 'nominal' => $data['nominal'], 'tanggal_perkiraan' => $data['tanggal'],
        ]);

        return back()->with('sukses', 'Jadwal pemasukan ditambahkan.');
    }

    /** Uang sudah datang: jadwal ditandai diterima dan otomatis dicatat sebagai pemasukan. */
    public function terima(Request $request, IncomeSchedule $jadwal): RedirectResponse
    {
        $user = $request->user();
        $this->pastikanMilik($request, $jadwal);
        $walletId = (int) $request->input('wallet_id');

        $error = DB::transaction(function () use ($user, $jadwal, $walletId) {
            $s = IncomeSchedule::whereKey($jadwal->id)->lockForUpdate()->first();
            if (! $s || $s->status !== 'menunggu') {
                return 'Jadwal tidak ditemukan atau sudah diterima.';
            }
            if (! $user->wallets()->whereKey($walletId)->exists()) {
                return 'Pilih wallet tujuan.';
            }
            $kategori = Category::whereNull('user_id')->where('nama', 'Uang Saku')->where('jenis', 'pemasukan')->value('id');
            $user->transactions()->create([
                'wallet_id' => $walletId, 'category_id' => $kategori, 'jenis' => 'pemasukan',
                'nominal' => $s->nominal, 'keterangan' => $s->sumber, 'tanggal' => Format::hariIni(),
            ]);
            $s->update(['status' => 'diterima']);

            return null;
        });

        return $error
            ? back()->with('error', $error)
            : back()->with('sukses', 'Pemasukan diterima dan dicatat ke transaksi.');
    }

    public function destroy(Request $request, IncomeSchedule $jadwal): RedirectResponse
    {
        $this->pastikanMilik($request, $jadwal);
        $jadwal->delete();

        return back()->with('sukses', 'Jadwal dihapus.');
    }

    private function pastikanMilik(Request $request, IncomeSchedule $jadwal): void
    {
        abort_unless((int) $jadwal->user_id === (int) $request->user()->id, 404);
    }
}
