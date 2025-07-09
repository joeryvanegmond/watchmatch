<?php

namespace App\Services;

use App\Models\BlacklistedDomain;
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

        $headers = $this->getRandomHeaders();

        $response = Http::withHeaders($headers)->get('https://duckduckgo.com/i.js', [
            'q' => $query,
            'vqd' => $vqd
        ])->json();
        $image_url = $this->getAllowedImageUrl($response);

        return [
            'image' => $this->cleanUrl($image_url),
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

    private function getRandomHeaders(): array
    {
        $userAgents = [
            // Chrome op Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            // Firefox op Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
            // Safari op macOS
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15',
            // Chrome op Android
            'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Mobile Safari/537.36',
            // Safari op iPhone
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
        ];

        $referers = [
            'https://duckduckgo.com/',
            'https://www.google.com/',
            'https://www.bing.com/',
            'https://search.yahoo.com/',
            'https://www.ecosia.org/'
        ];

        return [
            'User-Agent' => $userAgents[array_rand($userAgents)],
            'Referer' => $referers[array_rand($referers)],
        ];
    }

    public function getAllowedImageUrl(array $response): ?string
    {
        if (!isset($response['results']) || !is_array($response['results'])) {
            return null;
        }

        // Alle blacklisted domains ophalen (kan je cachen)
        $blacklistedDomains = BlacklistedDomain::pluck('domain')->toArray();

        foreach ($response['results'] as $result) {
            if (!isset($result['image'])) {
                continue;
            }

            $imageUrl = $result['image'];
            $host = parse_url($imageUrl, PHP_URL_HOST);

            if ($host && !in_array($host, $blacklistedDomains)) {
                return $imageUrl;
            }
        }

        return null; // Geen bruikbare url gevonden
    }

    private function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
    }
}
