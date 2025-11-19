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
            return response('Added ' . count($result['watches'] ?? []) . ' similarities');
        } catch (\Throwable $e) {
            logger()->error("Similizator error: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    public function descriptinator(Request $request) 
    {
        try {
            $amount = $request->input('amountPerRequest', 1);
            $watchesWithoutMetadata = Watch::whereNull('weight')->select('id', 'brand', 'model')->take($amount)->get();
            $result = $this->searchService->getDescription(json_encode($watchesWithoutMetadata));
            $count = 0;
            foreach ($result as $key => $value) {
                Watch::updateOrCreate(
                    [
                        'id' => strtolower($value->id),
                    ],
                    [
                        'description' => $value->description ?? null,
                        'type' => $value->type ?? null,
                        'diameter' => $value->diameter ?? null,
                        'material' => $value->material ?? null,
                        'dial_color' => $value->dial_color ?? null,
                        'band_color' => $value->band_color ?? null,
                        'movement' => $value->movement ?? null,
                        'year' => $value->year ?? null,
                        'water_resistance' => $value->water_resistance ?? null,
                        'gender' => $value->gender ?? null,
                        'style' => $value->style ?? null,
                        'weight' => $value->weight ?? null,
                    ]
                );
                $count++;
            }
            return response('Generated description for ' . $count . ' watches');
        } catch (\Throwable $e) {
            logger()->error("Descriptinator error: " . $e->getMessage());
            return response($e->getMessage());
        }
    }

    public function imagenator(Request $request)
    {
        try {
            $amount = $request->input('imagesPerRequest', 1);
            $totalImagesBefore = Watch::whereNotnull('image_url')->count();
    
            $watchesWithoutImage = Watch::whereNull('image_url')
                ->orderBy('created_at', 'asc')
                ->take($amount)
                ->get();
    
            foreach ($watchesWithoutImage as $key => $watch) {
                $failure = $this->imageService->fetchAndUpdateImage($watch);

                if($failure) $watch->delete();

                if ($amount > 1) sleep(1);
            }
    
            $totalImages = Watch::whereNotnull('image_url')->count();
            $added = $totalImages - $totalImagesBefore;
            return response("Generated image(s): {$added}, total: {$totalImages}");
        } catch (\Throwable $e) {
            return response($e->getMessage());
        }
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
