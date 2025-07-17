<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceArSeeder extends Seeder
{
    public function run()
    {
        // Get category IDs by slug
        $categories = DB::table('service_categories')->pluck('id', 'slug');

        DB::table('services_ar')->insert([
            // Pre-arrival
            [
                'slug' => 'airport-pickup',
                'name' => 'استقبال من المطار',
                'description' => 'خدمة نقل خاصة من المطار قبل إقامتك.',
                'price' => 120.00,
                'image_url' => '/services/airport_pickup.jpg',
                'category_id' => $categories['pre-arrival'],
            ],
            [
                'slug' => 'early-checkin',
                'name' => 'تسجيل وصول مبكر',
                'description' => 'الوصول المبكر قبل الوقت الرسمي لتسجيل الوصول.',
                'price' => 40.00,
                'image_url' => '/services/early_checkin.jpg',
                'category_id' => $categories['pre-arrival'],
            ],
            [
                'slug' => 'room-preferences',
                'name' => 'تفضيلات الغرفة',
                'description' => 'خصص تفضيلات غرفتك قبل الوصول.',
                'price' => 0.00,
                'image_url' => '/services/room_preferences.jpg',
                'category_id' => $categories['pre-arrival'],
            ],

            // Arrival
            [
                'slug' => 'welcome-drink',
                'name' => 'مشروب ترحيبي',
                'description' => 'استمتع بمشروب منعش عند الوصول.',
                'price' => 10.00,
                'image_url' => '/services/welcome_drink.jpg',
                'category_id' => $categories['arrival'],
            ],
            [
                'slug' => 'luggage-assistance',
                'name' => 'مساعدة في الأمتعة',
                'description' => 'خدمة حمل الحقائب عند الوصول.',
                'price' => 0.00,
                'image_url' => '/services/luggage_assistance.jpg',
                'category_id' => $categories['arrival'],
            ],
            [
                'slug' => 'express-checkin',
                'name' => 'تسجيل دخول سريع',
                'description' => 'تسجيل دخول سريع بدون انتظار.',
                'price' => 15.00,
                'image_url' => '/services/express_checkin.jpg',
                'category_id' => $categories['arrival'],
            ],

            // In-stay
            [
                'slug' => 'room-service',
                'name' => 'خدمة الغرف',
                'description' => 'خدمة طعام مريحة داخل الغرفة متوفرة 24/7.',
                'price' => 50.00,
                'image_url' => '/services/686bbad7c852f.jpg',
                'category_id' => $categories['in-stay'],
            ],
            [
                'slug' => 'laundry',
                'name' => 'غسيل الملابس',
                'description' => 'خدمة غسيل وتنظيف جاف احترافية.',
                'price' => 25.00,
                'image_url' => '/services/686bbbcb81790.jpg',
                'category_id' => $categories['in-stay'],
            ],
            [
                'slug' => 'spa',
                'name' => 'سبا',
                'description' => 'استرخِ مع علاجات السبا الفاخرة.',
                'price' => 90.00,
                'image_url' => '/services/spa.jpg',
                'category_id' => $categories['in-stay'],
            ],

            // Departure
            [
                'slug' => 'late-checkout',
                'name' => 'تسجيل خروج متأخر',
                'description' => 'تمديد إقامتك بعد الوقت المحدد للخروج.',
                'price' => 35.00,
                'image_url' => '/services/late_checkout.jpg',
                'category_id' => $categories['departure'],
            ],
            [
                'slug' => 'baggage-hold',
                'name' => 'حفظ الأمتعة',
                'description' => 'نحفظ حقائبك بعد الخروج حتى موعد مغادرتك.',
                'price' => 0.00,
                'image_url' => '/services/baggage_hold.jpg',
                'category_id' => $categories['departure'],
            ],
            [
                'slug' => 'airport-dropoff',
                'name' => 'توصيل إلى المطار',
                'description' => 'نقل مريح إلى المطار بعد انتهاء إقامتك.',
                'price' => 120.00,
                'image_url' => '/services/airport_dropoff.jpg',
                'category_id' => $categories['departure'],
            ],
        ]);
    }
}