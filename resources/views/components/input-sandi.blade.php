{{-- Kolom kata sandi dengan tombol mata (port dari components/form/InputSandi.tsx).
     Contoh: <x-input-sandi id="password" name="password" autocomplete="new-password" /> --}}
@props(['id', 'name'])
<div class="fc-sandi">
    <input id="{{ $id }}" name="{{ $name }}" type="password" placeholder="••••••••"
           {{ $attributes->merge(['class' => 'w-full px-4 py-3 pr-12 border text-sm font-semibold']) }}>
    <button type="button" class="fc-sandi-mata" data-toggle-sandi="{{ $id }}" aria-label="Lihat kata sandi" aria-pressed="false">
        <x-ikon nama="eye" data-ikon="lihat" />
        <x-ikon nama="eye-off" data-ikon="sembunyi" hidden />
    </button>
</div>
