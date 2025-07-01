<?php

namespace App\Services;

use App\Models\Watch;
use Illuminate\Support\Facades\Log;

class WatchImageService
{
    protected GoogleSearchService $googleSearch;
    private string $placeholder = 'https://static.watchpatrol.net/static/explorer/img/no_watch_placeholder.8793368e62ea.png';
    private bool $limitExceeded = false;

    public function __construct(GoogleSearchService $googleSearch)
    {
        $this->googleSearch = $googleSearch;
    }

    public function fetchAndUpdateImage(Watch $watch): array
    {
        if ($this->limitExceeded) {
            return ['image' => null, 'limitExceeded' => true];
        }

        try {
            $image = $this->googleSearch->searchImage("{$watch->brand} {$watch->model}");
            $watch->image_url = $image;
            $watch->save();
            return ['image' => $image, 'limitExceeded' => false];
        } catch (\Throwable $e) {
            if ($e->getCode() === 429) {
                $this->limitExceeded = true;
            }
            Log::warning("Image search failed for {$watch->brand} {$watch->model}: {$e->getMessage()}");
            return ['image' => null, 'limitExceeded' => $this->limitExceeded];
        }
    }

    public function safeSearchImage(string $query): array
    {
        if ($this->limitExceeded) {
            return ['image' => null, 'limitExceeded' => true];
        }

        try {
            return ['image' => $this->googleSearch->searchImage($query), 'limitExceeded' => false];
        } catch (\Throwable $e) {
            if ($e->getCode() === 429) {
                $this->limitExceeded = true;
            }
            return ['image' => null, 'limitExceeded' => $this->limitExceeded];
        }
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }
}
