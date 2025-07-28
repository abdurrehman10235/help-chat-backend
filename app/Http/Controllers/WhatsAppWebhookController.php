<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Api\ServiceController;

class WhatsAppWebhookController extends Controller
{
    private $messages = [
        'en' => [
            'welcome' => "👋 Welcome to Hotel Service Assistant!\n\n🏨 **Service Categories:**\n\n🚗 **Pre-Arrival** - Airport pickup, Early check-in, Room preferences\n📍 **Arrival** - Welcome drink, Luggage assistance, Express check-in\n🛎️ **In-Stay** - Room service, Laundry, Spa services\n✈️ **Departure** - Late checkout, Baggage hold, Airport drop-off\n\n💡 **Quick Tips:**\n• Choose a category or ask for a specific service\n• Type 'services' to see all services\n• Voice messages supported 🎤\n\nWhat can I help you with today?",
            'categories' => "🏨 **Service Categories:**\n\n🚗 **Pre-Arrival** - Services before you arrive\n📍 **Arrival** - Services when you check-in\n🛎️ **In-Stay** - Services during your stay\n✈️ **Departure** - Services when you leave\n\n💡 Choose a category or ask for a specific service!",
            'found' => "✨ Here's what I found:",
            'price' => "Price",
            'noResults' => "😔 Sorry, I couldn't find that service.\n\n💡 **Try:**\n• Room service, Spa, Airport pickup\n• Or type 'categories' to browse all services\n• Voice messages work too! 🎤",
            'error' => "❌ Sorry, something went wrong. Please try again or type 'categories' to start over.",
            'suggestion' => "🤔 Did you mean",
            'voiceReceived' => "🎤 Voice message received! I can understand voice messages.\n\n💡 Just speak naturally - I'll help you find the right service!",
            'help' => "🤖 **How to use this bot:**\n\n📋 **Quick Commands:**\n• 'categories' - Browse service categories\n• 'services' - See all available services\n• 'help' - Show this help message\n\n🔍 **Search Tips:**\n• Type service names like 'spa', 'room service', 'airport'\n• Use category names like 'arrival', 'in-stay'\n• I understand typos and similar words!\n\n🎤 **Voice Messages:**\n• Send voice messages anytime\n• For best results, also try typing your request\n\n💡 Just tell me what you need and I'll help you find it!"
        ],
        'ar' => [
            'welcome' => "👋 أهلاً وسهلاً بك في مساعد خدمات الفندق!\n\n🏨 **فئات الخدمات:**\n\n🚗 **قبل الوصول** - نقل المطار، تسجيل دخول مبكر، تفضيلات الغرفة\n📍 **الوصول** - مشروب ترحيب، مساعدة الأمتعة، تسجيل دخول سريع\n🛎️ **أثناء الإقامة** - خدمة الغرف، الغسيل، خدمات السبا\n✈️ **المغادرة** - تسجيل خروج متأخر، حفظ الأمتعة، توصيل المطار\n\n💡 **نصائح سريعة:**\n• اختر فئة أو اسأل عن خدمة معينة\n• اكتب 'خدمات' لرؤية جميع الخدمات\n• الرسائل الصوتية مدعومة 🎤\n\nكيف يمكنني مساعدتك اليوم؟",
            'categories' => "🏨 **فئات الخدمات:**\n\n🚗 **قبل الوصول** - خدمات قبل وصولك\n📍 **الوصول** - خدمات عند تسجيل الدخول\n🛎️ **أثناء الإقامة** - خدمات أثناء إقامتك\n✈️ **المغادرة** - خدمات عند المغادرة\n\n💡 اختر فئة أو اسأل عن خدمة معينة!",
            'found' => "✨ إليك ما وجدته:",
            'price' => "السعر",
            'noResults' => "😔 عذراً، لم أجد هذه الخدمة.\n\n💡 **جرب:**\n• خدمة الغرف، سبا، نقل المطار\n• أو اكتب 'فئات' لتصفح جميع الخدمات\n• الرسائل الصوتية تعمل أيضاً! 🎤",
            'error' => "❌ عذراً، حدث خطأ. يرجى المحاولة مرة أخرى أو اكتب 'فئات' للبدء من جديد.",
            'suggestion' => "🤔 هل تقصد",
            'voiceReceived' => "🎤 تم استلام الرسالة الصوتية! يمكنني فهم الرسائل الصوتية.\n\n💡 تحدث بشكل طبيعي - سأساعدك في العثور على الخدمة المناسبة!",
            'help' => "🤖 **كيفية استخدام هذا البوت:**\n\n📋 **أوامر سريعة:**\n• 'فئات' - تصفح فئات الخدمات\n• 'خدمات' - عرض جميع الخدمات المتاحة\n• 'مساعدة' - عرض رسالة المساعدة هذه\n\n🔍 **نصائح البحث:**\n• اكتب أسماء الخدمات مثل 'سبا'، 'خدمة الغرف'، 'المطار'\n• استخدم أسماء الفئات مثل 'الوصول'، 'أثناء الإقامة'\n• أفهم الأخطاء الإملائية والكلمات المشابهة!\n\n🎤 **الرسائل الصوتية:**\n• أرسل رسائل صوتية في أي وقت\n• للحصول على أفضل النتائج، جرب أيضاً كتابة طلبك\n\n💡 فقط أخبرني بما تحتاجه وسأساعدك في العثور عليه!"
        ]
    ];

    /**
     * Verify webhook (GET request)
     */
    public function verify(Request $request)
    {
        $verifyToken = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');
        $mode = $request->get('hub_mode');
        
        Log::info('Webhook verification attempt', [
            'mode' => $mode,
            'token' => $verifyToken,
            'challenge' => $challenge
        ]);
        
        if ($mode === 'subscribe' && $verifyToken === env('WHATSAPP_WEBHOOK_VERIFY_TOKEN')) {
            Log::info('Webhook verified successfully');
            return response($challenge, 200);
        }
        
        Log::error('Webhook verification failed', [
            'expected_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
            'received_token' => $verifyToken
        ]);
        
        return response('Unauthorized', 403);
    }

    /**
     * Handle incoming webhooks (POST request)
     */
    public function handle(Request $request)
    {
        try {
            Log::info('WhatsApp Webhook received:', $request->all());
            
            $entry = $request->input('entry', []);
            
            foreach ($entry as $entryItem) {
                $changes = $entryItem['changes'] ?? [];
                
                foreach ($changes as $change) {
                    if ($change['field'] === 'messages') {
                        $value = $change['value'];
                        
                        // Process incoming messages
                        $messages = $value['messages'] ?? [];
                        foreach ($messages as $message) {
                            $this->processMessage($message);
                        }
                        
                        // Process message status updates
                        $statuses = $value['statuses'] ?? [];
                        foreach ($statuses as $status) {
                            $this->processMessageStatus($status);
                        }
                    }
                }
            }
            
            return response('OK', 200);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response('Error', 500);
        }
    }

