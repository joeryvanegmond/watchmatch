<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SerpApiService;
use \App\Models\Watch;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleSearchService;

class SearchController extends Controller
{
    protected SerpApiService $searchService;
    protected GoogleSearchService $googleSearch;

    public function __construct(SerpApiService $searchService, GoogleSearchService $googleSearch)
    {
        $this->searchService = $searchService;
        $this->googleSearch = $googleSearch;
    }

    public function index()
    {
        return view('home');
    }

    public function search(Request $request)
    {
        $brand = $request->get('brand');
        $model = $request->get('model');

        if (!$brand || !$model) {
            return response()->json(['error' => 'Missing query'], 400);
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
        
        foreach ($results as $item) {
            $imageUrl = $this->googleSearch->searchImage($item->brand . ' ' . $item->model);

            $similarWatch = Watch::updateOrCreate(
                [
                    'brand' => strtolower($item->brand), 
                    'model' => strtolower($item->model),
                    'variant' => strtolower($item->variant ?? "")
                ],
                [
                    'image_url' => $imageUrl ?? null,
                    'price' => $item->price ?? null,
                    'url' => $this->CreateURL($item->brand, $item->model, $item->variant ?? ""),
                ]
                );

            $existing = DB::table('watch_similarities')
            ->where('watch_id', $watchToCompare->id)
            ->where('similar_watch_id', $similarWatch->id)
            ->first();
        
            if ($existing) {
                // Verhoog bestaande link_strength
                DB::table('watch_similarities')
                    ->where('watch_id', $watchToCompare->id)
                    ->where('similar_watch_id', $similarWatch->id)
                    ->increment('link_strength', 0.1);
            } else {
                // Voeg nieuwe relatie toe
                $watchToCompare->similarWatches()->attach($similarWatch->id, [
                    'link_strength' => 0.1,
                ]);
            }
        }
        return response()->json([
            'original' => $watchToCompare,
            'similar' => $watchToCompare->similarWatches()->get()
        ]);
    }

    public function link(Request $request)
    {

    }

    private function CreateURL(string $brand, string $model, string $variant):string
    {
        return "https://www.chrono24.nl/search/index.htm?dosearch=true&watchTypes=U&searchexplain=false&query={$brand}+{$model}";
    }
}
