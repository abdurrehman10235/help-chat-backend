<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceEnSeeder extends Seeder
{
    public function run()
    {
        DB::table('services_en')->insert([
            [
                'slug' => 'room-service',
                'name' => 'Room Service',
                'description' => 'Comfortable in-room dining available 24/7.',
                'price' => 50.00,
                'image_url' => '/services/686bb48f37f33.jpg'
            ],
            [
                'slug' => 'cafe',
                'name' => 'Cafe',
                'description' => 'Enjoy fresh coffee, tea, and pastries at our cafe.',
                'price' => 30.00,
                'image_url' => '/services/686bb5b281b85.jpg'
            ],
            [
                'slug' => 'laundry',
                'name' => 'Laundry',
                'description' => 'Professional laundry and dry-cleaning service.',
                'price' => 25.00,
                'image_url' => '/services/686bb6827509d.jpg'
            ],
            [
                'slug' => 'restaurant',
                'name' => 'Restaurant',
                'description' => 'Delicious meals served all day at our restaurant.',
                'price' => 70.00,
                'image_url' => '/services/686bb8b3644cf.jpeg'
            ],
            [
                'slug' => 'lounge',
                'name' => 'Lounge',
                'description' => 'Relax in our luxurious lounge area with refreshments.',
                'price' => 40.00,
                'image_url' => '/services/686bb9dd0aa26.jpg'
            ]
        ]);
    }
}