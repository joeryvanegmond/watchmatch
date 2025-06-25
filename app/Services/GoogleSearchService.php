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
            'q' => $query . ' watch front view single dial visible, professional photo',
            'num' => 1,
            'searchType' => 'image',
            'imgType' => 'photo',
            'imgSize' => 'large',
            'imgColorType' => 'color',
        ])->json();

        return $response['items'][0]['link'];
    }
}

