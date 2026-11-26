import './bootstrap';
import { bacaTeksStruk } from './struk-parser';
import QRCode from 'qrcode';

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

// Tombol buka/tutup bagian tersembunyi: data-buka="#id-elemen"
document.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-buka]');
    if (!tombol) return;
    const el = document.querySelector(tombol.dataset.buka);
    if (!el) return;
    el.hidden = !el.hidden;
    tombol.setAttribute('aria-expanded', String(!el.hidden));
    if (!el.hidden) el.querySelector('input:not([type=hidden])')?.focus();
});

// ===== Chatbot: ForeCash Assistant (port dari components/Chatbot.tsx) =====

/** Teks bot -> HTML aman: escape dulu, lalu **tebal**, [teks](/link-internal), dan baris baru. */
function htmlBot(teks) {
    const aman = teks.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    return aman
        .replace(/^\s*[*-]\s+/gm, '• ')   // poin daftar dari jawaban AI
        .replace(/\*\*([^*]+)\*\*/g, '<b>$1</b>')
        .replace(/(^|\s)_([^_\n]+)_(?=\s|$)/g, '$1<i class="text-slate-400">$2</i>')
        .replace(/\[([^\]]+)\]\((\/[^)\s]*)\)/g, '<a href="$2" class="underline font-bold">$1</a>')
        .replace(/\n/g, '<br>');
}

document.querySelectorAll('[data-chatbot]').forEach((bot) => {
    const tombol = bot.querySelector('[data-bot-tombol]');
    const panel = bot.querySelector('[data-bot-panel]');
    const list = bot.querySelector('[data-bot-list]');
    const form = bot.querySelector('[data-bot-form]');
    const input = bot.querySelector('[data-bot-input]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const POS_KEY = 'forecash_bot_pos';
    let menunggu = false;
    const riwayat = [];   // dikirim ke server supaya AI ingat percakapan sebelumnya
    let pos;

    // posisi tombol: dibatasi supaya tetap di layar (dan tidak menutupi tab bar di HP)
    const batasi = (p) => {
        const bawah = window.innerWidth < 1024 ? 100 : 0;
        return {
            x: Math.min(Math.max(8, p.x), window.innerWidth - tombol.offsetWidth - 8),
            y: Math.min(Math.max(8, p.y), window.innerHeight - tombol.offsetHeight - 8 - bawah),
        };
    };
    const pasang = () => {
        tombol.style.left = `${pos.x}px`;
        tombol.style.top = `${pos.y}px`;
        tombol.style.right = tombol.style.bottom = 'auto';
        // panel dibuka di sisi yang masih muat di layar
        const pw = Math.min(384, window.innerWidth - 24), ph = 512;
        const tw = tombol.offsetWidth, th = tombol.offsetHeight;
        panel.style.left = `${Math.min(Math.max(12, pos.x + tw - pw), window.innerWidth - pw - 12)}px`;
        panel.style.top = `${pos.y - ph - 12 > 12 ? pos.y - ph - 12 : Math.min(pos.y + th + 12, window.innerHeight - ph - 12)}px`;
    };
    try { pos = JSON.parse(localStorage.getItem(POS_KEY)); } catch { pos = null; }
    pos = batasi(pos ?? { x: window.innerWidth - tombol.offsetWidth - 24, y: window.innerHeight - tombol.offsetHeight - 32 });
    pasang();
    window.addEventListener('resize', () => { pos = batasi(pos); pasang(); });

    const tambahPesan = (dari, teks) => {
        const baris = document.createElement('div');
        if (dari === 'user') {
            baris.className = 'flex justify-end';
            const isi = document.createElement('div');
            isi.className = 'bg-hsOrange text-white rounded-2xl rounded-br-md px-3 py-2 max-w-[80%]';
            isi.textContent = teks;
            baris.append(isi);
        } else {
            baris.className = 'flex gap-2 items-end';
            baris.innerHTML = `<div class="bg-slate-100 rounded-2xl rounded-bl-md px-3 py-2 max-w-[80%] leading-relaxed">${htmlBot(teks)}</div>`;
        }
        list.append(baris);
        list.scrollTop = list.scrollHeight;
    };

    const toggle = () => {
        panel.hidden = !panel.hidden;
        if (!panel.hidden) {
            if (!list.children.length) tambahPesan('bot', `Halo ${bot.dataset.nama}! Aku ForeCash Assistant. Mau tanya apa soal keuanganmu?`);
            setTimeout(() => input.focus(), 50);
        }
    };

    async function tanya(teks) {
        if (!teks.trim() || menunggu) return;
        tambahPesan('user', teks);
        menunggu = true;
        const tunggu = document.createElement('p');
        tunggu.className = 'text-slate-500 pl-2';
        tunggu.textContent = 'Tupai sedang berpikir...';
        list.append(tunggu);
        try {
            // dikirim sebagai form biasa (bukan JSON): firewall hosting gratis (InfinityFree) memblokir POST berisi JSON
            const res = await fetch(bot.dataset.url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ pesan: teks, riwayat: JSON.stringify(riwayat.slice(-8)) }),
            });
            const j = await res.json();
            tunggu.remove();
            // sumber 'aturan' = AI sedang tidak tersedia, jawaban dari template otomatis
            tambahPesan('bot', (j.jawaban && j.sumber === 'aturan' && bot.dataset.ai === '1' ? '_(AI sedang sibuk, ini jawaban otomatis)_\n' : '') + (j.jawaban ?? j.error ?? j.message ?? 'Terjadi kesalahan.'));
            if (j.jawaban) riwayat.push({ dari: 'user', teks }, { dari: 'bot', teks: j.jawaban });
        } catch {
            tunggu.remove();
            tambahPesan('bot', 'Maaf, aku tidak bisa terhubung ke server. Coba lagi sebentar.');
        } finally {
            menunggu = false;
        }
    }

    // geser tombol; klik tanpa geser = buka/tutup panel
    let drag = null;
    tombol.addEventListener('pointerdown', (e) => {
        drag = { sx: e.clientX, sy: e.clientY, ox: pos.x, oy: pos.y, moved: false };
        tombol.setPointerCapture(e.pointerId);
    });
    tombol.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const dx = e.clientX - drag.sx, dy = e.clientY - drag.sy;
        if (Math.abs(dx) + Math.abs(dy) > 5) drag.moved = true;
        if (drag.moved) { pos = batasi({ x: drag.ox + dx, y: drag.oy + dy }); pasang(); }
    });
    tombol.addEventListener('pointerup', () => {
        const d = drag;
        drag = null;
        if (!d) return;
        if (d.moved) {
            try { localStorage.setItem(POS_KEY, JSON.stringify(pos)); } catch { /* abaikan */ }
        } else {
            toggle();
        }
    });
    // keyboard: Enter/Spasi pada tombol tidak memicu pointer event
    tombol.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });

    bot.querySelector('[data-bot-tutup]').addEventListener('click', toggle);
    bot.querySelectorAll('[data-bot-saran]').forEach((b) => b.addEventListener('click', () => tanya(b.textContent.trim())));
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const teks = input.value;
        input.value = '';
        tanya(teks);
    });
});

