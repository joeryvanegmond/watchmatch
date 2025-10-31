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
            $variant = $watch->variant;

            $query = $this->sanitizeString("{$watch->brand} {$watch->model} {$variant} watch");


            $result = $this->quack->getFirstDuckDuckGoImage($query);

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
            $watch->delete();
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

    private function sanitizeString($query): string
    {
        // Verwijder quotes en trim spaties
        $query = trim($query, "\"' ");

        // Zet accenten om (é -> e, ü -> u, etc.)
        $query = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $query);

        // Vervang spaties en ongeldige tekens door underscores

        // Meerdere underscores samenvoegen tot één
        $query = preg_replace('/_+/', '_', $query);

        // Kleine letters voor consistentie
        $query = strtolower($query);

        // Eventueel trailing underscores verwijderen
        $query = trim($query, '_');

        return $query;
    }
}
