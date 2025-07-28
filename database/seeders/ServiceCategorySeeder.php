<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCategorySeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::table('service_categories')->truncate();
        
        DB::table('service_categories')->insert([
            ['slug' => 'hotel-tour', 'name_en' => 'Hotel Tour', 'name_ar' => 'جولة في الفندق'],
            ['slug' => 'explore-jeddah', 'name_en' => 'Explore Jeddah', 'name_ar' => 'استكشف جدة'],
        ]);
    }
}