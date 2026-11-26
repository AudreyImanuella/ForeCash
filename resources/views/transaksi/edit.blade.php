{{-- Ubah transaksi (port dari app/(app)/transaksi/[id]/ubah/page.tsx versi Next.js). --}}
@extends('layouts.app')

@section('judul', 'Ubah Transaksi')
@section('judul_halaman', 'Ubah Transaksi')

@section('isi')
    <div class="max-w-2xl">
        @include('transaksi._form', ['aksi' => route('transaksi.update', $transaksi), 't' => $transaksi])
    </div>
@endsection