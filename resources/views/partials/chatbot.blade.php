{{-- ForeCash Assistant: tombol maskot yang bisa digeser + panel chat (port dari components/Chatbot.tsx).
     Logikanya di resources/js/app.js (bagian "Chatbot"). Jawaban diambil dari route('chatbot'). --}}
<div data-chatbot data-url="{{ route('chatbot') }}" data-nama="{{ auth()->user()->namaDepan() }}" data-ai="{{ \App\Services\Gemini::aktif() ? 1 : 0 }}">
    {{-- tombol pembuka: pil navy berwajah tupai + label, supaya jelas ini asisten AI. Bisa digeser. --}}
    <button type="button" id="fcBotBtn" data-bot-tombol aria-label="Buka ForeCash Assistant" title="Tanya ForeCash Assistant (bisa digeser)"
            class="fc-bot-launch fixed z-[90]" style="right: 24px; bottom: 32px">
        <span class="fc-bot-face pointer-events-none"><x-tupai pose="senang" /></span>
        <span class="fc-bot-text pointer-events-none"><b>Tanya Asisten</b><small>AI keuanganmu</small></span>
        <span class="fc-bot-chat pointer-events-none"><x-ikon nama="message-circle" :size="18" :tebal="2.4" /></span>
    </button>

    <div data-bot-panel hidden role="dialog" aria-label="ForeCash Assistant"
         class="fixed z-[91] w-[24rem] max-w-[calc(100vw-1.5rem)] hs-card flex flex-col overflow-hidden" style="height: 32rem">
        <div class="flex items-center gap-3 px-4 py-3 bg-hsYellow">
            <span class="fc-bot-face" style="width: 44px; height: 44px"><x-tupai pose="senang" /></span>
            <div class="flex-1">
                <p class="font-black text-base flex items-center gap-2">ForeCash Assistant <span class="fc-bot-ai">AI</span></p>
                <p class="text-xs font-semibold text-hsDark/70">Tanya apa saja soal uangmu, aku jawab dari datamu</p>
            </div>
            <button type="button" data-bot-tutup class="w-8 h-8 rounded-full bg-white/70 font-bold inline-flex items-center justify-center" aria-label="Tutup">
                <x-ikon nama="x" :size="16" />
            </button>
        </div>
        <div data-bot-list class="flex-1 overflow-y-auto p-4 space-y-3 text-sm" aria-live="polite"></div>
        <div class="flex flex-wrap gap-1.5 px-3 pb-2">
            @foreach (['Kapan uangku habis?', 'Tips hemat', 'Cek saldo', 'Utang & piutang', 'Level aku'] as $s)
                <button type="button" data-bot-saran class="bg-slate-100 hover:bg-hsYellow rounded-full px-3 py-1.5 text-xs font-bold">{{ $s }}</button>
            @endforeach
        </div>
        <form data-bot-form class="flex gap-2 p-3 border-t">
            <input data-bot-input maxlength="500" placeholder="Tanya soal keuanganmu..." aria-label="Pertanyaan" class="flex-1 min-w-0 px-3 py-2.5 text-sm border rounded-xl" autocomplete="off">
            <button type="submit" class="hs-btn text-white px-4 py-2 text-sm" style="background: #262A3B">Kirim</button>
        </form>
    </div>
</div>
