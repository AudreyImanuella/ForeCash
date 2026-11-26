<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pemanggil Gemini API (endpoint generateContent).
 * Mengembalikan teks jawaban, atau null kalau key belum diisi / kuota habis / server error,
 * supaya pemanggil bisa memakai jawaban cadangan.
 */
class Gemini
{
    public static function aktif(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * @param  string  $instruksi  instruksi sistem (peran, aturan, dan data keuangan user)
     * @param  array<int, array{dari: string, teks: string}>  $riwayat  percakapan sebelumnya ('user' / 'bot')
     */
    public static function jawab(string $instruksi, array $riwayat, string $pesan): ?string
    {
        if (! self::aktif()) {
            return null;
        }

        $contents = [];
        foreach ($riwayat as $r) {
            $contents[] = ['role' => $r['dari'] === 'user' ? 'user' : 'model', 'parts' => [['text' => $r['teks']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $pesan]]];

        // GEMINI_MODEL boleh berisi beberapa model dipisah koma: kalau model pertama sedang penuh (503),
        // kuotanya habis (429), atau tidak ada (404), model berikutnya dicoba.
        $models = array_filter(array_map('trim', explode(',', (string) config('services.gemini.model'))));
        $body = [
            'system_instruction' => ['parts' => [['text' => $instruksi]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.6, 'maxOutputTokens' => 800],
        ];

        foreach ($models as $model) {
            try {
                $res = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->timeout(config('services.gemini.timeout'))
                    ->acceptJson()
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $body);
            } catch (Throwable $e) {
                Log::warning("Gemini ({$model}) tidak bisa dihubungi: " . $e->getMessage());

                return null;   // masalah jaringan: model lain juga tidak akan bisa
            }

            if ($res->successful()) {
                $teks = trim(collect($res->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode(''));

                return $teks !== '' ? $teks : null;
            }

            // pesan error Google dicatat tanpa API key
            Log::warning("Gemini ({$model}) gagal (HTTP {$res->status()}): " . mb_substr((string) $res->json('error.message', $res->body()), 0, 300));
            if (! in_array($res->status(), [404, 429, 500, 503], true)) {
                return null;   // 400/403 = key atau permintaan salah: model lain juga akan ditolak
            }
        }

        return null;
    }
}
