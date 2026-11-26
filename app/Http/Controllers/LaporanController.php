<?php

namespace App\Http\Controllers;

use App\Support\Format;
use App\Support\Laporan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Laporan Bulanan (port dari app/(app)/laporan/page.tsx versi Next.js). */
class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $bulan = Laporan::bulanDariParam($request->query('bulan'));

        return view('laporan.index', [
            'bulan' => $bulan,
            'namaBulan' => Format::namaBulan($bulan),
            'r' => Laporan::untuk($request->user(), $bulan),
        ]);
    }
}
