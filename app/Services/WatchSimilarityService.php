<?php

namespace App\Services;

use App\Models\Watch;
use Illuminate\Support\Facades\DB;

class WatchSimilarityService
{
    protected WatchImageService $imageService;
    protected WatchUrlService $urlService;

    public function __construct(WatchImageService $imageService, WatchUrlService $urlService)
    {
        $this->imageService = $imageService;
        $this->urlService = $urlService;
    }

    public function getSimilarWatches(Watch $watch)
    {
        return $watch->similarWatches()->get();
    }

    public function processAndStoreSimilarities($results, Watch $original)
    {
        $watches = collect();
        $info = null;

        foreach ($results as $item) {
            $variant = $item->variant ?? '';
            $imageResult = $this->imageService->safeSearchImage("{$item->brand} {$item->model} {$variant}");
            if ($imageResult['limitExceeded']) {
                $info = "Niet alle afbeeldingen zijn beschikbaar.";
            }

            $watch = Watch::updateOrCreate(
                [
                    'brand' => strtolower($item->brand),
                    'model' => strtolower($item->model),
                    'variant' => strtolower($item->variant ?? ''),
                ],
                [
                    'image_url' => $imageResult['image'],
                    'price' => $item->price ?? 0,
                    'url' => $this->urlService->create($item->brand, $item->model, $item->variant ?? ''),
                ]
            );

            $watch->image_url ??= $this->imageService->getPlaceholder();
            $watches->push($watch);

            $original->similarWatches()->syncWithoutDetaching([$watch->id => ['link_strength' => 0.1]]);
        }

        return ['watches' => $watches, 'info' => $info];
    }

    public function linkWatches(int $originalId, int $matchId)
    {
        $exists = DB::table('watch_similarities')
            ->where('watch_id', $originalId)
            ->where('similar_watch_id', $matchId)
            ->exists();

        if ($exists) {
            DB::table('watch_similarities')
                ->where('watch_id', $originalId)
                ->where('similar_watch_id', $matchId)
                ->increment('link_strength', 0.1);
        } else {
            Watch::findOrFail($originalId)->similarWatches()->attach($matchId, ['link_strength' => 0.1]);
        }
    }
}
