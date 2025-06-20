<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Watch;

class WatchSeeder extends Seeder
{
    public function run()
    {
        $watches = [
            [
                'brand' => 'Rolex',
                'model' => 'Submariner',
                'url' => 'https://www.rolex.com/watches/submariner.html',
                'image_url' => 'https://example.com/images/rolex-submariner.jpg',
                'price' => 8990.00,
            ],
            [
                'brand' => 'Omega',
                'model' => 'Seamaster',
                'url' => 'https://www.omegawatches.com/seamaster',
                'image_url' => 'https://example.com/images/omega-seamaster.jpg',
                'price' => 5600.00,
            ],
            [
                'brand' => 'Tudor',
                'model' => 'Black Bay',
                'url' => 'https://www.tudorwatch.com/en/watches/black-bay',
                'image_url' => 'https://example.com/images/tudor-black-bay.jpg',
                'price' => 3500.00,
            ],
        ];

        foreach ($watches as $watch) {
            Watch::create($watch);
        }
    }
}
