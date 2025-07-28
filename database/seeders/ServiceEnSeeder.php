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
                'name' => 'Jeddah Beach Resorts Tour',
                'description' => 'Discover luxurious beach resorts along the Red Sea coast. Includes transport, lunch, and beach access. Full day experience with stunning views and water activities.',
                'price' => 150.00,
                'image_url' => '/services/jeddah_resorts.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'balad-site',
                'name' => 'Al-Balad Historic District',
                'description' => 'UNESCO World Heritage site tour of Historic Jeddah. Explore traditional coral stone houses, ancient souks, and Ottoman architecture. Includes guided tour, traditional coffee, and souvenir shopping.',
                'price' => 80.00,
                'image_url' => '/services/balad_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'corniche-site',
                'name' => 'Jeddah Corniche & Waterfront',
                'description' => 'Beautiful Red Sea waterfront tour with stunning views. Walk along the 30km coastline, visit sculptures, enjoy sunset views, and experience the vibrant atmosphere.',
                'price' => 50.00,
                'image_url' => '/services/corniche_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'shopping-mall',
                'name' => 'Premium Shopping Malls Tour',
                'description' => 'Visit Jeddah\'s premium shopping destinations including Red Sea Mall, Mall of Arabia, and Tahlia Street. Includes transport, dining vouchers, and personal shopping guide.',
                'price' => 120.00,
                'image_url' => '/services/shopping_mall.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'king-fahd-fountain',
                'name' => 'King Fahd Fountain Experience',
                'description' => 'Visit the world\'s tallest water fountain reaching 312 meters high. Best viewing spots, photography opportunities, and evening light show. Includes refreshments and transport.',
                'price' => 60.00,
                'image_url' => '/services/king_fahd_fountain.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
        ]);
    }
}