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
        // Get all watches without similarities
        $watchToCompare = Watch::doesntHave('similarWatches')
        ->orderBy('created_at', 'asc')
        ->first();

        // Check if there are any watches to compare
        if (is_null($watchToCompare)) {
            return response()->json(['message' => 'No watches available for comparison'], 404);
        }

        // Search for similarities for each watch
        $results = $this->searchService->search($watchToCompare->brand, $watchToCompare->model);
        $result = $this->similarityService->processAndStoreSimilarities($results, $watchToCompare);

        $totalWatches = Watch::all()->count();

        // Link results with original watch
        return response('Added ' . count($result['watches'] ?? []) . ' similarities for ' . $watchToCompare->brand . ' ' . $watchToCompare->model . ' total of ' . $totalWatches . ' watches');
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


    public function imagekit() {
        $query = Watch::where('image_url', 'not like', '%ik.imagekit.io%');
        $watchesToGo = $query->count();
        $watches = $query->take(5)->get();

        $count = 0;
        foreach ($watches as $key => $watch) {
            $url = $this->imageKitService->uploadFromUrl($watch->image_url, "{$watch->brand}_{$watch->model}_{$watch->id}");
    
            if ($url) {
                $count++;
                $watch->url = $watch->image_url;
                $watch->image_url = $url;
                $watch->save();
            }

            usleep(750000);
        }
        $watchesToGo = $watchesToGo - $count;
        return response("Uploaded to imagekit: {$count}, to go: {$watchesToGo}");
    }
}
