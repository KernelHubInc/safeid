<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GroqVoiceReadoutService
{
    public function generate(array $aiSummary, string $token): array
    {
        $apiKey = config('services.groq.key');
        $model = config('services.groq.tts_model', 'canopylabs/orpheus-v1-english');
        $voice = config('services.groq.tts_voice', 'troy');

        if (!$apiKey) {
            throw new RuntimeException('Groq API key is missing.');
        }

        $text = $this->buildSpeechText($aiSummary);

        $hash = md5($token . '|' . $text . '|' . $model . '|' . $voice);
        $relativePath = "ai-voice/{$hash}.wav";

        if (Storage::disk('public')->exists($relativePath)) {
            return [
                'text' => $text,
                'path' => $relativePath,
                'url' => Storage::disk('public')->url($relativePath),
                'cached' => true,
            ];
        }

        $response = Http::timeout(45)
            ->withToken($apiKey)
            ->accept('audio/wav')
            ->post('https://api.groq.com/openai/v1/audio/speech', [
                'model' => $model,
                'voice' => $voice,
                'input' => $text,
                'response_format' => 'wav',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Groq TTS failed: ' . $response->body());
        }

        Storage::disk('public')->put($relativePath, $response->body());

        return [
            'text' => $text,
            'path' => $relativePath,
            'url' => Storage::disk('public')->url($relativePath),
            'cached' => false,
        ];
    }

    protected function buildSpeechText(array $aiSummary): string
    {
        // $summary = trim((string) ($aiSummary['summary'] ?? ''));
        $summary = collect($aiSummary['summary'] ?? [])
            ->filter(fn ($item) => filled($item))
            ->map(fn ($item) => trim((string) $item))
            ->values()
            ->all();
        $alerts = collect($aiSummary['alerts'] ?? [])
            ->filter(fn ($item) => filled($item))
            ->map(fn ($item) => trim((string) $item))
            ->values()
            ->all();

        $parts = [];

        // if ($summary !== '') {
        //     $parts[] = "Emergency summary. {$summary}";
        // }

        if (! empty($summary)) {
            $parts[] = 'Emergency summary. ' . implode('. ', $summary) . '.';
        }

        if (! empty($alerts)) {
            $parts[] = 'Alerts. ' . implode('. ', $alerts) . '.';
        }

        $text = trim(implode(' ', $parts));

        // Groq Orpheus docs: max 200 chars input
        $text = preg_replace('/\s+/', ' ', $text);
        $text = Str::limit($text, 200, '...');

        // Optional vocal direction supported by Orpheus English
        return '[calm] ' . $text;
    }
}