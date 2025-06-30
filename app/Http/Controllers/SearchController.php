<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SerpApiService;
use \App\Models\Watch;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleSearchService;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected SerpApiService $searchService;
    protected GoogleSearchService $googleSearch;
    protected $placeholder = "https://static.watchpatrol.net/static/explorer/img/no_watch_placeholder.8793368e62ea.png";
    protected $googleLimitExceeded = false;

    public function __construct(SerpApiService $searchService, GoogleSearchService $googleSearch)
    {
        $this->searchService = $searchService;
        $this->googleSearch = $googleSearch;
    }

    public function index()
    {
        $watches = Watch::inRandomOrder()->limit(20)->get();
        $info = null;

        foreach ($watches as $watch) {
            $imageUrl = $this->placeholder;
            if ($watch->image_url == null) {
                if (!$this->googleLimitExceeded) {
                    try {
                        $imageUrl = $this->googleSearch->searchImage($watch->brand . ' ' . $watch->model);
                        $watch->image_url = $imageUrl;
                        $watch->save();
                    } catch (\Throwable $e) {
                        if ($e->getCode() == 429) {
                            $this->googleLimitExceeded = true;
                            $info = "Niet alle afbeeldingen zijn beschikbaar.";
                        }
                        Log::warning("Image search failed for {$watch->brand} {$watch->model}: {$e->getMessage()}");
                    }
                }
            } else {
                $imageUrl = $watch->image_url;
            }
            $watch->setAttribute('image_url', $imageUrl);
        }
        return view('home', compact('watches', 'info'));
    }

    public function search(Request $request)
    {
        try {
            $brand = $request->get('brand');
            $model = $request->get('model');
            $info = null;

            if (!$brand || !$model) {
                return response()->json(['error' => 'Vul merk en model in'], 400);
            }
            //save original (if not exist)
            $watchToCompare = Watch::where([
                ['brand', strtolower($brand)],
                ['model', strtolower($model)],
            ])->first();
            if (!$watchToCompare) {
                $foundImage = null;
                if (!$this->googleLimitExceeded) {
                    try {
                        $foundImage = $this->googleSearch->searchImage($brand . ' ' . $model);
                    } catch (\Throwable $th) {
                        if ($th->getCode() == 429) {
                            $this->googleLimitExceeded = true;
                            $info = "Niet alle afbeeldingen zijn beschikbaar.";
                        }
                    }
                }
                $watchToCompare = Watch::create([
                    'brand' => strtolower($brand),
                    'model' => strtolower($model),
                    'image_url' => $foundImage,
                    'price' => null,
                    'url' => $this->CreateURL($brand, $model, ""),
                ]);
            }
            // check if already similarities
            $similaritiesFromDatabase = $watchToCompare->similarWatches()->get();
            
            if (!$similaritiesFromDatabase->isEmpty()) {
                foreach ($similaritiesFromDatabase as $watch) {
                    if ($watch->image_url == null) {
                        $watch->image_url = $this->placeholder;
                    }
                }
                return response()->json([
                    'original' => $watchToCompare,
                    'similar' => $similaritiesFromDatabase
                ]);
            }
            $results = $this->searchService->search($brand, $model);
            $similarWatches = collect();

            foreach ($results as $item) {
                $imageUrl = null;

                if (!$this->googleLimitExceeded) {
                    try {
                        $imageUrl = $this->googleSearch->searchImage($item->brand . ' ' . $item->model);
                    } catch (\Throwable $e) {
                        if ($e->getCode() == 429) {
                            $this->googleLimitExceeded = true;
                            $info = "Niet alle afbeeldingen zijn beschikbaar.";
                        }
                        Log::warning("Image search failed for {$item->brand} {$item->model}: {$e->getMessage()}");
                    }
                }

                $similarWatch = Watch::updateOrCreate(
                    [
                        'brand'   => strtolower($item->brand),
                        'model'   => strtolower($item->model),
                        'variant' => strtolower($item->variant ?? ''),
                    ],
                    [
                        'image_url' => $imageUrl,
                        'price'     => $item->price ?? null,
                        'url'       => $this->CreateURL($item->brand, $item->model, $item->variant ?? ''),
                    ]
                );

                if ($imageUrl == null) {
                    $similarWatch->image_url = $this->placeholder;
                }

                $similarWatches->push($similarWatch);
            }

            return response()->json([
                'original' => $watchToCompare,
                'similar' => $similarWatches,
                'info' => $info,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    public function link(Request $request)
    {
        try {
            $original = Watch::find($request->original);
            $id = $request->match;
            $existing = DB::table('watch_similarities')
                ->where('watch_id', $original->id)
                ->where('similar_watch_id', $id)
                ->first();

            if ($existing) {
                DB::table('watch_similarities')
                    ->where('watch_id', $original->id)
                    ->where('similar_watch_id', $id)
                    ->increment('link_strength', 0.1);
            } else {
                $original->similarWatches()->attach($id, [
                    'link_strength' => 0.1,
                ]);
            }
            return response()->json($request);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e]);
        }
        return response()->json($request);
    }

    private function CreateURL(string $brand, string $model): string
    {
        return "https://www.chrono24.nl/search/index.htm?dosearch=true&watchTypes=U&searchexplain=false&query={$brand}+{$model}";
    }
}
