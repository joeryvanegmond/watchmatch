<?php

namespace App\Services;

use App\Models\Watch;
use Illuminate\Support\Facades\Log;

class WatchImageService
{
    protected GoogleSearchService $googleSearch;
    protected DuckDuckGoService $quack;
    private string $placeholder = 'https://static.watchpatrol.net/static/explorer/img/no_watch_placeholder.8793368e62ea.png';
    private bool $limitExceeded = false;

    public function __construct(GoogleSearchService $googleSearch, DuckDuckGoService $quack)
    {
        $this->googleSearch = $googleSearch;
        $this->quack = $quack;
    }

    public function fetchAndUpdateImage(Watch $watch): bool
    {
        if ($this->limitExceeded) {
            return true;
        }
        
        try {
            $variant = $watch->variant ?? '';
            // $image = $this->googleSearch->searchImage("{$watch->brand} {$watch->model} {$variant}");
            $result = $this->quack->getFirstDuckDuckGoImage("{$watch->brand} {$watch->model} {$variant}");
            if ($result['image'] != null) {
                $watch->image_url = $result['image'];
            }
            if ($result['url'] != null) {
                $watch->url = $result['url'];
            }
            $watch->save();
            
            return false;
        } catch (\Throwable $e) {
            if ($e->getCode() === 429) {
                $this->limitExceeded = true;
            }
            Log::warning("Image search failed for {$watch->brand} {$watch->model}: {$e->getMessage()}");
            return $this->limitExceeded;
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
