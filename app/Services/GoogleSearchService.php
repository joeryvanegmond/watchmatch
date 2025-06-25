<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use PHPUnit\Event\Code\Throwable;

class GoogleSearchService
{
    public function searchImage(string $query): ?string
    {
        $response = Http::get('https://www.googleapis.com/customsearch/v1', [
            'key'            => config('services.google.api_key'),
            'cx'             => config('services.google.cx'),
            'q'              => $query . ' watch front view single dial visible, professional photo',
            'num'            => 1,
            'searchType'     => 'image',
            'imgType'        => 'photo',
            'imgSize'        => 'large',
            'imgColorType'   => 'color',
        ])->json();
    
        // Controleer op error
        if (isset($response['error'])) {
            if ($response['error']['code'] == 429) {
                throw new \RuntimeException('Google Search API quota exceeded: ' . $response['error']['message']);
            }
            throw new \RuntimeException('Google Search API error: ' . $response['error']['message']);
        }
    
        return $response['items'][0]['link'] ?? null;
    }
}