// ===== Dialog (kartu pet di Petualangan) =====
// <button data-buka-dialog="#id">, <button data-tutup-dialog>, <dialog data-langsung-buka> terbuka saat halaman dimuat
document.addEventListener('click', (e) => {
    const buka = e.target.closest('[data-buka-dialog]');
    if (buka) document.querySelector(buka.dataset.bukaDialog)?.showModal();
    if (e.target.closest('[data-tutup-dialog]')) e.target.closest('dialog')?.close();
    // klik di luar kotak dialog (backdrop) menutupnya
    if (e.target instanceof HTMLDialogElement) e.target.close();
});
document.querySelectorAll('dialog[data-langsung-buka]').forEach((d) => d.showModal());

// ===== Badge baru =====
// Tingkat badge terakhir disimpan di localStorage per akun. Kunjungan pertama hanya mencatat.
document.querySelectorAll('[data-badge-baru]').forEach((toast) => {
    const kunci = toast.dataset.badgeBaru;
    const sekarang = JSON.parse(toast.dataset.badges || '{}');
    let lama = null;
    try {
        const s = localStorage.getItem(kunci);
        lama = s ? JSON.parse(s) : null;
        localStorage.setItem(kunci, JSON.stringify(sekarang));
    } catch {
        return;
    }
    if (!lama) return;
    const naik = Object.keys(sekarang).filter((k) => sekarang[k] > (lama[k] ?? 0));
    if (!naik.length) return;
    const tpl = toast.querySelector(`template[data-badge="${naik[0]}"]`);
    if (!tpl) return;
    toast.append(tpl.content.cloneNode(true));
    if (naik.length > 1) toast.querySelector('[data-lain]').textContent = `+${naik.length - 1} badge lain. `;
    setTimeout(() => { toast.hidden = false; }, 400);
    toast.querySelector('[data-tutup-toast]')?.addEventListener('click', () => { toast.hidden = true; });
});

