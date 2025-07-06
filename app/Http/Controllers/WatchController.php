<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use App\Services\WatchUrlService;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    private SerpApiService $searchService;
    private WatchImageService $imageService;
    private WatchUrlService $urlService;
    private WatchSimilarityService $similarityService;

    public function __construct(
        SerpApiService $searchService,
        WatchImageService $imageService,
        WatchUrlService $urlService,
        WatchSimilarityService $similarityService
    ) {
        $this->searchService = $searchService;
        $this->imageService = $imageService;
        $this->urlService = $urlService;
        $this->similarityService = $similarityService;
    }


    public function index()
    {
        $watches = json_encode(Watch::whereNotNull('image_url')->inRandomOrder()->paginate(30, ['*'], 'page', 1));

        return view('watch.index', compact('watches'));
    }

    public function show(Watch $watch)
    {
        $watchToCompare = $watch;
        $similarWatches = $watchToCompare->similarWatches()->get();

        if(!$watchToCompare->image_url) $watchToCompare->image_url = $this->imageService->getPlaceholder();

        $similarWatches->transform(function ($watch) {
            if (is_null($watch->image_url)) {
            $watch->image_url = $this->imageService->getPlaceholder();
            }
            return $watch;
        });
        return view('watch.show', compact('watchToCompare', 'similarWatches'));
    }

    public function findAndShow(Request $request)
    {   
        $brand = $request->get('brand');
        $model = $request->get('model');

        if (!$brand || !$model) {
            return response()->json(['error' => 'Vul merk en model in'], 400);
        }

        $watchToCompare = Watch::firstOrCreate(
            [
                'brand' => strtolower($brand),
                'model' => strtolower($model),
            ],
            [
                'image_url' => $this->imageService->safeSearchImage("{$brand} {$model}")['image'],
                'url' => $this->urlService->create($brand, $model),
            ]
        );
        if(!$watchToCompare->image_url) $watchToCompare->image_url = $this->imageService->getPlaceholder();

        return view('watch.show', compact('watchToCompare'));
    }
}
