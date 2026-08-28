<?php

namespace Database\Seeders;

use App\Models\VendorSpot;
use Illuminate\Database\Seeder;

class VendorSpotSeeder extends Seeder
{
    /**
     * Seed the vendor spots table.
     *
     * Coordinates are calculated as percentages from the source image
     * dimensions (1536 × 1024 px).
     */
    public function run(): void
    {
        // Source image dimensions
        $imgWidth  = 1536;
        $imgHeight = 1024;

        // Raw pixel coordinates for each spot
        $spots = [
            ['name' => 'Spot 1',  'px' => [904, 279]],
            ['name' => 'Spot 2',  'px' => [878, 283]],
            ['name' => 'Spot 3',  'px' => [844, 282]],
            ['name' => 'Spot 4',  'px' => [817, 279]],
            ['name' => 'Spot 5',  'px' => [786, 283]],
            ['name' => 'Spot 6',  'px' => [645, 279]],
            ['name' => 'Spot 7',  'px' => [616, 280]],
            ['name' => 'Spot 8',  'px' => [583, 279]],
            ['name' => 'Spot 9',  'px' => [551, 281]],
            ['name' => 'Spot 10', 'px' => [528, 279]],
        ];

        foreach ($spots as $spot) {
            VendorSpot::updateOrCreate(
                ['name' => $spot['name']],
                [
                    'event_id' => null,
                    'price'    => 50000,
                    'pos_x'    => round($spot['px'][0] / $imgWidth * 100, 4),
                    'pos_y'    => round($spot['px'][1] / $imgHeight * 100, 4),
                    'status'   => 'available',
                ]
            );
        }
    }
}
