<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use App\Services\ImagekitService;
use App\Services\SerpApiService;
use App\Services\WatchImageService;
use App\Services\WatchSimilarityService;
use App\Services\WatchUrlService;
use Http\Discovery\Exception\NotFoundException;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;

class WatchController extends Controller
{
    private WatchImageService $imageService;
    private WatchUrlService $urlService;
    private SerpApiService $searchService;

    public function __construct(
        WatchImageService $imageService,
        WatchUrlService $urlService,
        SerpApiService $searchService,
    ) {
        $this->imageService = $imageService;
        $this->urlService = $urlService;
        $this->searchService = $searchService;
    }


    public function index(Request $request)
    {
        $filter = $request->brand;
        $query = Watch::where('image_url', 'like', '%ik.imagekit.io%');
        
        if ($filter) {
            $query->where('brand', $filter);
        }
        
        $watches = json_encode(
            $query->inRandomOrder()
            ->paginate(30, ['*'], 'page', 1)
        );
        
        return view('watch.index', compact('watches', 'filter'));
    }

    public function show(Watch $watch)
    {
        $watchToCompare = $watch;
        $similarWatches = $watchToCompare->similarWatches()->get();

        if (!$watchToCompare->image_url) $watchToCompare->image_url = $this->imageService->getPlaceholder();

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
        try {
            $brand = $request->get('brand');
            $model = $request->get('model');
    
            if (!$brand || !$model) {
                return response()->json(['error' => 'Vul merk en model in'], 400);
            }
    
            $isGarbage = $this->searchService->isGarbage($brand, $model);
    
            $info = null;
            if(!$isGarbage) throw new NotFoundException("Dit lijkt geen horloge te zijn", 404);
    
            $watchToCompare = Watch::firstOrCreate(
                [
                    'brand' => strtolower($brand),
                    'model' => strtolower($model),
                ],
                [
                    'url' => $this->urlService->create($brand, $model),
                ]
            );
            if (!$watchToCompare->image_url) $watchToCompare->image_url = $this->imageService->getPlaceholder();
    
            return view('watch.show', compact('watchToCompare'));
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            return view('response.404', compact('brand', 'model', 'message'));
        }
    }
}
