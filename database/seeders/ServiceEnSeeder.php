<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceEnSeeder extends Seeder
{
    public function run()
    {
        // Get category IDs from the service_categories table
        $categories = DB::table('service_categories')->pluck('id', 'slug');

        DB::table('services_en')->insert([
            // Pre-arrival
            [
                'slug' => 'airport-pickup',
                'name' => 'Airport Pickup',
                'description' => 'Private airport transportation before your stay.',
                'price' => 120.00,
                'image_url' => '/services/airport_pickup.jpg',
                'category_id' => $categories['pre-arrival'],
            ],
            [
                'slug' => 'early-checkin',
                'name' => 'Early Check-in',
                'description' => 'Arrive early and relax before the standard check-in time.',
                'price' => 40.00,
                'image_url' => '/services/early_checkin.jpg',
                'category_id' => $categories['pre-arrival'],
            ],
            [
                'slug' => 'room-preferences',
                'name' => 'Room Preferences',
                'description' => 'Customize your room preferences ahead of arrival.',
                'price' => 0.00,
                'image_url' => '/services/room_preferences.jpg',
                'category_id' => $categories['pre-arrival'],
            ],

            // Arrival
            [
                'slug' => 'welcome-drink',
                'name' => 'Welcome Drink',
                'description' => 'Enjoy a refreshing welcome drink upon arrival.',
                'price' => 10.00,
                'image_url' => '/services/welcome_drink.jpg',
                'category_id' => $categories['arrival'],
            ],
            [
                'slug' => 'luggage-assistance',
                'name' => 'Luggage Assistance',
                'description' => 'Porter service to help you with your bags.',
                'price' => 0.00,
                'image_url' => '/services/luggage_assistance.jpg',
                'category_id' => $categories['arrival'],
            ],
            [
                'slug' => 'express-checkin',
                'name' => 'Express Check-in',
                'description' => 'Skip the lines with priority check-in.',
                'price' => 15.00,
                'image_url' => '/services/express_checkin.jpg',
                'category_id' => $categories['arrival'],
            ],

            // In-stay
            [
                'slug' => 'room-service',
                'name' => 'Room Service',
                'description' => 'Comfortable in-room dining available 24/7.',
                'price' => 50.00,
                'image_url' => '/services/686bb48f37f33.jpg',
                'category_id' => $categories['in-stay'],
            ],
            [
                'slug' => 'laundry',
                'name' => 'Laundry',
                'description' => 'Professional laundry and dry-cleaning service.',
                'price' => 25.00,
                'image_url' => '/services/686bb6827509d.jpg',
                'category_id' => $categories['in-stay'],
            ],
            [
                'slug' => 'spa',
                'name' => 'Spa',
                'description' => 'Relax with our luxury spa treatments.',
                'price' => 90.00,
                'image_url' => '/services/spa.jpg',
                'category_id' => $categories['in-stay'],
            ],

            // Departure
            [
                'slug' => 'late-checkout',
                'name' => 'Late Checkout',
                'description' => 'Extend your stay beyond normal checkout time.',
                'price' => 35.00,
                'image_url' => '/services/late_checkout.jpg',
                'category_id' => $categories['departure'],
            ],
            [
                'slug' => 'baggage-hold',
                'name' => 'Baggage Hold',
                'description' => 'We’ll keep your luggage safe after checkout.',
                'price' => 0.00,
                'image_url' => '/services/baggage_hold.jpg',
                'category_id' => $categories['departure'],
            ],
            [
                'slug' => 'airport-dropoff',
                'name' => 'Airport Drop-off',
                'description' => 'Convenient airport transportation at the end of your stay.',
                'price' => 120.00,
                'image_url' => '/services/airport_dropoff.jpg',
                'category_id' => $categories['departure'],
            ],
        ]);
    }
}