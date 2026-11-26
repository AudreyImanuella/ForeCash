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

function gambarGrafik(el, data, gajianIndex) {
    // ukuran mengikuti kotaknya, supaya teks sumbu tidak ikut mengecil/membesar
    const W = Math.max(280, el.clientWidth), H = Math.max(160, el.clientHeight), kiri = 48, bawah = 22, atas = 8, kanan = 8;
    const maks = Math.max(1, ...data.map((p) => p.saldo));
    const x = (i) => kiri + (i / (data.length - 1)) * (W - kiri - kanan);
    const y = (v) => atas + (1 - v / maks) * (H - atas - bawah);
    const garis = data.map((p, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(p.saldo).toFixed(1)}`).join(' ');
    const isi = `${garis} L${x(data.length - 1)},${H - bawah} L${x(0)},${H - bawah} Z`;
    const grid = [0, 0.25, 0.5, 0.75, 1].map((r) => {
        const v = maks * r;
        return `<line x1="${kiri}" x2="${W - kanan}" y1="${y(v)}" y2="${y(v)}" stroke="#F6EBCF"/>`
            + `<text x="${kiri - 6}" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#8C7A63">${ringkasRupiah(v)}</text>`;
    }).join('');
    const tiap = W < 480 ? 10 : 5;
    const labelX = data.map((p, i) => (i % tiap === 0
        ? `<text x="${x(i)}" y="${H - 4}" text-anchor="middle" font-size="11" fill="#8C7A63">${tglPendek(p.tanggal)}</text>` : '')).join('');
    const gajian = gajianIndex !== null && gajianIndex <= 30
        ? `<line x1="${x(gajianIndex)}" x2="${x(gajianIndex)}" y1="${atas}" y2="${H - bawah}" stroke="#E8A500" stroke-width="3"/>`
          + `<text x="${x(gajianIndex) - 4}" y="${atas + 12}" text-anchor="end" font-size="11" font-weight="700" fill="#2D2216">Pemasukan</text>` : '';
    const titik = data.map((p, i) => `<circle cx="${x(i)}" cy="${y(p.saldo)}" r="8" fill="transparent"><title>${tglPendek(p.tanggal)}: Rp ${formatRibuan(p.saldo)}</title></circle>`).join('');

    el.innerHTML = `<svg viewBox="0 0 ${W} ${H}" width="100%" height="100%" aria-hidden="true">
        <defs><linearGradient id="isiSaldo" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#FF8A1F" stop-opacity=".22"/><stop offset="100%" stop-color="#FF8A1F" stop-opacity=".02"/>
        </linearGradient></defs>
        ${grid}${labelX}${gajian}
        <path d="${isi}" fill="url(#isiSaldo)"/>
        <path d="${garis}" fill="none" stroke="#FF8A1F" stroke-width="2.5"/>
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

    const segarkan = () => {
        const sim = Number(input.value.replace(/\D/g, ''));
        gambarGrafik(grafik, proyeksi(saldo, sim > 0 ? sim : burn, d.hari, rencana), sisaHari);
        if (sim > 0) {
            const run = Math.floor(Math.max(0, saldo - rencanaDulu) / sim);
            hasil.textContent = `Saldo bertahan ${run} hari` + (sisaHari !== null
                ? (run >= sisaHari ? ' — aman sampai pemasukan berikutnya.' : ` — masih kurang ${sisaHari - run} hari.`) : '.');
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