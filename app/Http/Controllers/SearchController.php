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

    public function getWatches(Request $request)
    {
        $curPage = $request->page;

        $filter = $request->brand;
        $query = Watch::where('image_url', 'like', '%ik.imagekit.io%');

        if ($filter) {
            $query->where('brand', $filter);
        }

        $watches = $query->paginate(100, ['*'], 'page', $curPage);

        $watches->transform(function ($watch) {
            if (is_null($watch->image_url)) {
                $watch->image_url = $this->imageService->getPlaceholder();
            }
            return $watch;
        });

        return response($watches);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $terms = explode(' ', $query);

        $watches = Watch::query();

        foreach ($terms as $term) {
            $watches->where(function ($q) use ($term) {
                $q->where('brand', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%");
            });
        }

        return $watches
            ->limit(50)
            ->get()
            ->unique(function ($item) {
                return $item->brand . '|' . $item->model;
            })
            ->take(10)  
            ->values();
    }

    public function findSimilarities(Request $request) {
        $id = $request->input('id', null);
        $watchToCompare = Watch::find($id);

        $results = $this->searchService->search($watchToCompare->brand, $watchToCompare->model);
        $similarities = $this->similarityService->processAndStoreSimilarities($results, $watchToCompare);
        return response($similarities);
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
