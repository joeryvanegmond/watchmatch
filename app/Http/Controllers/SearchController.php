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

    public function __construct(SerpApiService $searchService, GoogleSearchService $googleSearch)
    {
        $this->searchService = $searchService;
        $this->googleSearch = $googleSearch;
    }

    public function index()
    {
        $watches = Watch::inRandomOrder()->limit(20)->get();

        foreach ($watches as $watch) {
            $imageUrl = $this->placeholder;
            if ($watch->image_url == null) {
                try {
                    $imageUrl = $this->googleSearch->searchImage($watch->brand . ' ' . $watch->model);
                    $watch->image_url = $imageUrl;
                    $watch->save();
                    // lokaal updaten
                } catch (\Throwable $e) {
                    Log::warning("Image search failed for {$watch->brand} {$watch->model}: {$e->getMessage()}");
                }
                $watch->setAttribute('image_url', $imageUrl);
            }
        }
        return view('home', compact('watches'));
    }

    public function search(Request $request)
    {
        try {
            $brand = $request->get('brand');
            $model = $request->get('model');

            if (!$brand || !$model) {
                return response()->json(['error' => 'Vul merk en model in'], 400);
            }
            //save original (if not exist)
            $watchToCompare = Watch::where([
                ['brand', strtolower($brand)],
                ['model', strtolower($model)],
            ])->first();
            if (!$watchToCompare) {
                $foundImage = $this->googleSearch->searchImage($brand . ' ' . $model);
                
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
                return response()->json([
                    'original' => $watchToCompare,
                    'similar' => $similaritiesFromDatabase
                ]);
            }
            $results = $this->searchService->search($brand, $model);
            $similarWatches = collect();
            
            foreach ($results as $item) {
                $imageUrl = null;
            
                try {
                    $imageUrl = $this->googleSearch->searchImage($item->brand . ' ' . $item->model);
                } catch (\Throwable $e) {
                    Log::warning("Image search failed for {$item->brand} {$item->model}: {$e->getMessage()}");
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
                    $placeholder = "https://static.watchpatrol.net/static/explorer/img/no_watch_placeholder.8793368e62ea.png";
                    $similarWatch->image_url = $placeholder;
                }

                $similarWatches->push($similarWatch);
            }
            
            return response()->json([
                'original' => $watchToCompare,
                'similar' => $similarWatches
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
                    // Verhoog bestaande link_strength
                    DB::table('watch_similarities')
                        ->where('watch_id', $original->id)
                        ->where('similar_watch_id', $id)
                        ->increment('link_strength', 0.1);
                } else {
                    // Voeg nieuwe relatie toe
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

    private function CreateURL(string $brand, string $model, string $variant): string
    {
        return "https://www.chrono24.nl/search/index.htm?dosearch=true&watchTypes=U&searchexplain=false&query={$brand}+{$model}";
    }
}
