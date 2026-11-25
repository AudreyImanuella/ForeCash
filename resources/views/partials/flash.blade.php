{{-- Pesan setelah aksi (dari controller: ->with('sukses', ...) atau ->with('error', ...)) --}}
@if (session('sukses'))
    <p class="fc-flash" role="status">{{ session('sukses') }}</p>
@endif
@if (session('error'))
    <p class="fc-flash is-error" role="alert">{{ session('error') }}</p>
@endif
