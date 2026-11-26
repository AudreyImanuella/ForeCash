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

// ===== Form transaksi (port dari InputRupiah.tsx & TxForm.tsx versi Next.js) =====

/** "25000" -> "25.000". Semua selain angka dibuang, nol di depan juga. */
const formatRibuan = (teks) =>
    String(teks).replace(/\D/g, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

// Pemisah ribuan otomatis saat mengetik. Posisi kursor dijaga (dihitung dari kanan)
// supaya tidak loncat ke ujung saat titik bertambah/berkurang.
document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-rupiah]');
    if (!input) return;
    const dariKanan = input.value.length - input.selectionStart;
    input.value = formatRibuan(input.value);
    const pos = Math.max(0, input.value.length - dariKanan);
    input.setSelectionRange(pos, pos);
});
document.querySelectorAll('[data-rupiah]').forEach((input) => {
    input.value = formatRibuan(input.value);
});

// Ganti jenis: tampil/sembunyikan kategori & wallet tujuan, dan saring pilihan kategori.
function aturJenis(form) {
    const jenis = form.querySelector('[data-pilih-jenis]')?.value;
    const transfer = jenis === 'transfer';

    form.querySelector('[data-bukan-transfer]')?.toggleAttribute('hidden', transfer);
    form.querySelector('[data-hanya-transfer]')?.toggleAttribute('hidden', !transfer);
    const label = form.querySelector('[data-label-wallet]');
    if (label) label.textContent = transfer ? 'Dari wallet' : 'Wallet';

    const kategori = form.querySelector('[data-pilih-kategori]');
    if (!kategori || transfer) return;
    kategori.querySelectorAll('optgroup').forEach((grup) => {
        const cocok = grup.dataset.jenis === jenis;
        grup.hidden = !cocok;
        grup.disabled = !cocok;
    });
    // kalau kategori yang terpilih bukan milik jenis ini, pilih kategori pertama yang cocok
    if (kategori.selectedOptions[0]?.parentElement.dataset.jenis !== jenis) {
        kategori.value = kategori.querySelector(`optgroup[data-jenis="${jenis}"] option`)?.value ?? '';
    }
}
document.addEventListener('change', (e) => {
    if (e.target.matches('[data-pilih-jenis]')) aturJenis(e.target.form);
});
document.querySelectorAll('[data-form-tx]').forEach(aturJenis);

// Nominal >= Rp5.000.000 minta konfirmasi, supaya salah ketik (kelebihan nol) tidak langsung tersimpan.
const BATAS_KONFIRMASI = 5_000_000;
document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-form-tx]');
    if (!form) return;
    const n = Number(form.nominal.value.replace(/\D/g, ''));
    if (n >= BATAS_KONFIRMASI &&
        !confirm(`Yakin nominalnya Rp ${formatRibuan(n)}?\n\nPeriksa lagi jumlah angka nol sebelum menyimpan.`)) {
        e.preventDefault();
        form.nominal.focus();
    }
});