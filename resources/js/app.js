import './bootstrap';

// ===== Kolom kata sandi: tombol mata untuk lihat / sembunyikan =====
// Pakai di Blade: <x-input-sandi id="password" name="password" />
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-toggle-sandi]');
    if (!tombol) return;
    const input = document.getElementById(tombol.dataset.toggleSandi);
    if (!input) return;
    const lihat = input.type === 'password';
    input.type = lihat ? 'text' : 'password';
    tombol.setAttribute('aria-pressed', String(lihat));
    tombol.setAttribute('aria-label', lihat ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
    tombol.querySelector('[data-ikon="lihat"]')?.toggleAttribute('hidden', lihat);
    tombol.querySelector('[data-ikon="sembunyi"]')?.toggleAttribute('hidden', !lihat);
});

// ===== Laci menu di HP (sama dengan state "laci" di AppShell.tsx) =====
const side = () => document.querySelector('.fc-side');
const scrim = () => document.querySelector('.fc-scrim');

function bukaLaci(buka) {
    side()?.classList.toggle('is-open', buka);
    scrim()?.toggleAttribute('hidden', !buka);
    document.body.style.overflow = buka ? 'hidden' : '';
    document.querySelector('[data-buka-laci]')?.setAttribute('aria-expanded', String(buka));
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-buka-laci]')) bukaLaci(true);
    if (e.target.closest('[data-tutup-laci]')) bukaLaci(false);
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') bukaLaci(false);
});

// ===== Sidebar ciut (ikon saja) di desktop, diingat lewat cookie fc_side =====
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-ciut]');
    if (!tombol) return;
    const body = document.querySelector('.fc-body');
    const ciut = !body.classList.contains('is-ciut');
    body.classList.toggle('is-ciut', ciut);
    tombol.setAttribute('aria-label', ciut ? 'Lebarkan menu' : 'Ciutkan menu');
    tombol.title = tombol.getAttribute('aria-label');
    document.cookie = `fc_side=${ciut ? 'ciut' : 'lebar'}; path=/; max-age=31536000; samesite=lax`;
});
