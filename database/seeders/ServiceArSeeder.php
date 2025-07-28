<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceArSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data
        DB::table('services_ar')->truncate();
        
        // Get category IDs from the service_categories table
        $categories = DB::table('service_categories')->pluck('id', 'slug');

        DB::table('services_ar')->insert([
            // Hotel Tour Services
            [
                'slug' => 'restaurant',
                'name' => 'المطعم',
                'description' => 'بوفيه مفتوح - التوقيت: 07:00 مساءً - 10:00 مساءً',
                'price' => 0.00,
                'image_url' => '/services/restaurant.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'room-service',
                'name' => 'خدمة الغرف',
                'description' => 'اطلب وجبات شهية إلى غرفتك',
                'price' => 0.00,
                'image_url' => '/services/room_service.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'laundry',
                'name' => 'المغسلة',
                'description' => 'خدمة غسيل احترافية - التوقيت: 07:00 صباحاً - 10:00 مساءً',
                'price' => 25.00,
                'image_url' => '/services/laundry.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'gym',
                'name' => 'النادي الرياضي',
                'description' => 'مركز لياقة بدنية متطور - التوقيت: 05:00 صباحاً - 11:00 مساءً',
                'price' => 0.00,
                'image_url' => '/services/gym.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'reception',
                'name' => 'الاستقبال',
                'description' => 'خدمات ومساعدة الاستقبال',
                'price' => 0.00,
                'image_url' => '/services/reception.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Reception Sub-services
            [
                'slug' => 'wake-up-call',
                'name' => 'خدمة الإيقاظ',
                'description' => 'مكالمة إيقاظ شخصية في الوقت المفضل لديك',
                'price' => 0.00,
                'image_url' => '/services/wake_up.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'visitor-invitation',
                'name' => 'دعوة زائر',
                'description' => 'تسجيل معلومات الزائر لإرشاد الاستقبال',
                'price' => 0.00,
                'image_url' => '/services/visitor.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Room Service Menu Items
            [
                'slug' => 'club-sandwich',
                'name' => 'ساندويش كلوب',
                'description' => 'ساندويش ثلاثي الطبقات بالدجاج والبيكون والخس والطماطم',
                'price' => 45.00,
                'image_url' => '/services/club_sandwich.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'pasta-alfredo',
                'name' => 'باستا ألفريدو',
                'description' => 'فيتوتشيني ألفريدو كريمي مع الدجاج المشوي',
                'price' => 55.00,
                'image_url' => '/services/pasta_alfredo.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'grilled-salmon',
                'name' => 'سلمون مشوي',
                'description' => 'سلمون أطلسي طازج مع زبدة الليمون والأعشاب',
                'price' => 85.00,
                'image_url' => '/services/grilled_salmon.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'caesar-salad',
                'name' => 'سلطة سيزر',
                'description' => 'خس رومين مقرمش مع البارميزان والخبز المحمص',
                'price' => 35.00,
                'image_url' => '/services/caesar_salad.jpg',
                'category_id' => $categories['hotel-tour'],
            ],
            [
                'slug' => 'chocolate-cake',
                'name' => 'كيك الشوكولاتة',
                'description' => 'كيك شوكولاتة غني بطبقات مع آيس كريم الفانيليا',
                'price' => 25.00,
                'image_url' => '/services/chocolate_cake.jpg',
                'category_id' => $categories['hotel-tour'],
            ],

            // Explore Jeddah Services
            [
                'slug' => 'jeddah-resorts',
                'name' => 'جولة منتجعات جدة الشاطئية',
                'description' => 'اكتشف المنتجعات الفاخرة على ساحل البحر الأحمر. يشمل النقل والغداء والوصول للشاطئ. تجربة يوم كامل مع مناظر خلابة وأنشطة مائية.',
                'price' => 150.00,
                'image_url' => '/services/jeddah_resorts.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'balad-site',
                'name' => 'منطقة البلد التاريخية',
                'description' => 'جولة في موقع التراث العالمي لليونسكو في جدة التاريخية. استكشف البيوت المرجانية التقليدية والأسواق القديمة والعمارة العثمانية. يشمل جولة مرشدة وقهوة تقليدية وتسوق الهدايا التذكارية.',
                'price' => 80.00,
                'image_url' => '/services/balad_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'corniche-site',
                'name' => 'كورنيش جدة والواجهة البحرية',
                'description' => 'جولة في الواجهة البحرية الجميلة للبحر الأحمر مع مناظر خلابة. تمشي على طول الساحل لمسافة 30 كم، زيارة المنحوتات، الاستمتاع بمنظر غروب الشمس، وتجربة الأجواء النابضة بالحياة.',
                'price' => 50.00,
                'image_url' => '/services/corniche_site.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'shopping-mall',
                'name' => 'جولة مراكز التسوق الفاخرة',
                'description' => 'زيارة وجهات التسوق الفاخرة في جدة بما في ذلك ريد سي مول ومول العرب وشارع التحلية. يشمل النقل وقسائم الطعام ومرشد التسوق الشخصي.',
                'price' => 120.00,
                'image_url' => '/services/shopping_mall.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
            [
                'slug' => 'king-fahd-fountain',
                'name' => 'تجربة نافورة الملك فهد',
                'description' => 'زيارة أطول نافورة مياه في العالم التي تصل إلى ارتفاع 312 متر. أفضل نقاط المشاهدة وفرص التصوير والعرض الضوئي المسائي. يشمل المرطبات والنقل.',
                'price' => 60.00,
                'image_url' => '/services/king_fahd_fountain.jpg',
                'category_id' => $categories['explore-jeddah'],
            ],
        ]);
    }
}