    /**
     * Process individual message
     */
    private function processMessage($message)
    {
        $from = $message['from'];
        $messageId = $message['id'];
        $timestamp = $message['timestamp'];
        $type = $message['type'] ?? 'unknown';
        
        Log::info('Processing message', [
            'from' => $from,
            'type' => $type,
            'id' => $messageId
        ]);

        // Get user language preference
        $userLang = $this->getUserLanguage($from);
        
        try {
            // Handle different message types
            switch ($type) {
                case 'text':
                    $this->handleTextMessage($from, $message['text']['body'], $userLang);
                    break;
                    
                case 'audio':
                    $this->handleVoiceMessage($from, $message['audio'], $userLang);
                    break;
                    
                case 'voice':
                    $this->handleVoiceMessage($from, $message['voice'], $userLang);
                    break;
                    
                case 'image':
                    $this->handleMediaMessage($from, 'image', $userLang);
                    break;
                    
                case 'video':
                    $this->handleMediaMessage($from, 'video', $userLang);
                    break;
                    
                case 'document':
                    $this->handleMediaMessage($from, 'document', $userLang);
                    break;
                    
                default:
                    $this->handleUnsupportedMessage($from, $type, $userLang);
                    break;
            }
            
        } catch (\Exception $e) {
            Log::error('Message processing error:', [
                'error' => $e->getMessage(),
                'from' => $from,
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            
            $msgs = $userLang ? $this->messages[$userLang] : $this->messages['en'];
            $this->sendMessage($from, $msgs['error']);
        }
    }

    /**
     * Handle text messages with new hotel flow
     */
    private function handleTextMessage($from, $text, $userLang)
    {
        $userText = trim($text);
        $lowerText = strtolower($userText);
        
        // Auto-detect language from input text
        $hasArabicChars = preg_match('/[\x{0600}-\x{06FF}]/u', $userText);
        $detectedLang = $hasArabicChars ? 'ar' : 'en';
        
        // Set user language if not set or if language preference changes
        if (!$userLang || $userLang !== $detectedLang) {
            $userLang = $detectedLang;
            $this->setUserLanguage($from, $userLang);
        }
        
        Log::info('Text message received', [
            'from' => $from,
            'text' => $userText,
            'detected_language' => $detectedLang,
            'user_language' => $userLang
        ]);

        // Get user's current context/state
        $userState = $this->getUserState($from);
        
        // Handle navigation commands first
        if ($this->isBackCommand($lowerText, $userLang)) {
            $this->handleBackNavigation($from, $userLang, $userState);
            return;
        }
        
        if ($this->isMainMenuCommand($lowerText, $userLang)) {
            $this->sendWelcomeMessage($from, $userLang);
            $this->setUserState($from, 'main');
            return;
        }
        
        // Handle based on current state
        switch ($userState) {
            case 'wake-up-call':
                $this->handleWakeUpTimeInput($from, $userText, $userLang);
                break;
                
            case 'visitor-invitation':
                $this->handleVisitorNameInput($from, $userText, $userLang);
                break;
                
            default:
                $this->handleMainFlowInput($from, $lowerText, $userLang);
                break;
        }
    }
    
    /**
     * Handle main conversation flow
     */
    private function handleMainFlowInput($from, $lowerText, $userLang)
    {
        // Welcome/greeting messages
        if ($this->isWelcomeMessage($lowerText, $userLang)) {
            $this->sendWelcomeMessage($from, $userLang);
            $this->setUserState($from, 'main');
            return;
        }
        
        // Main categories
        if ($this->isHotelTourRequest($lowerText, $userLang)) {
            $this->sendHotelTourMenu($from, $userLang);
            $this->setUserState($from, 'hotel-tour');
            return;
        }
        
        if ($this->isExploreJeddahRequest($lowerText, $userLang)) {
            $this->sendExploreJeddahMenu($from, $userLang);
            $this->setUserState($from, 'explore-jeddah');
            return;
        }
        
        // Hotel tour services
        if ($this->isRestaurantRequest($lowerText, $userLang)) {
            $this->sendRestaurantInfo($from, $userLang);
            $this->setUserState($from, 'restaurant');
            return;
        }
        
        if ($this->isRoomServiceRequest($lowerText, $userLang)) {
            $this->sendRoomServiceMenu($from, $userLang);
            $this->setUserState($from, 'room-service');
            return;
        }
        
        if ($this->isLaundryRequest($lowerText, $userLang)) {
            $this->sendLaundryInfo($from, $userLang);
            $this->setUserState($from, 'laundry');
            return;
        }
        
        if ($this->isGymRequest($lowerText, $userLang)) {
            $this->sendGymInfo($from, $userLang);
            $this->setUserState($from, 'gym');
            return;
        }
        
        if ($this->isReceptionRequest($lowerText, $userLang)) {
            $this->sendReceptionMenu($from, $userLang);
            $this->setUserState($from, 'reception');
            return;
        }
        
        // Reception services
        if ($this->isWakeUpCallRequest($lowerText, $userLang)) {
            $this->sendWakeUpCallPrompt($from, $userLang);
            $this->setUserState($from, 'wake-up-call');
            return;
        }
        
        if ($this->isVisitorInvitationRequest($lowerText, $userLang)) {
            $this->sendVisitorInvitationPrompt($from, $userLang);
            $this->setUserState($from, 'visitor-invitation');
            return;
        }
        
        // Try intelligent service search for unmatched text
        $searchResult = $this->searchForServices($from, $lowerText, $userLang);
        
        // If no service found, send welcome message
        if (!$searchResult) {
            $this->sendWelcomeMessage($from, $userLang);
            $this->setUserState($from, 'main');
        }
    }

    /**
     * Enhanced service search with better matching
     */
    private function searchForServices($from, $text, $userLang)
    {
        try {
            // Auto-detect search language
            $hasArabicChars = preg_match('/[\x{0600}-\x{06FF}]/u', $text);
            $searchLang = $hasArabicChars ? 'ar' : 'en';
            
            Log::info('Enhanced service search', [
                'from' => $from,
                'text' => $text,
                'search_lang' => $searchLang,
                'user_lang' => $userLang
            ]);
            
            // Enhanced direct service search with better matching
            $directMatch = $this->findEnhancedServiceMatch($text, $searchLang);
            
            if ($directMatch) {
                $this->sendServiceResult($from, $directMatch, $userLang);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Enhanced service search error:', [
                'error' => $e->getMessage(),
                'from' => $from,
                'text' => $text
            ]);
            return false;
        }
    }

    /**
     * Enhanced service matching with better fuzzy logic
     */
    private function findEnhancedServiceMatch($text, $searchLang)
    {
        $table = $searchLang === 'ar' ? 'services_ar' : 'services_en';
        $services = DB::table($table)->get();
        
        $normalize = function($string) use ($searchLang) {
            $string = trim(strtolower($string));
            if ($searchLang === 'ar') {
                // Remove Arabic diacritics
                $string = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $string);
            } else {
                // Remove punctuation and normalize spaces
                $string = preg_replace('/[^a-z0-9\s]/', ' ', $string);
                $string = preg_replace('/\s+/', ' ', $string);
            }
            return $string;
        };
        
        $inputNorm = $normalize($text);
        $inputWords = explode(' ', $inputNorm);
        
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($services as $service) {
            $nameNorm = $normalize($service->name);
            $descNorm = $normalize($service->description ?? '');
            $score = 0;
            
            // Exact name match (highest priority)
            if ($nameNorm === $inputNorm) {
                return $service;
            }
            
            // Enhanced keyword matching for specific services
            $enhancedScore = $this->getEnhancedServiceScore($inputNorm, $inputWords, $service, $nameNorm, $descNorm);
            $score += $enhancedScore;
            
            // Check if input contains service name words
            $nameWords = explode(' ', $nameNorm);
            foreach ($nameWords as $nameWord) {
                if (strlen($nameWord) > 2) {
                    if (strpos($inputNorm, $nameWord) !== false) {
                        $score += 40;
                    }
                }
            }
            
            // Check if service name contains input words
            foreach ($inputWords as $inputWord) {
                if (strlen($inputWord) > 2) {
                    if (strpos($nameNorm, $inputWord) !== false) {
                        $score += 30;
                    }
                    if (strpos($descNorm, $inputWord) !== false) {
                        $score += 15;
                    }
                }
            }
            
            // Similar text scoring
            similar_text($inputNorm, $nameNorm, $percent);
            $score += $percent * 0.8;
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $service;
            }
        }
        
