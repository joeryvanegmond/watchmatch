<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    private WatchSimilarityService $similarityService;
    private WatchImageService $imageService;
    private SerpApiService $searchService;


    public function __construct(
        SerpApiService $searchService,
        WatchImageService $imageService,
        WatchSimilarityService $similarityService
    ) {
        $this->imageService = $imageService;
        $this->similarityService = $similarityService;
        $this->searchService = $searchService;
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
}
