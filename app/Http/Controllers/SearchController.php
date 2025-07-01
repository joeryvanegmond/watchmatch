<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Watch;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchUrlService;
use App\Services\WatchSimilarityService;

class SearchController extends Controller
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
        $watches = Watch::inRandomOrder()->limit(20)->get();
        $info = null;

        foreach ($watches as $watch) {
            if (is_null($watch->image_url)) {
                $result = $this->imageService->fetchAndUpdateImage($watch);
                if ($result['limitExceeded']) {
                    $info = "Niet alle afbeeldingen zijn beschikbaar.";
                }
            }
            $watch->setAttribute('image_url', $watch->image_url ?? $this->imageService->getPlaceholder());
        }

        return view('home', compact('watches', 'info'));
    }

    public function search(Request $request)
    {
        $brand = $request->get('brand');
        $model = $request->get('model');

        if (!$brand || !$model) {
            return response()->json(['error' => 'Vul merk en model in'], 400);
        }

        $info = null;

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

        $existingSimilarities = $this->similarityService->getSimilarWatches($watchToCompare);

        if ($existingSimilarities->isNotEmpty()) {
            foreach ($existingSimilarities as $watch) {
                $watch->image_url ??= $this->imageService->getPlaceholder();
            }
            return response()->json([
                'original' => $watchToCompare,
                'similar' => $existingSimilarities,
            ]);
        }

        $results = $this->searchService->search($brand, $model);
        $similarWatches = $this->similarityService->processAndStoreSimilarities($results, $watchToCompare);

        return response()->json([
            'original' => $watchToCompare,
            'similar' => $similarWatches['watches'],
            'info' => $similarWatches['info'],
        ]);
    }

    public function link(Request $request)
    {
        try {
            $this->similarityService->linkWatches($request->original, $request->match);
            return response()->json($request);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
