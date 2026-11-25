<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $jumlahPeran = User::selectRaw('role, COUNT(*) AS jumlah')->groupBy('role')->pluck('jumlah', 'role');
        $jumlahTransaksi = Transaction::count();
        $users = User::orderByDesc('created_at')->paginate(15);

        return view('admin.index', compact('jumlahPeran', 'jumlahTransaksi', 'users'));
    }
}