// ===== Prediksi: grafik proyeksi saldo 30 hari + simulasi burn rate =====
// Port dari ProyeksiPanel.tsx (recharts) dan proyeksi() di lib/forecast.ts, digambar sebagai SVG biasa.

const tambahHari = (t, n) => {
    const d = new Date(t + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + n);
    return d.toISOString().slice(0, 10);
};
const tglPendek = (t) => { const [, m, d] = t.split('-').map(Number); return `${d}/${m}`; };
const ringkasRupiah = (v) => (v >= 1e6 ? `${(v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 })}jt` : v === 0 ? '0' : `${Math.round(v / 1000)}rb`);

/** Saldo per hari; rencana pengeluaran dipotong pada tanggalnya. */
function proyeksi(saldo, burn, hariIni, rencana, days = 30) {
    const hasil = [];
    for (let i = 0; i <= days; i++) {
        const tanggal = tambahHari(hariIni, i);
        const dibayar = rencana.filter((r) => r.tanggal <= tanggal).reduce((s, r) => s + Number(r.nominal), 0);
        hasil.push({ tanggal, saldo: Math.round(Math.max(0, saldo - dibayar - burn * i)) });
    }
    return hasil;
}

const BLN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const tglSedang = (t) => { const [, m, d] = t.split('-').map(Number); return `${d} ${BLN[m - 1]}`; };

/** Grafik: garis biru = saldo (burn rate asli), oranye putus-putus = simulasi,
 *  area pink = saldo habis sebelum pemasukan, garis hijau = tanggal pemasukan berikutnya. */
function gambarGrafik(el, data, dataSim, gajianIndex) {
    // ukuran mengikuti kotaknya, supaya teks sumbu tidak ikut mengecil/membesar
    const W = Math.max(280, el.clientWidth), H = Math.max(160, el.clientHeight), kiri = 52, bawah = 24, atas = 22, kanan = 10;
    const puncak = Math.max(1, ...data.map((p) => p.saldo), ...(dataSim || []).map((p) => p.saldo));
    // angka sumbu yang bulat: 0, 150rb, 300rb, ...
    const kasar = puncak / 4, pangkat = 10 ** Math.floor(Math.log10(kasar));
    const langkah = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10].map((k) => k * pangkat).find((k) => k >= kasar);
    const maks = langkah * 4;
    const x = (i) => kiri + (i / (data.length - 1)) * (W - kiri - kanan);
    const y = (v) => atas + (1 - v / maks) * (H - atas - bawah);
    const jalur = (d) => d.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p.saldo).toFixed(1)}`).join(' ');
    const garis = jalur(data);
    const isi = `${garis} L${x(data.length - 1)},${H - bawah} L${x(0)},${H - bawah} Z`;
    const teks = (tx, ty, isi, ext = '') => `<text x="${tx}" y="${ty}" font-size="11.5" font-family="Spline Sans, sans-serif" ${ext}>${isi}</text>`;
    const grid = [0, 0.25, 0.5, 0.75, 1].map((r) => {
        const v = maks * r;
        return `<line x1="${kiri}" x2="${W - kanan}" y1="${y(v)}" y2="${y(v)}" stroke="#EFE6D6"/>`
            + teks(kiri - 8, y(v) + 4, ringkasRupiah(v), 'text-anchor="end" fill="#4A4F63"');
    }).join('');
    const tiap = W < 480 ? 10 : 5;
    const labelX = data.map((p, i) => (i % tiap === 0
        ? teks(x(i), H - 5, tglSedang(p.tanggal), `text-anchor="${i === data.length - 1 ? 'end' : 'middle'}" fill="#4A4F63"`) : '')).join('');

    // area "saldo habis": dari hari pertama saldo 0 sampai pemasukan (atau akhir grafik)
    const nol = data.findIndex((p, i) => i > 0 && p.saldo <= 0);
    const ujung = gajianIndex !== null && gajianIndex <= 30 ? gajianIndex : data.length - 1;
    let zona = '';
    if (nol > 0 && nol < ujung) {
        const zx = x(nol), zw = x(ujung) - zx;
        zona = `<rect x="${zx}" y="${atas}" width="${zw}" height="${H - atas - bawah}" rx="6" fill="#FDDDEB" opacity=".7"/>`
            + (zw > 64 ? teks(zx + zw / 2, H - bawah - 8, 'saldo habis', 'text-anchor="middle" font-weight="700" fill="#2B2F42"') : '');
    }
    const gajian = gajianIndex !== null && gajianIndex <= 30
        ? `<line x1="${x(gajianIndex)}" x2="${x(gajianIndex)}" y1="${atas}" y2="${H - bawah}" stroke="#14704F" stroke-width="2" stroke-dasharray="4 4"/>`
          + teks(gajianIndex > 22 ? x(gajianIndex) - 6 : x(gajianIndex) + 6, atas - 8, 'pemasukan',
              `font-weight="700" fill="#14704F" text-anchor="${gajianIndex > 22 ? 'end' : 'start'}"`) : '';
    const sim = dataSim ? `<path d="${jalur(dataSim)}" fill="none" stroke="#FFA934" stroke-width="2.5" stroke-dasharray="6 5" stroke-linecap="round"/>` : '';
    const titik = data.map((p, i) => `<circle cx="${x(i)}" cy="${y(p.saldo)}" r="9" fill="transparent"><title>${tglSedang(p.tanggal)}: Rp ${formatRibuan(p.saldo)}`
        + (dataSim ? ` (simulasi Rp ${formatRibuan(dataSim[i].saldo)})` : '') + '</title></circle>').join('');

    el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" width="100%" height="100%" aria-hidden="true">
        ${grid}${zona}${labelX}${gajian}
        <path d="${isi}" fill="#00A0B5" opacity=".12"/>
        ${sim}
        <path d="${garis}" fill="none" stroke="#00A0B5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${x(0)}" cy="${y(data[0].saldo)}" r="5" fill="#00A0B5" stroke="#fff" stroke-width="2"/>
        ${titik}
    </svg>`;
}

