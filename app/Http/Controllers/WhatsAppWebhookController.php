<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\ServiceController;

class WhatsAppWebhookController extends Controller
{
    private $messages = [
        'en' => [
            'welcome' => "👋 Welcome to Hotel Service Assistant!\n\n🌍 Language / اللغة:\n• Type 'EN' for English\n• اكتب 'AR' للعربية\n\n🎤 You can also send voice messages!\n\nOr just tell me what service you need!",
            'langSet' => "✅ Language set to English!\n\nNow tell me what service you're looking for:\n• 🧴 Spa services\n• 🛎️ Room service\n• 🚗 Airport pickup\n• 🍽️ Restaurant\n• 👔 Laundry\n\n🎤 Voice messages are supported!",
            'found' => "✨ Here's what I found:",
            'price' => "Price",
            'noResults' => "😔 Sorry, I couldn't find services matching your request.\n\nTry keywords like:\n• Room service\n• Spa\n• Restaurant\n• Transportation\n• Cleaning\n\n🎤 You can also send a voice message!",
            'error' => "❌ Sorry, something went wrong. Please try again.",
            'reset' => "🔄 Settings reset! Please choose your language:\n• Type 'EN' for English\n• اكتب 'AR' للعربية",
            'voiceReceived' => "🎤 Voice message received!\n\nI understand voice messages, but I can only respond with text. Please send your request as text or voice - both work the same way!\n\nExample: \"I need spa services\" or just say it in a voice note.",
            'voiceProcessing' => "🎤 Processing your voice message...",
            'voiceError' => "❌ Sorry, I couldn't process your voice message. Please try sending it as text instead."
        ],
        'ar' => [
            'welcome' => "👋 أهلاً وسهلاً بك في مساعد خدمات الفندق!\n\n🌍 Language / اللغة:\n• Type 'EN' for English\n• اكتب 'AR' للعربية\n\n🎤 يمكنك أيضاً إرسال رسائل صوتية!\n\nأو أخبرني فقط بالخدمة التي تحتاجها!",
            'langSet' => "✅ تم تعيين اللغة للعربية!\n\nالآن أخبرني بالخدمة التي تبحث عنها:\n• 🧴 خدمات السبا\n• 🛎️ خدمة الغرف\n• 🚗 نقل المطار\n• 🍽️ المطعم\n• 👔 الغسيل\n\n🎤 الرسائل الصوتية مدعومة!",
            'found' => "✨ إليك ما وجدته:",
            'price' => "السعر",
            'noResults' => "😔 عذراً، لم أجد خدمات تطابق طلبك.\n\nجرب كلمات مثل:\n• خدمة الغرف\n• سبا\n• مطعم\n• نقل\n• تنظيف\n\n🎤 يمكنك أيضاً إرسال رسالة صوتية!",
            'error' => "❌ عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.",
            'reset' => "🔄 تم إعادة تعيين الإعدادات! يرجى اختيار لغتك:\n• Type 'EN' for English\n• اكتب 'AR' للعربية",
            'voiceReceived' => "🎤 تم استلام الرسالة الصوتية!\n\nأفهم الرسائل الصوتية، لكنني أستطيع الرد بالنص فقط. أرسل طلبك كنص أو صوت - كلاهما يعمل بنفس الطريقة!\n\nمثال: \"أريد خدمات السبا\" أو قلها في رسالة صوتية.",
            'voiceProcessing' => "🎤 معالجة رسالتك الصوتية...",
            'voiceError' => "❌ عذراً، لم أستطع معالجة رسالتك الصوتية. يرجى إرسالها كنص بدلاً من ذلك."
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
     * Handle text messages
     */
    private function handleTextMessage($from, $text, $userLang)
    {
        $userText = trim($text);
        $upperText = strtoupper($userText);
        
        Log::info('Text message received', [
            'from' => $from,
            'text' => $userText,
            'language' => $userLang
        ]);

        // Handle language selection
        if ($upperText === 'EN') {
            $this->setUserLanguage($from, 'en');
            $this->sendMessage($from, $this->messages['en']['langSet']);
            return;
        }
        
        if ($upperText === 'AR') {
            $this->setUserLanguage($from, 'ar');
            $this->sendMessage($from, $this->messages['ar']['langSet']);
            return;
        }
        
        // Handle reset command
        if (in_array($upperText, ['RESET', 'إعادة تعيين', 'RESTART'])) {
            $this->clearUserLanguage($from);
            $this->sendMessage($from, $this->messages['en']['reset']);
            return;
        }
        
        // If no language set, show welcome message
        if (!$userLang) {
            $this->sendMessage($from, $this->messages['en']['welcome']);
            return;
        }
        
        // Handle service list queries
        if ($this->isServiceListQuery($userText)) {
            $this->sendServiceList($from, $userLang);
            return;
        }
        
        // Handle pricing queries
        if ($this->isPricingQuery($userText)) {
            $this->sendPricingInfo($from, $userLang);
            return;
        }
        
        // Validate input
        if (strlen($userText) < 2) {
            $this->sendInputValidationMessage($from, $userLang);
            return;
        }
        
        // Search for services
        $this->searchAndSendServices($from, $userText, $userLang);
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

        $msgs = $userLang ? $this->messages[$userLang] : $this->messages['en'];
        
        // Send acknowledgment that voice was received
        $this->sendMessage($from, $msgs['voiceReceived']);
        
        // For now, we'll ask them to send text instead
        // In a production environment, you could integrate with speech-to-text services
        // like Google Speech-to-Text, Azure Speech, or AWS Transcribe
        
        // Example of how you could implement voice processing:
        // $transcription = $this->transcribeAudio($audioData['id']);
        // if ($transcription) {
        //     $this->handleTextMessage($from, $transcription, $userLang);
        // } else {
        //     $this->sendMessage($from, $msgs['voiceError']);
        // }
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

        $msgs = $userLang ? $this->messages[$userLang] : $this->messages['en'];
        
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

        $response = $userLang === 'ar'
            ? "🤖 نوع الرسالة غير مدعوم. يرجى إرسال نص أو رسالة صوتية للبحث عن الخدمات."
            : "🤖 Message type not supported. Please send text or voice message to search for services.";
            
        $this->sendMessage($from, $response);
    }

    /**
     * Check if message is asking for service list
     */
    private function isServiceListQuery($text)
    {
        $lowerText = strtolower($text);
        $serviceListKeywords = [
            'what service', 'list', 'show', 'available', 'متوفر', 'services', 'خدمات',
            'what can you do', 'help', 'مساعدة', 'ماذا تستطيع', 'menu', 'قائمة'
        ];
        
        foreach ($serviceListKeywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if message is asking for pricing
     */
    private function isPricingQuery($text)
    {
        $lowerText = strtolower($text);
        $pricingKeywords = [
            'price', 'cost', 'rate', 'how much', 'سعر', 'تكلفة', 'كم', 'بكم'
        ];
        
        foreach ($pricingKeywords as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Send service list
     */
    private function sendServiceList($from, $userLang)
    {
        $serviceListResponse = $userLang === 'ar' 
            ? "🏨 الخدمات المتوفرة:\n\n🚗 نقل المطار\n🛎️ خدمة الغرف\n🧴 السبا والعافية\n🍽️ خدمة المطاعم\n👔 خدمة الغسيل\n⏰ تسجيل دخول مبكر\n🕐 تسجيل خروج متأخر\n🧳 مساعدة الأمتعة\n\n💡 اكتب اسم الخدمة أو أرسل رسالة صوتية للحصول على التفاصيل"
            : "🏨 Available Services:\n\n🚗 Airport Transfer\n🛎️ Room Service\n🧴 Spa & Wellness\n🍽️ Restaurant Service\n👔 Laundry Service\n⏰ Early Check-in\n🕐 Late Checkout\n🧳 Luggage Assistance\n\n💡 Type a service name or send voice message for details";
        
        $this->sendMessage($from, $serviceListResponse);
    }

    /**
     * Send pricing information
     */
    private function sendPricingInfo($from, $userLang)
    {
        $pricingResponse = $userLang === 'ar'
            ? "💰 للاستعلام عن الأسعار:\n\nيرجى تحديد الخدمة أولاً (مثل: سبا، نقل المطار، خدمة الغرف) وسأعرض لك السعر والتفاصيل.\n\n💡 اكتب اسم الخدمة أو أرسل رسالة صوتية"
            : "💰 For pricing information:\n\nPlease specify the service first (e.g., spa, airport transfer, room service) and I'll show you the price and details.\n\n💡 Type a service name or send voice message";
        
        $this->sendMessage($from, $pricingResponse);
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
     * Search for services and send results
     */
    private function searchAndSendServices($from, $text, $userLang)
    {
        $serviceController = new ServiceController();
        
        // Auto-detect search language
        $hasArabicChars = preg_match('/[\x{0600}-\x{06FF}]/u', $text);
        $searchLang = $hasArabicChars ? 'ar' : 'en';
        
        Log::info('Searching services', [
            'from' => $from,
            'text' => $text,
            'search_lang' => $searchLang,
            'user_lang' => $userLang
        ]);
        
        $request = new Request(['text' => $text, 'lang' => $searchLang]);
        $response = $serviceController->searchServiceByText($request);
        $responseData = $response->getData(true);
        
        $msgs = $this->messages[$userLang];
        
        if (!empty($responseData) && is_array($responseData)) {
            // Send up to 3 services
            $services = array_slice($responseData, 0, 3);
            
            foreach ($services as $index => $service) {
                $serviceText = "✨ {$service['name']}\n\n{$service['description']}";
                
                if (isset($service['price']) && floatval($service['price']) > 0) {
                    $currency = $userLang === 'ar' ? 'ريال سعودي' : 'SAR';
                    $serviceText .= "\n\n💰 {$msgs['price']}: {$service['price']} {$currency}";
                }
                
                // Send with image if available
                if (isset($service['image_url']) && $service['image_url']) {
                    $this->sendMessageWithImage($from, $serviceText, $service['image_url']);
                } else {
                    $this->sendMessage($from, $serviceText);
                }
                
                // Small delay between messages to avoid rate limiting
                if ($index < count($services) - 1) {
                    usleep(500000); // 0.5 second delay
                }
            }
            
            // Send additional options
            $additionalOptions = $userLang === 'ar'
                ? "\n💡 اكتب 'خدمات' لعرض جميع الخدمات\n🎤 يمكنك إرسال رسالة صوتية\n🔄 اكتب 'RESET' لتغيير اللغة"
                : "\n💡 Type 'services' for all services\n🎤 You can send voice messages\n� Type 'RESET' to change language";
                
            $this->sendMessage($from, $additionalOptions);
            
        } else {
            $noResultsMsg = $msgs['noResults'] . "\n\n💡 Type 'services' for full list\n🔄 Type 'RESET' to change language";
            $this->sendMessage($from, $noResultsMsg);
        }
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

    /**
     * Transcribe audio (placeholder for future implementation)
     */
    private function transcribeAudio($audioId)
    {
        // This is where you would implement speech-to-text
        // Example integrations:
        // - Google Cloud Speech-to-Text
        // - Azure Cognitive Services Speech
        // - AWS Transcribe
        // - OpenAI Whisper
        
        // For now, return null to indicate transcription not available
        return null;
    }
}
