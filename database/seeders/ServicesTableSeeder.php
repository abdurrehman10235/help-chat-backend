<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 

class ServicesTableSeeder extends Seeder
{
    public function run()
{
    DB::table('services_en')->insert([
        [
            'slug' => 'general-checkup',
            'name' => 'General Checkup',
            'description' => 'A complete health checkup for adults and children.',
            'price' => 50.00,
            'image_url' => 'https://example.com/images/checkup_en.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'slug' => 'dental-cleaning',
            'name' => 'Dental Cleaning',
            'description' => 'Professional teeth cleaning by a dentist.',
            'price' => 75.00,
            'image_url' => 'https://example.com/images/dental_en.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('services_ar')->insert([
        [
            'slug' => 'general-checkup',
            'name' => 'فحص عام',
            'description' => 'فحص صحي شامل للبالغين والأطفال.',
            'price' => 50.00,
            'image_url' => 'https://example.com/images/checkup_ar.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'slug' => 'dental-cleaning',
            'name' => 'تنظيف الأسنان',
            'description' => 'تنظيف الأسنان باحتراف على يد طبيب أسنان.',
            'price' => 75.00,
            'image_url' => 'https://example.com/images/dental_ar.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
}
}