document.querySelectorAll('[data-proyeksi]').forEach((panel) => {
    const d = panel.dataset;
    const saldo = Number(d.saldo), burn = Number(d.burn), rencanaDulu = Number(d.rencanaDulu);
    const sisaHari = d.sisaHari === '' ? null : Number(d.sisaHari);
    const rencana = JSON.parse(d.rencana || '[]');
    const grafik = panel.querySelector('[data-grafik]');
    const input = panel.querySelector('[data-sim-burn]');
    const hasil = panel.querySelector('[data-sim-hasil]');
    const dasar = () => proyeksi(saldo, burn, d.hari, rencana);

    const segarkan = () => {
        const sim = Number(input.value.replace(/\D/g, ''));
        const beda = sim > 0 && Math.round(sim) !== Math.round(burn);
        gambarGrafik(grafik, dasar(), beda ? proyeksi(saldo, sim, d.hari, rencana) : null, sisaHari);
        if (sim > 0) {
            const run = Math.floor(Math.max(0, saldo - rencanaDulu) / sim);
            const aman = sisaHari === null || run >= sisaHari;
            const judul = sisaHari !== null && aman
                ? `Cukup sampai ${tglSedang(tambahHari(d.hari, sisaHari))}`
                : `Cukup sampai ${tglSedang(tambahHari(d.hari, run))}`;
            let kalimat = sisaHari === null ? `Saldo bertahan ${run} hari.`
                : aman ? 'Aman sampai pemasukan berikutnya datang.' : `Masih kurang ${sisaHari - run} hari sebelum pemasukan berikutnya.`;
            if (burn > sim) kalimat += ` Hemat Rp ${formatRibuan(Math.round(burn - sim))} per hari dari sekarang.`;
            hasil.innerHTML = '';
            const b = document.createElement('b');
            b.className = aman ? 'is-aman' : 'is-kurang';
            b.textContent = judul;
            hasil.append(b, document.createTextNode(kalimat));
        } else {
            hasil.textContent = '';
        }
    };
    input.addEventListener('input', segarkan);
    window.addEventListener('resize', segarkan);
    segarkan();
});

// ===== Utang & Patungan =====

