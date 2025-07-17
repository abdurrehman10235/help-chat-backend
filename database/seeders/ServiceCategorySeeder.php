<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('service_categories')->insertOrIgnore([
            ['slug' => 'pre-arrival', 'name_en' => 'Pre-Arrival', 'name_ar' => 'قبل الوصول'],
            ['slug' => 'arrival', 'name_en' => 'Arrival', 'name_ar' => 'الوصول'],
            ['slug' => 'in-stay', 'name_en' => 'In-Stay', 'name_ar' => 'أثناء الإقامة'],
            ['slug' => 'departure', 'name_en' => 'Departure', 'name_ar' => 'المغادرة'],
        ]);
    }
}