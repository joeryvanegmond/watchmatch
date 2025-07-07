<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class DuckDuckGoService
{
    public function getFirstDuckDuckGoImage(string $query): array
    {
        $vqd = $this->getVqdToken($query);
        if (!$vqd) {
            throw new \Exception('Kon vqd token niet vinden');
        }
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            'Referer' => 'https://duckduckgo.com/'
        ])->get('https://duckduckgo.com/i.js', [
            'q' => $query,
            'vqd' => $vqd
        ])->json();

        return [
            'image' => $response['results'][0]['image'] ?? null,
            'url' => $response['results'][0]['url'] ?? null
        ];
    }

    private function getVqdToken(string $query): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0'
        ])->get('https://duckduckgo.com/', [
            'q' => $query
        ])->body();
        if (preg_match('/vqd=([0-9\-]+)/', $response, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