// Tombol salin kode (data-salin="KODE")
document.addEventListener('click', async (e) => {
    const tombol = e.target.closest('[data-salin]');
    if (!tombol) return;
    try {
        await navigator.clipboard.writeText(tombol.dataset.salin);
        const label = tombol.querySelector('span');
        if (label) {
            label.textContent = 'Tersalin';
            setTimeout(() => { label.textContent = 'Salin'; }, 1500);
        }
    } catch {
        /* clipboard tidak tersedia: kode tetap terlihat di layar */
    }
});

// Pratinjau split bill: "Rp 50.000 per orang · 3 piutang akan dicatat"
function hitungSplit(form) {
    const hasil = form.querySelector('[data-split-hasil]');
    const total = Number(form.total.value.replace(/\D/g, ''));
    const nama = form.teman.value.split(',').map((s) => s.trim()).filter(Boolean).length;
    const fc = form.querySelectorAll('[name="teman_fc[]"]:checked').length;
    const jumlah = nama + fc;
    const orang = jumlah + (form.termasuk_saya.checked ? 1 : 0);
    if (!total || !jumlah) { hasil.hidden = true; return; }
    hasil.hidden = false;
    hasil.textContent = `Rp ${formatRibuan(Math.round(total / orang))} per orang · ${jumlah} piutang akan dicatat`
        + (fc ? `, ${fc} tagihan dikirim` : '');
}
document.querySelectorAll('[data-split]').forEach((form) => {
    form.addEventListener('input', () => hitungSplit(form));
    form.addEventListener('change', () => hitungSplit(form));
    hitungSplit(form);
});

// Catat utang: kalau memilih teman ForeCash, kolom nama disembunyikan & hanya boleh "piutang"
function aturTeman(form) {
    const pilih = form.querySelector('[data-pilih-teman]');
    const fc = !!pilih?.value;
    form.querySelector('[data-nama-teman]')?.toggleAttribute('hidden', fc);
    form.querySelector('[data-info-teman]')?.toggleAttribute('hidden', !fc);
    const opsiUtang = form.querySelector('[data-bukan-teman]');
    if (opsiUtang) {
        opsiUtang.hidden = fc;
        opsiUtang.disabled = fc;
        if (fc) form.arah.value = 'piutang';
    }
}
document.querySelectorAll('[data-catat-utang]').forEach((form) => {
    form.addEventListener('change', (e) => { if (e.target.matches('[data-pilih-teman]')) aturTeman(form); });
    aturTeman(form);
});

// Sebelum form dikirim:
// 1. form ber-atribut data-konfirmasi="..." (misal tombol hapus) minta konfirmasi dulu.
// 2. nominal >= Rp5.000.000 di kolom rupiah mana pun minta konfirmasi,
//    supaya salah ketik (kelebihan nol) tidak langsung tersimpan.
const BATAS_KONFIRMASI = 5_000_000;
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.konfirmasi && !confirm(form.dataset.konfirmasi)) {
        e.preventDefault();
        return;
    }
    for (const input of form.querySelectorAll('[data-rupiah]')) {
        const n = Number(input.value.replace(/\D/g, ''));
        if (n >= BATAS_KONFIRMASI &&
            !confirm(`Yakin nominalnya Rp ${formatRibuan(n)}?\n\nPeriksa lagi jumlah angka nol sebelum menyimpan.`)) {
            e.preventDefault();
            input.focus();
            return;
        }
    }
});


// ===== Scan struk: Gemini dulu, cadangannya tesseract.js di browser =====
// Pakai di Blade: tombol [data-scan-struk] di dalam form [data-form-tx] yang punya data-scan-url.
// Hasil hanya MENGISI form (nominal, tanggal, keterangan, kategori); user mengecek lalu menekan Simpan.
// Foto yang di-scan juga dipasang ke kolom "bukti" supaya ikut tersimpan sebagai foto struk.
const SCAN_SISI_MAKS = 1600;
const SCAN_STRUK_MAKS = 2 * 1024 * 1024;
const TESSERACT_CDN = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
// nama kategori dari parser cadangan -> kata kunci untuk mencari kategori milik user
const KATA_KATEGORI = { 'Makan & Minum': ['makan', 'minum', 'food'], 'Transportasi': ['transport', 'ojek'], 'Kebutuhan Kuliah': ['kuliah', 'atk', 'fotokopi'], 'Hiburan': ['hiburan'], 'Kos / Sewa': ['kos', 'sewa'] };

