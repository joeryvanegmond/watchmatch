<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use App\Services\ImageKitService;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    private WatchSimilarityService $similarityService;
    private WatchImageService $imageService;
    private SerpApiService $searchService;
    private ImageKitService $imageKitService;

    public function __construct(
        SerpApiService $searchService,
        WatchImageService $imageService,
        WatchSimilarityService $similarityService,
        ImageKitService $imageKitService
    ) {
        $this->imageService = $imageService;
        $this->similarityService = $similarityService;
        $this->searchService = $searchService;
        $this->imageKitService = $imageKitService;
    }


    public function similizator()
    {
        try {
            // Get all watches without similarities
            $watchToCompare = Watch::doesntHave('similarWatches')
                ->orderBy('created_at', 'asc')
                ->first();

            // Check if there are any watches to compare
            if (is_null($watchToCompare)) {
                return response()->json(['message' => 'No watches available for comparison'], 404);
            }

            // Search for similarities for each watch

            //code...
            $results = $this->searchService->search($watchToCompare->brand, $watchToCompare->model);
            $result = $this->similarityService->processAndStoreSimilarities($results, $watchToCompare);

            // Link results with original watch
            $totalWatches = Watch::all()->count();
            return response('Added ' . count($result['watches'] ?? []) . ' similarities for ' . $watchToCompare->brand . ' ' . $watchToCompare->model . ' total of ' . $totalWatches . ' watches');
        } catch (\Throwable $e) {
            logger()->error("Similizator error: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    public function imagenator(Request $request)
    {
        $amount = $request->input('imagesPerRequest', 1);
        $totalImagesBefore = Watch::whereNotnull('image_url')->count();

        $watchesWithoutImage = Watch::whereNull('image_url')
            ->orderBy('created_at', 'asc')
            ->take($amount)
            ->get();

        foreach ($watchesWithoutImage as $key => $watch) {
            $this->imageService->fetchAndUpdateImage($watch);

            if ($amount > 1) sleep(1);
        }

        $totalImages = Watch::whereNotnull('image_url')->count();
        $added = $totalImages - $totalImagesBefore;
        return response("Generated image(s): {$added}, total: {$totalImages}");
    }


    public function imagekit(Request $request)
    {
        $amount = $request->input('imagesPerRequest', 1);
        $query = Watch::where('image_url', 'not like', '%ik.imagekit.io%');
        $watchesToGo = $query->count();
        $watches = $query->take($amount)->get();
        $count = 0;
        foreach ($watches as $key => $watch) {
            $url = $this->imageKitService->uploadFromUrl($watch, "{$watch->brand}_{$watch->model}_{$watch->id}");

            if ($url) {
                $count++;
                $watch->url = $watch->image_url;
                $watch->image_url = $url;
                $watch->save();
            }

            sleep(1);
        }
        $watchesToGo = $watchesToGo - $count;
        return response("Uploaded to imagekit: {$count}, to go: {$watchesToGo}");
    }


    public function garbageCleaner()
    {
        $watch = Watch::where('legit', false)->first();

        $isGarbage = $this->searchService->isGarbage($watch->brand, $watch->model);

        if (!$isGarbage) {
            $watch->delete();
            return response("Removed {$watch->brand} {$watch->model}");
        } else {
            $watch->legit = true;
            $watch->save();
            return response("Checked {$watch->brand} {$watch->model}, LEGIT");
        }
    }
}
