<?php

namespace App\Services;

class WatchUrlService
{
    public function create(string $brand, string $model, ?string $variant = null): string
    {
        $query = urlencode("{$brand} {$model} {$variant}");
        return "https://www.chrono24.nl/search/index.htm?dosearch=true&watchTypes=U&searchexplain=false&query={$query}";
    }
}