function muatGambarScan(file) {
    return new Promise((ok, gagal) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); ok(img); };
        img.onerror = () => { URL.revokeObjectURL(url); gagal(new Error('gambar')); };
        img.src = url;
    });
}
function kanvasScan(img) {
    const skala = Math.min(1, SCAN_SISI_MAKS / Math.max(img.naturalWidth, img.naturalHeight));
    const c = document.createElement('canvas');
    c.width = Math.max(1, Math.round(img.naturalWidth * skala));
    c.height = Math.max(1, Math.round(img.naturalHeight * skala));
    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
    return c;
}
async function kanvasKeJpeg(c) {
    for (const q of [0.82, 0.7, 0.55, 0.4]) {
        const blob = await new Promise((ok) => c.toBlob(ok, 'image/jpeg', q));
        if (blob && blob.size <= SCAN_STRUK_MAKS) return new File([blob], 'struk.jpg', { type: 'image/jpeg' });
    }
    return null;
}
/** abu-abu + regangkan kontras supaya struk thermal yang pudar lebih mudah dibaca OCR */
function kanvasUntukOcr(sumber) {
    const c = document.createElement('canvas');
    c.width = sumber.width; c.height = sumber.height;
    const ctx = c.getContext('2d');
    ctx.drawImage(sumber, 0, 0);
    const data = ctx.getImageData(0, 0, c.width, c.height), px = data.data, hist = new Uint32Array(256);
    for (let i = 0; i < px.length; i += 4) { const g = Math.round(0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]); px[i] = g; hist[g]++; }
    const total = px.length / 4;
    let lo = 0, hi = 255, acc = 0;
    for (let g = 0; g < 256; g++) { acc += hist[g]; if (acc >= total * 0.02) { lo = g; break; } }
    acc = 0;
    for (let g = 255; g >= 0; g--) { acc += hist[g]; if (acc >= total * 0.02) { hi = g; break; } }
    const r = Math.max(1, hi - lo);
    for (let i = 0; i < px.length; i += 4) { const g = Math.max(0, Math.min(255, ((px[i] - lo) * 255) / r)); px[i] = px[i + 1] = px[i + 2] = g; }
    ctx.putImageData(data, 0, 0);
    return c;
}
function muatTesseract() {
    if (window.Tesseract) return Promise.resolve(window.Tesseract);
    return new Promise((ok, gagal) => {
        const s = document.createElement('script');
        s.src = TESSERACT_CDN; s.async = true;
        s.onload = () => (window.Tesseract ? ok(window.Tesseract) : gagal(new Error('tesseract')));
        s.onerror = () => gagal(new Error('tesseract'));
        document.head.appendChild(s);
    });
}
async function scanCadangan(kanvas, lapor) {
    const T = await muatTesseract();
    const worker = await T.createWorker('eng', 1, {
        logger: (m) => { if (m.status === 'recognizing text') lapor(`Membaca struk (mode cadangan)... ${Math.round(m.progress * 100)}%`); },
    });
    try {
        const teks = (await worker.recognize(kanvasUntukOcr(kanvas))).data.text;
        const h = bacaTeksStruk(teks);
        return { ok: h.nominal != null, sumber: 'ocr', yakin: h.yakin, nominal: h.nominal, tanggal: h.tanggal, toko: h.toko, kategori: h.kategori };
    } finally {
        await worker.terminate();
    }
}
function pilihKategori(select, nama) {
    if (!select || !nama) return false;
    const opsi = [...select.querySelectorAll('optgroup[data-jenis="pengeluaran"] option')];
    const cari = nama.toLowerCase();
    let cocok = opsi.find((o) => o.textContent.trim().toLowerCase() === cari);
    if (!cocok) {
        const kunci = KATA_KATEGORI[nama] ?? [cari];
        cocok = opsi.find((o) => kunci.some((k) => o.textContent.toLowerCase().includes(k)));
    }
    if (cocok) select.value = cocok.value;
    return !!cocok;
}
function isiDariStruk(form, h) {
    const jenis = form.querySelector('[data-pilih-jenis]');
    if (jenis && jenis.value !== 'pengeluaran') { jenis.value = 'pengeluaran'; jenis.dispatchEvent(new Event('change', { bubbles: true })); }
    const nominal = form.querySelector('[name="nominal"]');
    if (nominal && h.nominal) nominal.value = formatRibuan(Math.round(h.nominal));
    const tanggal = form.querySelector('[name="tanggal"]');
    if (tanggal && h.tanggal && (!tanggal.max || h.tanggal <= tanggal.max)) tanggal.value = h.tanggal;
    const ket = form.querySelector('[name="keterangan"]');
    if (ket && h.toko && !ket.value.trim()) ket.value = h.toko;
    pilihKategori(form.querySelector('[data-pilih-kategori]'), h.kategori);
}
const rupiahTeks = (n) => 'Rp ' + formatRibuan(Math.round(n));

