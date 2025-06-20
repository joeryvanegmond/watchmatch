<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Watch;
use Illuminate\Support\Facades\DB;

class WatchSimilaritySeeder extends Seeder
{
    public function run()
    {
        $rolex = Watch::where('brand', 'Rolex')->first();
        $omega = Watch::where('brand', 'Omega')->first();
        $tudor = Watch::where('brand', 'Tudor')->first();

        if ($rolex && $omega) {
            DB::table('watch_similarities')->insertOrIgnore([
                'watch_id' => $rolex->id,
                'similar_watch_id' => $omega->id,
                'link_strength' => 0.3, // voorbeeldscore
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($rolex && $tudor) {
            DB::table('watch_similarities')->insertOrIgnore([
                'watch_id' => $rolex->id,
                'similar_watch_id' => $tudor->id,
                'link_strength' => 0.2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($omega && $tudor) {
            DB::table('watch_similarities')->insertOrIgnore([
                'watch_id' => $omega->id,
                'similar_watch_id' => $tudor->id,
                'link_strength' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
