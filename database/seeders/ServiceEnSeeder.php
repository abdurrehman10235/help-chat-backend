<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceEnSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::table('services_en')->truncate();
        
        // Get category IDs from the service_categories table
        $categories = DB::table('service_categories')->pluck('id', 'slug');

        DB::table('services_en')->insert([
            // Hotel Tour Services
            [
                'slug' => 'restaurant',
                'name' => 'Restaurant',
                'description' => 'Open Buffet - Timing: 07:00 PM - 10:00 PM',
                'price' => 0.00,
                'image_url' => '/services/restaurant.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'room-service',
                'name' => 'Room Service',
                'description' => 'Order delicious meals to your room',
                'price' => 0.00,
                'image_url' => '/services/room_service.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'laundry',
                'name' => 'Laundry',
                'description' => 'Professional laundry service - Timing: 07:00 AM - 10:00 PM',
                'price' => 25.00,
                'image_url' => '/services/laundry.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'gym',
                'name' => 'Gym',
                'description' => 'State-of-the-art fitness center - Timing: 05:00 AM - 11:00 PM',
                'price' => 0.00,
                'image_url' => '/services/gym.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'reception',
                'name' => 'Reception',
                'description' => 'Reception services and assistance',
                'price' => 0.00,
                'image_url' => '/services/reception.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Reception Sub-services
            [
                'slug' => 'wake-up-call',
                'name' => 'Wake-up Call Service',
                'description' => 'Personal wake-up call at your preferred time',
                'price' => 0.00,
                'image_url' => '/services/wake_up.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'visitor-invitation',
                'name' => 'Visitor Invitation',
                'description' => 'Register visitor information for reception guidance',
                'price' => 0.00,
                'image_url' => '/services/visitor.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Room Service Menu Items
            [
                'slug' => 'club-sandwich',
                'name' => 'Club Sandwich',
                'description' => 'Triple layered sandwich with chicken, bacon, lettuce and tomato',
                'price' => 45.00,
                'image_url' => '/services/club_sandwich.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'pasta-alfredo',
                'name' => 'Pasta Alfredo',
                'description' => 'Creamy fettuccine alfredo with grilled chicken',
                'price' => 55.00,
                'image_url' => '/services/pasta_alfredo.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'grilled-salmon',
                'name' => 'Grilled Salmon',
                'description' => 'Fresh Atlantic salmon with lemon herb butter',
                'price' => 85.00,
                'image_url' => '/services/grilled_salmon.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'caesar-salad',
                'name' => 'Caesar Salad',
                'description' => 'Crisp romaine lettuce with parmesan and croutons',
                'price' => 35.00,
                'image_url' => '/services/caesar_salad.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'chocolate-cake',
                'name' => 'Chocolate Cake',
                'description' => 'Rich chocolate layer cake with vanilla ice cream',
                'price' => 25.00,
                'image_url' => '/services/chocolate_cake.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Explore Jeddah Services
            [
                'slug' => 'jeddah-resorts',
                'name' => 'Jeddah Resorts',
                'description' => 'Discover beautiful beach resorts around Jeddah',
                'price' => 150.00,
                'image_url' => '/services/jeddah_resorts.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'balad-site',
                'name' => 'Al-Balad Historic Site',
                'description' => 'UNESCO World Heritage site - Historic Jeddah',
                'price' => 80.00,
                'image_url' => '/services/balad_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'corniche-site',
                'name' => 'Jeddah Corniche',
                'description' => 'Beautiful waterfront area with stunning Red Sea views',
                'price' => 60.00,
                'image_url' => '/services/corniche_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'shopping-mall',
                'name' => 'Shopping Malls',
                'description' => 'Premium shopping destinations in Jeddah',
                'price' => 100.00,
                'image_url' => '/services/shopping_mall.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'king-fahd-fountain',
                'name' => 'King Fahd Fountain',
                'description' => 'The tallest water fountain in the world',
                'price' => 40.00,
                'image_url' => '/services/king_fahd_fountain.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
        ]);
    }
}