document.querySelectorAll('[data-form-tx][data-scan-url]').forEach((form) => {
    const tombol = form.querySelector('[data-scan-struk]');
    const pilih = form.querySelector('[data-scan-input]');
    const hasil = form.querySelector('[data-scan-hasil]');
    const bukti = form.querySelector('[name="bukti"]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!tombol || !pilih || !hasil) return;

    const tampil = (jenis, teks) => { hasil.hidden = false; hasil.dataset.jenis = jenis; hasil.textContent = teks; };
    tombol.addEventListener('click', () => pilih.click());

    pilih.addEventListener('change', async () => {
        const file = pilih.files?.[0];
        pilih.value = '';
        if (!file) return;
        if (!file.type.startsWith('image/')) return tampil('gagal', 'File harus berupa foto (JPG, PNG, atau WEBP).');
        tombol.disabled = true;
        try {
            tampil('proses', 'Menyiapkan foto...');
            const kanvas = kanvasScan(await muatGambarScan(file));
            const jpeg = await kanvasKeJpeg(kanvas);
            if (!jpeg) return tampil('gagal', 'Foto terlalu besar. Coba foto yang lebih kecil.');
            // pasang foto ke kolom bukti supaya ikut tersimpan sebagai foto struk
            if (bukti) { const dt = new DataTransfer(); dt.items.add(jpeg); bukti.files = dt.files; }

            // 1) Gemini
            let h = null;
            try {
                tampil('proses', 'Membaca struk dengan AI...');
                const fd = new FormData(); fd.append('foto', jpeg);
                const res = await fetch(form.dataset.scanUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
                if (res.ok) h = await res.json();
            } catch { h = null; }

            // 2) cadangan: tesseract.js di browser
            if (!h?.ok) {
                tampil('proses', 'AI sedang tidak bisa dipakai, membaca struk dengan mode cadangan...');
                try { h = await scanCadangan(kanvas, (t) => tampil('proses', t)); } catch { h = null; }
            }

            if (!h?.ok) return tampil('gagal', 'Total belanja tidak terbaca. Foto tetap terlampir; isi nominalnya manual ya. Tips: foto lurus, dekat, dan terang.');
            isiDariStruk(form, h);
            const bagian = [h.toko, rupiahTeks(h.nominal), h.tanggal ? h.tanggal.split('-').reverse().join('/') : null].filter(Boolean).join(' · ');
            const cek = h.sumber === 'ocr' && h.yakin !== 'tinggi' ? ' Totalnya hanya tebakan, cek lagi ya.' : ' Cek lagi sebelum disimpan.';
            tampil('ok', `Ketemu: ${bagian}.${cek}`);
            form.querySelector('[name="nominal"]')?.focus();
        } catch {
            tampil('gagal', 'Struk gagal dibaca. Foto tetap terlampir; isi manual ya.');
        } finally {
            tombol.disabled = false;
        }
    });
});


// ===== Teman ForeCash: QR undangan & tombol bagikan link =====
// QR digambar di browser dari link undangan (tidak lewat layanan QR pihak ketiga).
document.querySelectorAll('canvas[data-qr]').forEach((kanvas) => {
    QRCode.toCanvas(kanvas, kanvas.dataset.qr, { width: 104, margin: 1, color: { dark: '#2B2F42', light: '#FFFFFF' } })
        .catch(() => { kanvas.hidden = true; });
});
// Bagikan: menu bagikan bawaan HP (Web Share API); di laptop pakai link WhatsApp
document.addEventListener('click', async (e) => {
    const tombol = e.target.closest('[data-bagikan]');
    if (!tombol) return;
    const url = tombol.dataset.bagikan;
    const teks = tombol.dataset.teks ?? '';
    if (navigator.share) {
        try { await navigator.share({ title: 'Undangan teman ForeCash', text: teks, url }); return; }
        catch (err) { if (err?.name === 'AbortError') return; }
    }
    window.open(`https://wa.me/?text=${encodeURIComponent(teks + url)}`, '_blank', 'noopener');
});
