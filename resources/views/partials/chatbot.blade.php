{{-- ForeCash Assistant: tombol maskot yang bisa digeser + panel chat (port dari components/Chatbot.tsx).
     Logikanya di resources/js/app.js (bagian "Chatbot"). Jawaban diambil dari route('chatbot'). --}}
<div data-chatbot data-url="{{ route('chatbot') }}" data-nama="{{ auth()->user()->namaDepan() }}" data-ai="{{ \App\Services\Gemini::aktif() ? 1 : 0 }}">
    <button type="button" id="fcBotBtn" data-bot-tombol aria-label="Buka ForeCash Assistant" title="ForeCash Assistant (bisa digeser)"
            class="fixed z-[90] w-16 h-16 flex items-center justify-center cursor-grab touch-none select-none bg-transparent" style="right: 24px; bottom: 32px">
        <x-tupai pose="koin" class="fc-bot-logo w-16 h-16 pointer-events-none" />
    </button>

    <div data-bot-panel hidden role="dialog" aria-label="ForeCash Assistant"
         class="fixed z-[91] w-[22rem] max-w-[calc(100vw-1.5rem)] hs-card flex flex-col overflow-hidden" style="height: 28rem">
        <div class="flex items-center gap-3 px-4 py-3 bg-hsYellow">
            <x-tupai pose="koin" class="w-10 h-10 shrink-0" />
            <div class="flex-1">
                <p class="font-black text-sm">ForeCash Assistant</p>
                <p class="text-[10px] font-bold text-hsDark/70">Menjawab dari data keuanganmu</p>
            </div>
            <button type="button" data-bot-tutup class="w-8 h-8 rounded-full bg-white/70 font-bold inline-flex items-center justify-center" aria-label="Tutup">
                <x-ikon nama="x" :size="16" />
            </button>
        </div>
        <div data-bot-list class="flex-1 overflow-y-auto p-3 space-y-2 text-xs" aria-live="polite"></div>
        <div class="flex flex-wrap gap-1.5 px-3 pb-2">
            @foreach (['Kapan uangku habis?', 'Tips hemat', 'Cek saldo', 'Utang & piutang', 'Level aku'] as $s)
                <button type="button" data-bot-saran class="bg-slate-100 hover:bg-hsYellow rounded-full px-2.5 py-1 text-[11px] font-bold">{{ $s }}</button>
            @endforeach
        </div>
        <form data-bot-form class="flex gap-2 p-3 border-t">
            <input data-bot-input maxlength="500" placeholder="Tanya soal keuanganmu..." aria-label="Pertanyaan" class="flex-1 min-w-0 px-3 py-2 text-xs border" autocomplete="off">
            <button type="submit" class="hs-btn bg-hsOrange text-white px-4 py-2 text-xs">Kirim</button>
        </form>
    </div>
</div>
