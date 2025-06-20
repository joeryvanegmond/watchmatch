<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleSearchService
{
    public function searchImage($query)
    {
        $response = Http::get('https://www.googleapis.com/customsearch/v1', [
            'key' => config('services.google.api_key'),
            'cx' => config('services.google.cx'),
            'q' => 'watch face ' . $query . ' Link bracelet',
            'num' => 1,
            'searchType' => 'image',
        ])->json();

        return $response['items'][0]['link'];
    }
}