        // Return if we have a good match (lowered threshold for better matching)
        return $highestScore > 35 ? $bestMatch : null;
    }

    /**
     * Enhanced scoring for specific service patterns
     */
    private function getEnhancedServiceScore($inputNorm, $inputWords, $service, $nameNorm, $descNorm)
    {
        $score = 0;
        $slug = $service->slug ?? '';
        
        // Special matching patterns for common queries
        $patterns = [
            // Al-Balad patterns
            'balad' => ['al-balad', 'balad-site', 'historic'],
            'historic' => ['al-balad', 'balad-site', 'historic'],
            'al balad' => ['al-balad', 'balad-site', 'historic'],
            'old town' => ['al-balad', 'balad-site', 'historic'],
            'unesco' => ['al-balad', 'balad-site', 'historic'],
            
            // Room service patterns
            'room service' => ['room-service', 'club-sandwich', 'pasta-alfredo', 'grilled-salmon'],
            'food' => ['room-service', 'restaurant', 'club-sandwich', 'pasta-alfredo'],
            'meal' => ['room-service', 'restaurant', 'club-sandwich', 'pasta-alfredo'],
            'order' => ['room-service', 'club-sandwich', 'pasta-alfredo'],
            
            // Laundry patterns
            'laundry' => ['laundry'],
            'washing' => ['laundry'],
            'cleaning' => ['laundry'],
            'clothes' => ['laundry'],
            
            // Restaurant patterns
            'restaurant' => ['restaurant'],
            'dining' => ['restaurant'],
            'buffet' => ['restaurant'],
            
            // Gym patterns
            'gym' => ['gym'],
            'fitness' => ['gym'],
            'exercise' => ['gym'],
            'workout' => ['gym'],
            
            // Reception patterns
            'reception' => ['reception', 'wake-up-call', 'visitor-invitation'],
            'front desk' => ['reception'],
            'concierge' => ['reception'],
            'wake up' => ['wake-up-call'],
            'visitor' => ['visitor-invitation'],
            
            // Jeddah patterns
            'jeddah' => ['jeddah-resorts', 'balad-site', 'corniche-site', 'king-fahd-fountain'],
            'corniche' => ['corniche-site'],
            'fountain' => ['king-fahd-fountain'],
            'king fahd' => ['king-fahd-fountain'],
            'shopping' => ['shopping-mall'],
            'mall' => ['shopping-mall'],
            'resort' => ['jeddah-resorts'],
        ];
        
        // Check for pattern matches
        foreach ($patterns as $pattern => $targetSlugs) {
            if (strpos($inputNorm, $pattern) !== false && in_array($slug, $targetSlugs)) {
                $score += 80;
                break;
            }
        }
        
        return $score;
    }

    // Helper methods for message detection
    private function isWelcomeMessage($text, $lang)
    {
        $welcomeKeywords = [
            'en' => ['hi', 'hello', 'hey', 'start', 'welcome'],
            'ar' => ['مرحبا', 'أهلا', 'السلام عليكم', 'مساء الخير', 'صباح الخير']
        ];
        
        foreach ($welcomeKeywords[$lang] ?? $welcomeKeywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isBackCommand($text, $lang)
    {
        $backKeywords = [
            'en' => ['back', 'return', 'previous'],
            'ar' => ['رجوع', 'عودة', 'السابق']
        ];
        
        foreach ($backKeywords[$lang] ?? $backKeywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isMainMenuCommand($text, $lang)
    {
        $mainMenuKeywords = [
            'en' => ['main menu', 'home', 'start over'],
            'ar' => ['القائمة الرئيسية', 'الرئيسية', 'البداية']
        ];
        
        foreach ($mainMenuKeywords[$lang] ?? $mainMenuKeywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isHotelTourRequest($text, $lang)
    {
        $keywords = [
            'en' => ['hotel tour', 'hotel services', 'services', 'facilities'],
            'ar' => ['جولة الفندق', 'خدمات الفندق', 'المرافق']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isExploreJeddahRequest($text, $lang)
    {
        $keywords = [
            'en' => ['explore jeddah', 'jeddah tour', 'attractions', 'tourism'],
            'ar' => ['استكشاف جدة', 'جولة جدة', 'السياحة', 'المعالم']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isRestaurantRequest($text, $lang)
    {
        $keywords = [
            'en' => ['restaurant', 'dining', 'buffet', 'food'],
            'ar' => ['مطعم', 'طعام', 'بوفيه', 'غداء', 'عشاء']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isRoomServiceRequest($text, $lang)
    {
        $keywords = [
            'en' => ['room service', 'order food', 'menu', 'delivery'],
            'ar' => ['خدمة الغرف', 'طلب طعام', 'قائمة الطعام', 'توصيل']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isLaundryRequest($text, $lang)
    {
        $keywords = [
            'en' => ['laundry', 'washing', 'cleaning', 'clothes'],
            'ar' => ['غسيل', 'تنظيف', 'ملابس', 'مغسلة']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isGymRequest($text, $lang)
    {
        $keywords = [
            'en' => ['gym', 'fitness', 'exercise', 'workout'],
            'ar' => ['صالة رياضية', 'لياقة', 'تمارين', 'نادي']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isReceptionRequest($text, $lang)
    {
        $keywords = [
            'en' => ['reception', 'front desk', 'concierge', 'help'],
            'ar' => ['الاستقبال', 'مكتب الاستقبال', 'مساعدة', 'خدمة العملاء']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isWakeUpCallRequest($text, $lang)
    {
        $keywords = [
            'en' => ['wake up', 'wake me', 'alarm', 'call me'],
            'ar' => ['إيقاظ', 'اتصال إيقاظ', 'منبه', 'اتصل بي']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function isVisitorInvitationRequest($text, $lang)
    {
        $keywords = [
            'en' => ['visitor', 'guest', 'invite', 'someone coming'],
            'ar' => ['زائر', 'ضيف', 'دعوة', 'شخص قادم']
        ];
        
        foreach ($keywords[$lang] ?? $keywords['en'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    // Helper methods for sending messages
    private function sendWelcomeMessage($from, $lang)
    {
        // Check if we should show the full branded header
        if ($this->shouldShowBrandedWelcome($from)) {
            $this->sendBrandedWelcomeHeader($from, $lang);
        } else {
            // Send shorter welcome for returning users
            $this->sendQuickWelcome($from, $lang);
        }
    }

    private function shouldShowBrandedWelcome($from)
    {
        $lastSeen = session("last_seen_$from");
        $sixHoursAgo = time() - (6 * 60 * 60); // 6 hours in seconds
        
        // Show branded welcome if user is new or hasn't been seen in 6+ hours
        return !$lastSeen || $lastSeen < $sixHoursAgo;
    }

    private function sendBrandedWelcomeHeader($from, $lang)
    {
        // Update last seen timestamp
        session(["last_seen_$from" => time()]);
        
        $header = [
            'en' => "
🏨 **ELITE HOTEL CASABLANCA** 🏨
═══════════════════════════════════════
✨ *Luxury • Comfort • Excellence* ✨
═══════════════════════════════════════

🌟 Welcome Mr. Ali 🌟
Thank You for Visiting Elite Hotel Casablanca

📶 *Your WiFi Password:* **183738134**
🔑 *Your Digital Concierge is Ready*

Have a pleasant stay with us!

How can I assist you today?

🏨 *1️⃣ Hotel Tour* - Explore our facilities
🌃 *2️⃣ Explore Jeddah* - Discover the city

💡 Type *Main Menu* anytime to return here.",

            'ar' => "
🏨 **فندق إليت الدار البيضاء** 🏨
═══════════════════════════════════════
✨ *الفخامة • الراحة • التميز* ✨
═══════════════════════════════════════

🌟 مرحباً سيد علي 🌟
شكراً لزيارتكم فندق إليت الدار البيضاء

📶 *كلمة مرور الواي فاي:* **183738134**
🔑 *مساعدكم الرقمي جاهز للخدمة*

نتمنى لكم إقامة سعيدة!

كيف يمكنني مساعدتك اليوم؟

🏨 *1️⃣ جولة الفندق* - استكشف مرافقنا
🌃 *2️⃣ استكشاف جدة* - اكتشف المدينة

💡 اكتب *القائمة الرئيسية* في أي وقت للعودة هنا."
        ];
        
        // Send the branded welcome message with hotel logo
        $logoUrl = url('/logo.jpg');
        $this->sendMessageWithImage($from, $header[$lang] ?? $header['en'], $logoUrl);
    }

    private function sendQuickWelcome($from, $lang)
    {
        // Update last seen timestamp
        session(["last_seen_$from" => time()]);
        
        $message = [
            'en' => "🏨 *Elite Hotel Casablanca* 🏨\n\nWelcome back, Mr. Ali!\n\nHow can I help you today?\n\n1️⃣ *Hotel Tour* - Explore our facilities\n2️⃣ *Explore Jeddah* - Discover the city\n\nType *Main Menu* anytime to return here.",
            'ar' => "🏨 *فندق إليت الدار البيضاء* 🏨\n\nمرحباً بعودتك، سيد علي!\n\nكيف يمكنني مساعدتك اليوم؟\n\n1️⃣ *جولة الفندق* - استكشف مرافقنا\n2️⃣ *استكشاف جدة* - اكتشف المدينة\n\nاكتب *القائمة الرئيسية* في أي وقت للعودة هنا."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendHotelTourMenu($from, $lang)
    {
        $message = [
            'en' => "*🏨 Hotel Tour - Our Services*\n\n1️⃣ *Restaurant* - Dining & Buffet\n2️⃣ *Room Service* - Order to your room\n3️⃣ *Laundry* - Cleaning services\n4️⃣ *Gym* - Fitness center\n5️⃣ *Reception* - Front desk services\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*🏨 جولة الفندق - خدماتنا*\n\n1️⃣ *المطعم* - الطعام والبوفيه\n2️⃣ *خدمة الغرف* - اطلب إلى غرفتك\n3️⃣ *الغسيل* - خدمات التنظيف\n4️⃣ *الصالة الرياضية* - مركز اللياقة\n5️⃣ *الاستقبال* - خدمات مكتب الاستقبال\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendExploreJeddahMenu($from, $lang)
    {
        $message = [
            'en' => "*🌃 Explore Jeddah - City Attractions*\n\nDiscover the beauty of Jeddah with our curated experiences:\n\n• *Al-Balad Historic District* - UNESCO World Heritage\n• *King Fahd Fountain* - World's tallest fountain\n• *Jeddah Corniche* - Beautiful waterfront\n• *Red Sea Mall* - Shopping & entertainment\n• *Floating Mosque* - Iconic architecture\n\nPrices range from $40-$150 per person\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*🌃 استكشاف جدة - معالم المدينة*\n\nاكتشف جمال جدة مع تجاربنا المختارة:\n\n• *البلد التاريخية* - تراث اليونسكو العالمي\n• *نافورة الملك فهد* - أطول نافورة في العالم\n• *كورنيش جدة* - الواجهة البحرية الجميلة\n• *ريد سي مول* - التسوق والترفيه\n• *المسجد العائم* - عمارة مميزة\n\nالأسعار تتراوح من 40-150 دولار للشخص\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    // State management methods
    private function getUserState($from)
    {
        // For now, store in session or cache. In production, use database
        return session("user_state_$from", 'main');
    }

    private function setUserState($from, $state)
    {
        session(["user_state_$from" => $state]);
    }

    // Navigation handlers
    private function handleBackNavigation($from, $userLang, $currentState)
    {
        // Handle back navigation based on current state
        switch ($currentState) {
            case 'hotel-tour':
            case 'explore-jeddah':
                $this->sendWelcomeMessage($from, $userLang);
                $this->setUserState($from, 'main');
                break;
            
            case 'restaurant':
            case 'room-service':
            case 'laundry':
            case 'gym':
            case 'reception':
                $this->sendHotelTourMenu($from, $userLang);
                $this->setUserState($from, 'hotel-tour');
                break;
                
            case 'wake-up-call':
            case 'visitor-invitation':
                $this->sendReceptionMenu($from, $userLang);
                $this->setUserState($from, 'reception');
                break;
                
            default:
                $this->sendWelcomeMessage($from, $userLang);
                $this->setUserState($from, 'main');
                break;
        }
    }

    // Service-specific message senders
    private function sendRestaurantInfo($from, $lang)
    {
        $message = [
            'en' => "*🍽️ Restaurant - Elite Casablanca*\n\n*Buffet Hours:* 7:00 PM - 10:00 PM\n*Location:* Ground Floor\n*Cuisine:* International & Local\n\n✨ *Tonight's Special Menu:*\n• Fresh Seafood\n• Grilled Specialties\n• Vegetarian Options\n• Dessert Selection\n\n*Dress Code:* Smart Casual\n*Reservations:* Recommended\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*🍽️ المطعم - إليت الدار البيضاء*\n\n*ساعات البوفيه:* 7:00 مساءً - 10:00 مساءً\n*الموقع:* الطابق الأرضي\n*المطبخ:* عالمي ومحلي\n\n✨ *قائمة الليلة الخاصة:*\n• المأكولات البحرية الطازجة\n• المشاوي المتخصصة\n• خيارات نباتية\n• تشكيلة من الحلويات\n\n*قواعد الملبس:* كاجوال أنيق\n*الحجوزات:* مُستحسنة\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendRoomServiceMenu($from, $lang)
    {
        $message = [
            'en' => "*🍽️ Room Service Menu*\n\n*Available 24/7*\n\n🥗 *Appetizers & Salads*\n• Caesar Salad - $45\n• Mediterranean Mezze - $55\n\n🍖 *Main Courses*\n• Grilled Salmon - $75\n• Chicken Tikka - $65\n• Beef Tenderloin - $85\n• Vegetarian Pasta - $55\n\n🍰 *Desserts*\n• Chocolate Cake - $25\n• Fresh Fruit Platter - $30\n\n☕ *Beverages*\n• Fresh Juice - $15\n• Coffee/Tea - $10\n\n⏰ *Delivery Time:* 30-45 minutes\n💰 *Service Charge:* Included\n\nTo order, call Reception or reply with item name.\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*🍽️ قائمة خدمة الغرف*\n\n*متاحة 24/7*\n\n🥗 *المقبلات والسلطات*\n• سلطة قيصر - 45 دولار\n• مزة متوسطية - 55 دولار\n\n🍖 *الأطباق الرئيسية*\n• سلمون مشوي - 75 دولار\n• دجاج تكا - 65 دولار\n• لحم بقر تندرلوين - 85 دولار\n• باستا نباتية - 55 دولار\n\n🍰 *الحلويات*\n• كيكة الشوكولاتة - 25 دولار\n• طبق فواكه طازجة - 30 دولار\n\n☕ *المشروبات*\n• عصير طازج - 15 دولار\n• قهوة/شاي - 10 دولار\n\n⏰ *وقت التوصيل:* 30-45 دقيقة\n💰 *رسوم الخدمة:* مشمولة\n\nللطلب، اتصل بالاستقبال أو رد باسم الصنف.\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendLaundryInfo($from, $lang)
    {
        $message = [
            'en' => "*🧺 Laundry Service*\n\n*Service Hours:* 8:00 AM - 8:00 PM\n*Price:* $25 per load\n\n📋 *Services Available:*\n• Washing & Drying\n• Dry Cleaning\n• Ironing\n• Same-day service available\n\n⏰ *Turnaround Time:*\n• Standard: 24 hours\n• Express: 6 hours (+$10)\n\n📞 *How to Order:*\nCall Reception or place items in laundry bag provided in your room.\n\n*Pickup:* 9:00 AM & 6:00 PM daily\n*Delivery:* Next day by 7:00 PM\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*🧺 خدمة الغسيل*\n\n*ساعات الخدمة:* 8:00 صباحاً - 8:00 مساءً\n*السعر:* 25 دولار للحمولة الواحدة\n\n📋 *الخدمات المتاحة:*\n• الغسيل والتجفيف\n• التنظيف الجاف\n• الكوي\n• خدمة نفس اليوم متاحة\n\n⏰ *وقت التسليم:*\n• عادي: 24 ساعة\n• سريع: 6 ساعات (+10 دولار)\n\n📞 *كيفية الطلب:*\nاتصل بالاستقبال أو ضع الملابس في كيس الغسيل المتوفر في غرفتك.\n\n*الاستلام:* 9:00 صباحاً و 6:00 مساءً يومياً\n*التسليم:* اليوم التالي بحلول 7:00 مساءً\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendGymInfo($from, $lang)
    {
        $message = [
            'en' => "*💪 Fitness Center*\n\n*Hours:* 5:00 AM - 11:00 PM daily\n*Location:* 2nd Floor\n*Access:* Free for all guests\n\n🏋️ *Equipment Available:*\n• Cardio machines (treadmills, bikes)\n• Weight training equipment\n• Free weights\n• Yoga mats\n• Towel service\n\n🏊 *Additional Facilities:*\n• Swimming pool access\n• Sauna (6:00 AM - 10:00 PM)\n• Changing rooms with lockers\n\n📋 *Rules:*\n• Proper gym attire required\n• Maximum 90 minutes per session\n• No food or drinks (water allowed)\n\n*Personal Trainer:* Available upon request ($50/hour)\n\nType *Back* to return or *Main Menu* for home.",
            'ar' => "*💪 مركز اللياقة البدنية*\n\n*الساعات:* 5:00 صباحاً - 11:00 مساءً يومياً\n*الموقع:* الطابق الثاني\n*الدخول:* مجاني لجميع النزلاء\n\n🏋️ *المعدات المتاحة:*\n• آلات الكارديو (مشايات، دراجات)\n• معدات تدريب الأوزان\n• أوزان حرة\n• حصائر يوغا\n• خدمة المناشف\n\n🏊 *مرافق إضافية:*\n• دخول إلى المسبح\n• ساونا (6:00 صباحاً - 10:00 مساءً)\n• غرف تغيير الملابس مع خزائن\n\n📋 *القواعد:*\n• ملابس رياضية مناسبة مطلوبة\n• حد أقصى 90 دقيقة لكل جلسة\n• ممنوع الطعام أو المشروبات (الماء مسموح)\n\n*مدرب شخصي:* متاح عند الطلب (50 دولار/ساعة)\n\nاكتب *رجوع* للعودة أو *القائمة الرئيسية* للصفحة الرئيسية."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendReceptionMenu($from, $lang)
    {
        $message = [
            'en' => "*📞 Reception Services*\n\n*Available 24/7*\n\n🛎️ *Our Services:*\n1️⃣ *Wake-up Call* - Set your morning call\n2️⃣ *Visitor Invitation* - Register a guest\n3️⃣ *General Assistance* - Any other help\n\n📋 *Additional Services:*\n• Taxi/Transportation booking\n• Restaurant reservations\n• Tour arrangements\n• Lost & Found\n• Room maintenance requests\n\n📞 *Direct Line:* Available in your room\n📱 *WhatsApp:* You're already here!\n\nSelect a service above or type *Back* to return to Hotel Tour.\n\nType *Main Menu* anytime to start over.",
            'ar' => "*📞 خدمات الاستقبال*\n\n*متاحة 24/7*\n\n🛎️ *خدماتنا:*\n1️⃣ *اتصال الإيقاظ* - حدد مكالمة الصباح\n2️⃣ *دعوة زائر* - سجل ضيف\n3️⃣ *مساعدة عامة* - أي مساعدة أخرى\n\n📋 *خدمات إضافية:*\n• حجز تاكسي/مواصلات\n• حجز مطاعم\n• ترتيب الجولات\n• المفقودات والمعثورات\n• طلبات صيانة الغرف\n\n📞 *الخط المباشر:* متاح في غرفتك\n📱 *واتساب:* أنت هنا بالفعل!\n\nاختر خدمة أعلاه أو اكتب *رجوع* للعودة إلى جولة الفندق.\n\nاكتب *القائمة الرئيسية* في أي وقت للبدء من جديد."
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendWakeUpCallPrompt($from, $lang)
    {
        $message = [
            'en' => "*⏰ Wake-up Call Service*\n\nI'll help you set up a wake-up call!\n\n📝 *Please tell me:*\nWhen would you like to be woken up?\n\n💡 *Examples:*\n• \"Tomorrow at 7 AM\"\n• \"In 8 hours\"\n• \"6:30 in the morning\"\n• \"After 3 hours\"\n\n⏰ Your wake-up call will be delivered via WhatsApp and phone call.\n\nType your preferred time now:",
            'ar' => "*⏰ خدمة اتصال الإيقاظ*\n\nسأساعدك في إعداد مكالمة إيقاظ!\n\n📝 *يرجى إخباري:*\nمتى تريد أن يتم إيقاظك؟\n\n💡 *أمثلة:*\n• \"غداً في الساعة 7 صباحاً\"\n• \"بعد 8 ساعات\"\n• \"6:30 صباحاً\"\n• \"بعد 3 ساعات\"\n\n⏰ سيتم تسليم مكالمة الإيقاظ عبر واتساب ومكالمة هاتفية.\n\nاكتب الوقت المفضل الآن:"
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    private function sendVisitorInvitationPrompt($from, $lang)
    {
        $message = [
            'en' => "*👥 Visitor Invitation*\n\nI'll help you register a visitor!\n\n📝 *Please provide:*\nThe full name of your visitor\n\n💡 *Example:*\n• \"Ahmed Mohammed\"\n• \"Sarah Johnson\"\n• \"Dr. Ali Hassan\"\n\n🛂 *What happens next:*\n• Your visitor will be added to our guest list\n• Reception will be notified\n• They can mention your room number at the front desk\n\n📝 Please type the visitor's full name:",
            'ar' => "*👥 دعوة زائر*\n\nسأساعدك في تسجيل زائر!\n\n📝 *يرجى تقديم:*\nالاسم الكامل لزائرك\n\n💡 *مثال:*\n• \"أحمد محمد\"\n• \"سارة جونسون\"\n• \"د. علي حسن\"\n\n🛂 *ما سيحدث بعد ذلك:*\n• سيتم إضافة زائرك إلى قائمة الضيوف\n• سيتم إخطار الاستقبال\n• يمكنهم ذكر رقم غرفتك في مكتب الاستقبال\n\n📝 يرجى كتابة الاسم الكامل للزائر:"
        ];
        
        $this->sendMessage($from, $message[$lang] ?? $message['en']);
    }

    // Input handlers for specific states
    private function handleWakeUpTimeInput($from, $userText, $userLang)
    {
        // Parse the wake-up time input
        $timeInput = trim($userText);
        
        // Simple time parsing - in a real app, you'd use more sophisticated parsing
        $successMessage = [
            'en' => "✅ *Wake-up Call Scheduled!*\n\n⏰ *Your Request:* $timeInput\n\n📞 You will receive:\n• WhatsApp message\n• Phone call to your room\n\n✨ *Confirmation:* Your wake-up call has been registered with reception.\n\n*Need to change it?* Just send another wake-up request.\n\nType *Reception* for more services or *Main Menu* to start over.",
            'ar' => "✅ *تم جدولة اتصال الإيقاظ!*\n\n⏰ *طلبك:* $timeInput\n\n📞 ستتلقى:\n• رسالة واتساب\n• مكالمة هاتفية إلى غرفتك\n\n✨ *التأكيد:* تم تسجيل مكالمة الإيقاظ في الاستقبال.\n\n*تريد تغييرها؟* فقط أرسل طلب إيقاظ آخر.\n\nاكتب *الاستقبال* لمزيد من الخدمات أو *القائمة الرئيسية* للبدء من جديد."
        ];
        
        $this->sendMessage($from, $successMessage[$userLang] ?? $successMessage['en']);
        $this->setUserState($from, 'reception'); // Return to reception menu state
    }

    private function handleVisitorNameInput($from, $userText, $userLang)
    {
        // Process the visitor name
        $visitorName = trim($userText);
        
        if (strlen($visitorName) < 2) {
            $errorMessage = [
                'en' => "❌ Please provide a valid visitor name (at least 2 characters).\n\nTry again:",
                'ar' => "❌ يرجى تقديم اسم زائر صحيح (حرفان على الأقل).\n\nحاول مرة أخرى:"
            ];
            $this->sendMessage($from, $errorMessage[$userLang] ?? $errorMessage['en']);
            return;
        }
        
        $successMessage = [
            'en' => "✅ *Visitor Registered Successfully!*\n\n👤 *Visitor Name:* $visitorName\n🏨 *Your Room:* [Room will be auto-detected]\n\n📋 *What's Done:*\n• Added to guest list\n• Reception notified\n• 24-hour access granted\n\n🛂 *Instructions for your visitor:*\n1. Present ID at front desk\n2. Mention your name and room number\n3. They'll be directed to your room\n\n*Need to register another visitor?* Just send their name.\n\nType *Reception* for more services or *Main Menu* to start over.",
            'ar' => "✅ *تم تسجيل الزائر بنجاح!*\n\n👤 *اسم الزائر:* $visitorName\n🏨 *غرفتك:* [سيتم اكتشاف الغرفة تلقائياً]\n\n📋 *ما تم عمله:*\n• الإضافة إلى قائمة الضيوف\n• إخطار الاستقبال\n• منح دخول لمدة 24 ساعة\n\n🛂 *تعليمات لزائرك:*\n1. تقديم الهوية في مكتب الاستقبال\n2. ذكر اسمك ورقم غرفتك\n3. سيتم توجيههم إلى غرفتك\n\n*تريد تسجيل زائر آخر؟* فقط أرسل اسمه.\n\nاكتب *الاستقبال* لمزيد من الخدمات أو *القائمة الرئيسية* للبدء من جديد."
        ];
        
        $this->sendMessage($from, $successMessage[$userLang] ?? $successMessage['en']);
        $this->setUserState($from, 'reception'); // Return to reception menu state
    }

    /**
     * Handle voice messages
     */
    private function handleVoiceMessage($from, $audioData, $userLang)
    {
        Log::info('Voice message received', [
            'from' => $from,
            'audio_id' => $audioData['id'] ?? 'unknown'
        ]);

        // Auto-detect language if not set
        if (!$userLang) {
            $userLang = 'en'; // Default to English for voice messages
            $this->setUserLanguage($from, $userLang);
        }

        try {
            // Send processing message first
            $processingMsg = $userLang === 'ar' 
                ? "🎤 معالجة رسالتك الصوتية... يرجى الانتظار لحظة"
                : "🎤 Processing your voice message... Please wait a moment";
            $this->sendMessage($from, $processingMsg);

            // Transcribe the audio using AssemblyAI
            $transcription = $this->transcribeAudioSimple($audioData['id']);
            
            if ($transcription && !empty(trim($transcription))) {
                Log::info('Voice transcription successful', [
                    'from' => $from,
                    'transcription' => $transcription
                ]);
                
                // Send confirmation of what was heard
                $confirmMsg = $userLang === 'ar' 
                    ? "👂 سمعتك تقول: \"$transcription\"\n\n� البحث عن النتائج..."
                    : "👂 I heard you say: \"$transcription\"\n\n🔍 Searching for results...";
                $this->sendMessage($from, $confirmMsg);
                
                // Process the transcribed text as a regular text message
                $this->handleTextMessage($from, $transcription, $userLang);
            } else {
                // Transcription failed or empty
                $errorMsg = $userLang === 'ar' 
                    ? "😔 عذراً، لم أتمكن من فهم رسالتك الصوتية.\n\n💡 **يرجى المحاولة مرة أخرى:**\n• تحدث بوضوح أكبر\n• تأكد من الهدوء حولك\n• أو اكتب رسالة نصية بدلاً من ذلك\n\n📝 يمكنك أيضاً كتابة 'فئات' أو 'خدمات'"
                    : "😔 Sorry, I couldn't understand your voice message.\n\n💡 **Please try again:**\n• Speak more clearly\n• Ensure it's quiet around you\n• Or send a text message instead\n\n📝 You can also type 'categories' or 'services'";
                $this->sendMessage($from, $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('Voice transcription error', [
                'from' => $from,
                'error' => $e->getMessage()
            ]);
            
            // Send fallback message
            $fallbackMsg = $userLang === 'ar' 
                ? "⚠️ حدث خطأ أثناء معالجة رسالتك الصوتية.\n\n📝 يرجى كتابة رسالة نصية أو اكتب 'مساعدة' للمساعدة."
                : "⚠️ Error processing your voice message.\n\n📝 Please send a text message or type 'help' for assistance.";
            $this->sendMessage($from, $fallbackMsg);
        }
    }

    /**
     * Transcribe audio using AssemblyAI
     */
    private function transcribeAudio($audioId)
    {
        try {
            // Download the audio file from WhatsApp
            $audioUrl = $this->getWhatsAppMediaUrl($audioId);
            if (!$audioUrl) {
                Log::error('Failed to get media URL for audio', ['audio_id' => $audioId]);
                return null;
            }

            // Download the audio file
            $audioContent = $this->downloadWhatsAppMedia($audioUrl);
            if (!$audioContent) {
                Log::error('Failed to download audio content', ['audio_id' => $audioId]);
                return null;
            }

            // Save temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'whatsapp_audio_') . '.ogg';
            file_put_contents($tempFile, $audioContent);

            // Upload audio to AssemblyAI
            $uploadResponse = Http::withHeaders([
                'authorization' => env('ASSEMBLYAI_API_KEY'),
            ])->attach('file', file_get_contents($tempFile), 'audio.ogg')
              ->post('https://api.assemblyai.com/v2/upload');

            if (!$uploadResponse->successful()) {
                Log::error('Failed to upload audio to AssemblyAI', [
                    'audio_id' => $audioId,
                    'response' => $uploadResponse->body()
                ]);
                unlink($tempFile);
                return null;
            }

            $audioUrl = $uploadResponse->json()['upload_url'];

            // Request transcription
            $transcribeResponse = Http::withHeaders([
                'authorization' => env('ASSEMBLYAI_API_KEY'),
                'content-type' => 'application/json',
            ])->post('https://api.assemblyai.com/v2/transcript', [
                'audio_url' => $audioUrl,
                'language_detection' => true,
                'punctuate' => true,
                'format_text' => true,
            ]);

            if (!$transcribeResponse->successful()) {
                Log::error('Failed to request transcription from AssemblyAI', [
                    'audio_id' => $audioId,
                    'response' => $transcribeResponse->body()
                ]);
                unlink($tempFile);
                return null;
            }

            $transcriptId = $transcribeResponse->json()['id'];

            // Poll for completion (max 30 seconds)
            $maxAttempts = 30;
            $attempt = 0;
            
            while ($attempt < $maxAttempts) {
                sleep(1);
                $attempt++;

                $statusResponse = Http::withHeaders([
                    'authorization' => env('ASSEMBLYAI_API_KEY'),
                ])->get("https://api.assemblyai.com/v2/transcript/{$transcriptId}");

                if ($statusResponse->successful()) {
                    $result = $statusResponse->json();
                    
                    if ($result['status'] === 'completed') {
                        unlink($tempFile);
                        
                        $transcription = $result['text'] ?? '';
                        Log::info('Transcription successful', [
                            'audio_id' => $audioId,
                            'transcription' => $transcription,
                            'language' => $result['language_code'] ?? 'unknown'
                        ]);
                        
                        return trim($transcription);
                    } elseif ($result['status'] === 'error') {
                        Log::error('AssemblyAI transcription failed', [
                            'audio_id' => $audioId,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                        unlink($tempFile);
                        return null;
                    }
                }
            }

            // Timeout
            Log::warning('Transcription timeout', ['audio_id' => $audioId]);
            unlink($tempFile);
            return null;

        } catch (\Exception $e) {
            Log::error('Audio transcription failed', [
                'audio_id' => $audioId,
                'error' => $e->getMessage()
            ]);
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            return null;
        }
    }

    /**
     * Simple and fast transcription using AssemblyAI
     */
    private function transcribeAudioSimple($audioId)
    {
        try {
            // Download the audio file from WhatsApp
            $audioUrl = $this->getWhatsAppMediaUrl($audioId);
            if (!$audioUrl) {
                return null;
            }

            $audioContent = $this->downloadWhatsAppMedia($audioUrl);
            if (!$audioContent) {
                return null;
            }

            // Save temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'whatsapp_audio_') . '.ogg';
            file_put_contents($tempFile, $audioContent);

            // Use AssemblyAI's simple API
            $response = Http::timeout(30)->withHeaders([
                'authorization' => env('ASSEMBLYAI_API_KEY'),
            ])->attach('audio', file_get_contents($tempFile), 'audio.ogg')
              ->post('https://api.assemblyai.com/v2/upload');

            if ($response->successful()) {
                $uploadUrl = $response->json()['upload_url'];
                
                // Request transcription
                $transcriptResponse = Http::timeout(30)->withHeaders([
                    'authorization' => env('ASSEMBLYAI_API_KEY'),
                    'content-type' => 'application/json',
                ])->post('https://api.assemblyai.com/v2/transcript', [
                    'audio_url' => $uploadUrl,
                ]);

                if ($transcriptResponse->successful()) {
                    $transcriptId = $transcriptResponse->json()['id'];
                    
                    // Simple polling for completion (max 30 seconds)
                    for ($i = 0; $i < 10; $i++) {
                        sleep(3);
                        
                        $result = Http::timeout(10)->withHeaders([
                            'authorization' => env('ASSEMBLYAI_API_KEY'),
                        ])->get("https://api.assemblyai.com/v2/transcript/{$transcriptId}");
                        
                        if ($result->successful()) {
                            $data = $result->json();
                            if ($data['status'] === 'completed') {
                                unlink($tempFile);
                                return trim($data['text'] ?? '');
                            } elseif ($data['status'] === 'error') {
                                break;
                            }
                        }
                    }
                }
            }

            unlink($tempFile);
            return null;

        } catch (\Exception $e) {
            Log::error('Simple transcription failed', [
                'audio_id' => $audioId,
                'error' => $e->getMessage()
            ]);
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            return null;
        }
    }

    /**
     * Get WhatsApp media URL
     */
    private function getWhatsAppMediaUrl($mediaId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('WHATSAPP_ACCESS_TOKEN')
            ])->get("https://graph.facebook.com/v21.0/{$mediaId}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['url'] ?? null;
            }

            Log::error('Failed to get media URL', [
                'media_id' => $mediaId,
                'response' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error getting media URL', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Download WhatsApp media content
     */
    private function downloadWhatsAppMedia($mediaUrl)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('WHATSAPP_ACCESS_TOKEN')
            ])->get($mediaUrl);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Failed to download media', [
                'media_url' => $mediaUrl,
                'status' => $response->status()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error downloading media', [
                'media_url' => $mediaUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if text is a navigation keyword
     */
    private function isNavigationKeyword($text, $userLang)
    {
        $navigationKeywords = [
            'en' => ['services', 'categories', 'back', 'menu', 'help', 'start', 'home'],
            'ar' => ['خدمات', 'فئات', 'رجوع', 'قائمة', 'مساعدة', 'بداية', 'الرئيسية']
        ];
        
        $keywords = array_merge($navigationKeywords['en'], $navigationKeywords['ar']);
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle navigation commands
     */
    private function handleNavigation($from, $text, $userLang)
    {
        $isServicesRequest = strpos($text, 'services') !== false || strpos($text, 'خدمات') !== false;
        $isCategoriesRequest = strpos($text, 'categories') !== false || strpos($text, 'فئات') !== false;
        $isMenuRequest = strpos($text, 'menu') !== false || strpos($text, 'قائمة') !== false;
        
        if ($isServicesRequest) {
            $this->sendAllServices($from, $userLang);
        } elseif ($isCategoriesRequest || $isMenuRequest) {
            $this->sendMessage($from, $this->messages[$userLang]['categories']);
        } else {
            // Default to welcome message for help/start/home
            $this->sendMessage($from, $this->messages[$userLang]['welcome']);
        }
    }

    /**
     * Check if text is a category request
     */
    private function isCategoryRequest($text, $userLang)
    {
        $categoryKeywords = [
            'en' => [
                'pre-arrival' => ['pre-arrival', 'pre arrival', 'before arrival', 'airport pickup', 'early check'],
                'arrival' => ['arrival', 'check-in', 'check in', 'welcome', 'luggage'],
                'in-stay' => ['in-stay', 'in stay', 'during stay', 'room service', 'spa', 'laundry'],
                'departure' => ['departure', 'check-out', 'check out', 'leaving', 'late checkout']
            ],
            'ar' => [
                'pre-arrival' => ['قبل الوصول', 'ما قبل الوصول', 'نقل المطار', 'تسجيل مبكر'],
                'arrival' => ['الوصول', 'تسجيل الدخول', 'ترحيب', 'أمتعة'],
                'in-stay' => ['أثناء الإقامة', 'خلال الإقامة', 'خدمة الغرف', 'سبا', 'غسيل'],
                'departure' => ['المغادرة', 'تسجيل الخروج', 'خروج متأخر']
            ]
        ];
        
        $allKeywords = array_merge($categoryKeywords['en'], $categoryKeywords['ar']);
        
        foreach ($allKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Handle category requests
     */
    private function handleCategoryRequest($from, $text, $userLang)
    {
        $categoryKeywords = [
            'en' => [
                'pre-arrival' => ['pre-arrival', 'pre arrival', 'before arrival', 'airport pickup', 'early check'],
                'arrival' => ['arrival', 'check-in', 'check in', 'welcome', 'luggage'],
                'in-stay' => ['in-stay', 'in stay', 'during stay', 'room service', 'spa', 'laundry'],
                'departure' => ['departure', 'check-out', 'check out', 'leaving', 'late checkout']
            ],
            'ar' => [
                'pre-arrival' => ['قبل الوصول', 'ما قبل الوصول', 'نقل المطار', 'تسجيل مبكر'],
                'arrival' => ['الوصول', 'تسجيل الدخول', 'ترحيب', 'أمتعة'],
                'in-stay' => ['أثناء الإقامة', 'خلال الإقامة', 'خدمة الغرف', 'سبا', 'غسيل'],
                'departure' => ['المغادرة', 'تسجيل الخروج', 'خروج متأخر']
            ]
        ];
        
        $foundCategory = null;
        $allKeywords = array_merge($categoryKeywords['en'], $categoryKeywords['ar']);
        
        foreach ($allKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $foundCategory = $category;
                    break 2;
                }
            }
        }
        
        if ($foundCategory) {
            $this->sendCategoryServices($from, $foundCategory, $userLang);
        } else {
            $this->sendMessage($from, $this->messages[$userLang]['categories']);
        }
    }

    /**
     * Check if text is a help request
     */
    private function isHelpRequest($text, $userLang)
    {
        $helpKeywords = [
            'en' => ['help', 'what can you do', 'how to use', 'commands', 'instructions'],
            'ar' => ['مساعدة', 'ماذا تستطيع', 'كيف استخدم', 'أوامر', 'تعليمات']
        ];
        
        $allKeywords = array_merge($helpKeywords['en'], $helpKeywords['ar']);
        
        foreach ($allKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if text is a welcome request
     */
    private function isWelcomeRequest($text, $userLang)
    {
        $welcomeKeywords = [
            'en' => ['hi', 'hello', 'hey', 'start'],
            'ar' => ['مرحبا', 'أهلا', 'سلام', 'ابدأ']
        ];
        
        $allKeywords = array_merge($welcomeKeywords['en'], $welcomeKeywords['ar']);
        
        foreach ($allKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Send all services grouped by category
     */
    private function sendAllServices($from, $userLang)
    {
        try {
            $categories = DB::table('service_categories')->get();
            $table = $userLang === 'ar' ? 'services_ar' : 'services_en';
            $categoryNameField = $userLang === 'ar' ? 'name_ar' : 'name_en';
            
            $response = $userLang === 'ar' ? "🏨 **جميع الخدمات:**\n\n" : "🏨 **All Services:**\n\n";
            
            foreach ($categories as $category) {
                $services = DB::table($table)
                    ->where('category_id', $category->id)
                    ->select('name', 'price')
                    ->get();
                
                if ($services->count() > 0) {
                    $categoryIcon = $this->getCategoryIcon($category->slug);
                    $response .= "{$categoryIcon} **{$category->$categoryNameField}:**\n";
                    
                    foreach ($services as $service) {
                        $price = floatval($service->price) > 0 ? " - {$service->price} SAR" : "";
                        $response .= "• {$service->name}{$price}\n";
                    }
                    $response .= "\n";
                }
            }
            
            $footer = $userLang === 'ar' 
                ? "💡 اكتب اسم الخدمة للتفاصيل أو 'فئات' للعودة للفئات"
                : "💡 Type a service name for details or 'categories' to go back";
            
            $response .= $footer;
            
            $this->sendMessage($from, $response);
            
        } catch (\Exception $e) {
            Log::error('Error sending all services:', [
                'error' => $e->getMessage(),
                'from' => $from
            ]);
            $this->sendMessage($from, $this->messages[$userLang]['error']);
        }
    }

    /**
     * Send services for a specific category
     */
    private function sendCategoryServices($from, $categorySlug, $userLang)
    {
        try {
            $category = DB::table('service_categories')->where('slug', $categorySlug)->first();
            if (!$category) {
                $this->sendMessage($from, $this->messages[$userLang]['error']);
                return;
            }
            
            $table = $userLang === 'ar' ? 'services_ar' : 'services_en';
            $categoryNameField = $userLang === 'ar' ? 'name_ar' : 'name_en';
            
            $services = DB::table($table)
                ->where('category_id', $category->id)
                ->select('name', 'description', 'price', 'image_url')
                ->get();
            
            if ($services->count() === 0) {
                $this->sendMessage($from, $this->messages[$userLang]['noResults']);
                return;
            }
            
            $categoryIcon = $this->getCategoryIcon($categorySlug);
            $response = "{$categoryIcon} **{$category->$categoryNameField}**\n\n";
            
            foreach ($services as $service) {
                $price = floatval($service->price) > 0 ? "\n💰 {$service->price} SAR" : "";
                $serviceText = "✨ **{$service->name}**\n{$service->description}{$price}";
                
                // Send with image if available
                if (isset($service->image_url) && $service->image_url) {
                    $this->sendMessageWithImage($from, $serviceText, $service->image_url);
                } else {
                    $this->sendMessage($from, $serviceText);
                }
                
                // Small delay between messages
                usleep(500000); // 0.5 second delay
            }
            
            $footer = $userLang === 'ar'
                ? "\n💡 اكتب 'فئات' للعودة أو 'خدمات' لجميع الخدمات"
                : "\n💡 Type 'categories' to go back or 'services' for all services";
                
            $this->sendMessage($from, $footer);
            
        } catch (\Exception $e) {
            Log::error('Error sending category services:', [
                'error' => $e->getMessage(),
                'category' => $categorySlug,
                'from' => $from
            ]);
            $this->sendMessage($from, $this->messages[$userLang]['error']);
        }
    }

    /**
     * Get emoji icon for category
     */
    private function getCategoryIcon($categorySlug)
    {
        $icons = [
            'pre-arrival' => '🚗',
            'arrival' => '📍',
            'in-stay' => '🛎️',
            'departure' => '✈️'
        ];
        
        return $icons[$categorySlug] ?? '🏨';
    }

    /**
     * Search for services and send results with fuzzy matching
     */
    private function searchAndSendServices($from, $text, $userLang)
    {
        try {
            // Auto-detect search language
            $hasArabicChars = preg_match('/[\x{0600}-\x{06FF}]/u', $text);
            $searchLang = $hasArabicChars ? 'ar' : 'en';
            
            Log::info('Searching services', [
                'from' => $from,
                'text' => $text,
                'search_lang' => $searchLang,
                'user_lang' => $userLang
            ]);
            
            // First try direct service search
            $directMatch = $this->findDirectServiceMatch($text, $searchLang);
            
            if ($directMatch) {
                $this->sendServiceResult($from, $directMatch, $userLang);
                return;
            }
            
            // Try fuzzy matching for suggestions
            $suggestions = $this->findServiceSuggestions($text, $searchLang);
            
            if (!empty($suggestions)) {
                $this->sendServiceSuggestions($from, $suggestions, $text, $userLang);
                return;
            }
            
            // No matches found
            $msgs = $this->messages[$userLang];
            $this->sendMessage($from, $msgs['noResults']);
            
        } catch (\Exception $e) {
            Log::error('Service search error:', [
                'error' => $e->getMessage(),
                'from' => $from,
                'text' => $text,
                'trace' => $e->getTraceAsString()
            ]);
            
            $msgs = $this->messages[$userLang];
            $this->sendMessage($from, $msgs['error']);
        }
    }

    /**
     * Find direct service match (exact or very close)
     */
    private function findDirectServiceMatch($text, $searchLang)
    {
        $table = $searchLang === 'ar' ? 'services_ar' : 'services_en';
        $services = DB::table($table)->get();
        
        $normalize = function($string) use ($searchLang) {
            $string = trim(strtolower($string));
            if ($searchLang === 'ar') {
                // Remove Arabic diacritics
                $string = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $string);
            } else {
                // Remove punctuation and normalize spaces
                $string = preg_replace('/[^a-z0-9\s]/', ' ', $string);
                $string = preg_replace('/\s+/', ' ', $string);
            }
            return $string;
        };
        
        $inputNorm = $normalize($text);
        $inputWords = explode(' ', $inputNorm);
        
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($services as $service) {
            $nameNorm = $normalize($service->name);
            $score = 0;
            
            // Exact name match (highest priority)
            if ($nameNorm === $inputNorm) {
                return $service;
            }
            
            // Check if input contains service name
            if (strpos($inputNorm, $nameNorm) !== false) {
                $score += 100;
            }
            
            // Check if service name contains input
            if (strpos($nameNorm, $inputNorm) !== false) {
                $score += 90;
            }
            
            // Word-by-word matching
            $nameWords = explode(' ', $nameNorm);
            foreach ($inputWords as $inputWord) {
                if (strlen($inputWord) > 2) {
                    foreach ($nameWords as $nameWord) {
                        if ($inputWord === $nameWord) {
                            $score += 50;
                        } elseif (strpos($nameWord, $inputWord) !== false) {
                            $score += 30;
                        } elseif (strpos($inputWord, $nameWord) !== false) {
                            $score += 20;
                        }
                    }
                }
            }
            
            // Similar text scoring
            similar_text($inputNorm, $nameNorm, $percent);
            $score += $percent;
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $service;
            }
        }
        
        // Return if we have a good match (threshold for direct match)
        return $highestScore > 60 ? $bestMatch : null;
    }

    /**
     * Find service suggestions for fuzzy matching
     */
    private function findServiceSuggestions($text, $searchLang)
    {
        $table = $searchLang === 'ar' ? 'services_ar' : 'services_en';
        $services = DB::table($table)->get();
        
        $suggestions = [];
        $inputLower = strtolower($text);
        
        foreach ($services as $service) {
            $nameLower = strtolower($service->name);
            
            // Calculate similarity for suggestions
            similar_text($inputLower, $nameLower, $percent);
            
            // Also check for partial matches
            $partialMatch = false;
            if (strlen($text) >= 3) {
                $partialMatch = strpos($nameLower, $inputLower) !== false || 
                               strpos($inputLower, $nameLower) !== false;
            }
            
            // If similarity is decent or there's a partial match, add to suggestions
            if ($percent > 40 || $partialMatch) {
                $suggestions[] = [
                    'service' => $service,
                    'score' => $percent + ($partialMatch ? 20 : 0)
                ];
            }
        }
        
        // Sort by score and return top 3
        usort($suggestions, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($suggestions, 0, 3);
    }

    /**
     * Send a single service result
     */
    private function sendServiceResult($from, $service, $userLang)
    {
        $msgs = $this->messages[$userLang];
        
        $serviceText = "✨ **{$service->name}**\n\n{$service->description}";
        
        if (isset($service->price) && floatval($service->price) > 0) {
            $currency = $userLang === 'ar' ? 'ريال سعودي' : 'SAR';
            $serviceText .= "\n\n💰 {$msgs['price']}: {$service->price} {$currency}";
        }
        
        // Send with image if available
        if (isset($service->image_url) && $service->image_url) {
            $this->sendMessageWithImage($from, $serviceText, $service->image_url);
        } else {
            $this->sendMessage($from, $serviceText);
        }
        
        // Send additional options
        $additionalOptions = $userLang === 'ar'
            ? "\n💡 اكتب 'خدمات' لجميع الخدمات أو 'فئات' للفئات\n🎤 يمكنك إرسال رسالة صوتية"
            : "\n💡 Type 'services' for all services or 'categories' for categories\n🎤 You can send voice messages";
            
        $this->sendMessage($from, $additionalOptions);
    }

    /**
     * Send service suggestions
     */
    private function sendServiceSuggestions($from, $suggestions, $originalText, $userLang)
    {
        $msgs = $this->messages[$userLang];
        
        $suggestionText = $userLang === 'ar' 
            ? "🤔 لم أجد '{$originalText}' بالضبط. هل تقصد إحدى هذه الخدمات؟\n\n"
            : "🤔 I couldn't find '{$originalText}' exactly. Did you mean one of these services?\n\n";
        
        foreach ($suggestions as $index => $suggestion) {
            $service = $suggestion['service'];
            $price = floatval($service->price) > 0 ? " ({$service->price} SAR)" : "";
            $suggestionText .= "• {$service->name}{$price}\n";
        }
        
        $suggestionText .= $userLang === 'ar'
            ? "\n💡 اكتب اسم الخدمة بالضبط أو 'خدمات' لجميع الخدمات"
            : "\n💡 Type the exact service name or 'services' for all services";
        
        $this->sendMessage($from, $suggestionText);
    }

    /**
     * Send input validation message
     */
    private function sendInputValidationMessage($from, $userLang)
    {
        $shortResponse = $userLang === 'ar'
            ? "🤔 يرجى كتابة اسم الخدمة أو سؤال أكثر تفصيلاً\n\n💡 مثال: سبا، نقل المطار، خدمة الغرف\n🎤 أو أرسل رسالة صوتية"
            : "🤔 Please type a service name or more detailed question\n\n💡 Example: spa, airport transfer, room service\n🎤 Or send a voice message";
            
        $this->sendMessage($from, $shortResponse);
    }

    /**
     * Process message status updates (delivered, read, etc.)
     */
    private function processMessageStatus($status)
    {
        Log::info('Message status update', [
            'status' => $status['status'],
            'message_id' => $status['id'],
            'recipient_id' => $status['recipient_id']
        ]);
        
        // You can store delivery status in database here if needed
    }

    /**
     * Handle media messages (images, videos, documents)
     */
    private function handleMediaMessage($from, $mediaType, $userLang)
    {
        Log::info('Media message received', [
            'from' => $from,
            'type' => $mediaType
        ]);

        // Auto-detect language if not set
        if (!$userLang) {
            $userLang = 'en'; // Default to English
            $this->setUserLanguage($from, $userLang);
        }

        $msgs = $this->messages[$userLang];
        
        $mediaResponse = $userLang === 'ar' 
            ? "📎 استلمت ملف {$mediaType}! يرجى إرسال نص للبحث عن الخدمات.\n\n💡 اكتب 'خدمات' لعرض قائمة الخدمات المتوفرة"
            : "📎 {$mediaType} received! Please send text to search for services.\n\n💡 Type 'services' to see available services";
            
        $this->sendMessage($from, $mediaResponse);
    }

    /**
     * Handle unsupported message types
     */
    private function handleUnsupportedMessage($from, $type, $userLang)
    {
        Log::info('Unsupported message type', [
            'from' => $from,
            'type' => $type
        ]);

        // Auto-detect language if not set
        if (!$userLang) {
            $userLang = 'en'; // Default to English
            $this->setUserLanguage($from, $userLang);
        }

        $response = $userLang === 'ar'
            ? "🤖 نوع الرسالة غير مدعوم. يرجى إرسال نص أو رسالة صوتية للبحث عن الخدمات."
            : "🤖 Message type not supported. Please send text or voice message to search for services.";
            
        $this->sendMessage($from, $response);
    }

    /**
     * Send text message via WhatsApp API
     */
    private function sendMessage($to, $message)
    {
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        
        if (!$accessToken || !$phoneNumberId) {
            Log::error('WhatsApp credentials not configured');
            return false;
        }
        
        $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        return $this->makeWhatsAppAPICall($url, $data, $to, 'text message');
    }

    /**
     * Send message with image
     */
    private function sendMessageWithImage($to, $caption, $imageUrl)
    {
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        
        if (!$accessToken || !$phoneNumberId) {
            Log::error('WhatsApp credentials not configured');
            return false;
        }
        
        // Convert relative URL to absolute
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = url($imageUrl);
        }
        
        $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
                'caption' => $caption
            ]
        ];
        
        $result = $this->makeWhatsAppAPICall($url, $data, $to, 'image message');
        
        if (!$result) {
            // Fallback to text-only message
            Log::info('Falling back to text message for: ' . $to);
            return $this->sendMessage($to, $caption);
        }
        
        return $result;
    }

    /**
     * Make API call to WhatsApp
     */
    private function makeWhatsAppAPICall($url, $data, $to, $messageType)
    {
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('CURL Error:', [
                'error' => $error,
                'to' => $to,
                'type' => $messageType
            ]);
            return false;
        }
        
        if ($httpCode !== 200) {
            Log::error('WhatsApp API Error:', [
                'http_code' => $httpCode,
                'response' => $response,
                'to' => $to,
                'type' => $messageType,
                'data' => $data
            ]);
            return false;
        }
        
        $responseData = json_decode($response, true);
        Log::info('WhatsApp API Success:', [
            'to' => $to,
            'type' => $messageType,
            'message_id' => $responseData['messages'][0]['id'] ?? 'unknown'
        ]);
        
        return true;
    }

    /**
     * User language management with cache
     */
    private function getUserLanguage($userId)
    {
        return Cache::get("whatsapp_user_lang_{$userId}");
    }

    private function setUserLanguage($userId, $lang)
    {
        Cache::put("whatsapp_user_lang_{$userId}", $lang, now()->addDays(30));
        Log::info('User language set', ['user' => $userId, 'language' => $lang]);
    }

    private function clearUserLanguage($userId)
    {
        Cache::forget("whatsapp_user_lang_{$userId}");
        Log::info('User language cleared', ['user' => $userId]);
    }
}
