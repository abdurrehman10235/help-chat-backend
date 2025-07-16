<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceArSeeder extends Seeder
{
    public function run()
    {
        DB::table('services_ar')->insert([
            [
                'slug' => 'room service',
                'name' => 'خدمة الغرف',
                'description' => 'خدمة طعام مريحة داخل الغرفة متوفرة 24/7.',
                'price' => 50.00,
                'image_url' => '/storage/services/686bbad7c852f.jpg'
            ],
            [
                'slug' => 'cafe',
                'name' => 'مقهى',
                'description' => 'استمتع بالقهوة والشاي والمعجنات الطازجة في المقهى.',
                'price' => 30.00,
                'image_url' => '/storage/services/686bbb599c792.jpg'
            ],
            [
                'slug' => 'laundry',
                'name' => 'غسيل الملابس',
                'description' => 'خدمة غسيل وتنظيف جاف احترافية.',
                'price' => 25.00,
                'image_url' => '/storage/services/686bbbcb81790.jpg'
            ],
            [
                'slug' => 'restaurant',
                'name' => 'مطعم',
                'description' => 'وجبات لذيذة تُقدم طوال اليوم في المطعم.',
                'price' => 70.00,
                'image_url' => '/storage/services/686bbc26b4ac7.jpeg'
            ],
            [
                'slug' => 'lounge',
                'name' => 'صالة',
                'description' => 'استرخِ في منطقة الصالة الفاخرة مع المشروبات.',
                'price' => 40.00,
                'image_url' => '/storage/services/686bbc7701e81.jpg'
            ]
        ]);
    }
    
}