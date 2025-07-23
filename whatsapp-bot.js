const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const axios = require('axios');
const QRCode = require('qrcode');

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8001/api';

let client;
let isReady = false;
let userLanguages = {}; // Store user language preferences
let qrTimeout; // Track QR timeout
let lastQRGenerated = null;

const messages = {
    en: {
        welcome: "👋 Welcome! I can help you find hotel services.\n\n🌍 Language / اللغة:\n• Type 'EN' for English\n• اكتب 'AR' للعربية\n\nOr just tell me what service you need!",
        langSet: "✅ Language set to English! Now tell me what service you're looking for (e.g., 'spa', 'room service', 'airport pickup')",
        found: "✨ Here's what I found:",
        price: "Price",
        noResults: "😔 Sorry, I couldn't find services matching your request. Try keywords like:\n\n• Room service\n• Spa\n• Restaurant\n• Transportation\n• Cleaning",
        error: "❌ Sorry, something went wrong. Please try again.",
        reset: "🔄 Settings reset! Please choose your language:\n• Type 'EN' for English\n• اكتب 'AR' للعربية"
    },
    ar: {
        welcome: "👋 أهلاً وسهلاً! يمكنني مساعدتك في العثور على خدمات الفندق.\n\n🌍 Language / اللغة:\n• Type 'EN' for English\n• اكتب 'AR' للعربية\n\nأو أخبرني فقط بالخدمة التي تحتاجها!",
        langSet: "✅ تم تعيين اللغة للعربية! الآن أخبرني بالخدمة التي تبحث عنها (مثل 'سبا'، 'خدمة الغرف'، 'نقل المطار')",
        found: "✨ إليك ما وجدته:",
        price: "السعر",
        noResults: "😔 عذراً، لم أجد خدمات تطابق طلبك. جرب كلمات مثل:\n\n• خدمة الغرف\n• سبا\n• مطعم\n• نقل\n• تنظيف",
        error: "❌ عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.",
        reset: "🔄 تم إعادة تعيين الإعدادات! يرجى اختيار لغتك:\n• Type 'EN' for English\n• اكتب 'AR' للعربية"
    }
};

async function updateStatus(status, qr = null, extra = null) {
    try {
        const payload = {
            status,
            qr,
            timestamp: new Date().toISOString()
        };
        
        if (extra) {
            payload.message = extra.message || `${extra.percent}% loaded`;
            payload.extra = extra;
        }
        
        await axios.post(`${BACKEND_URL}/whatsapp/status`, payload);
        console.log('Status updated:', status);
    } catch (error) {
        console.log('Status update failed:', error.message);
    }
}

function getUserLanguage(userId) {
    return userLanguages[userId] || null;
}

function setUserLanguage(userId, lang) {
    userLanguages[userId] = lang;
}

async function startBot() {
    console.log('🚀 Starting WhatsApp Bot...');
    
    client = new Client({
        authStrategy: new LocalAuth({
            clientId: "whatsapp-bot-v2",
            dataPath: "./whatsapp-session"
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding'
            ],
            timeout: 60000 // Increase timeout
        },
        qrMaxRetries: 3, // Allow multiple QR retries
        authTimeoutMs: 60000, // 60 second auth timeout
        takeoverOnConflict: true // Handle multiple sessions
    });

    // Handle authentication process
    client.on('authenticated', async () => {
        console.log('🔐 Authentication successful! Connecting...');
        await updateStatus('authenticated');
    });

    client.on('loading_screen', async (percent, message) => {
        console.log(`📱 Loading: ${percent}% - ${message}`);
        await updateStatus('loading', null, { percent, message });
    });

    // Handle authentication failure
    client.on('auth_failure', async (message) => {
        console.log('❌ Authentication failed:', message);
        await updateStatus('auth_failed');
        
        // Clear session and restart after 10 seconds
        console.log('🔄 Clearing session and restarting...');
        setTimeout(() => {
            startBot().catch(console.error);
        }, 10000);
    });

    client.on('qr', async (qr) => {
        console.log('📱 QR Code received');
        try {
            const qrImage = await QRCode.toDataURL(qr);
            await updateStatus('qr_ready', qrImage);
            lastQRGenerated = Date.now();
            
            // Clear existing timeout
            if (qrTimeout) {
                clearTimeout(qrTimeout);
            }
            
            // Set timeout for QR code expiration (60 seconds - longer timeout)
            qrTimeout = setTimeout(async () => {
                console.log('⏰ QR Code expired after 60 seconds, requesting new one...');
                await updateStatus('qr_expired');
                // The WhatsApp client will automatically generate a new QR
            }, 60000);
            
        } catch (error) {
            console.error('QR generation failed:', error);
            await updateStatus('qr_error');
        }
    });

    client.on('ready', async () => {
        console.log('✅ WhatsApp bot is ready!');
        isReady = true;
        
        // Clear QR timeout since we're now connected
        if (qrTimeout) {
            clearTimeout(qrTimeout);
            qrTimeout = null;
        }
        
        // Test API connection
        try {
            console.log('🔍 Testing API connection...');
            const testResponse = await axios.get(`${BACKEND_URL}/services`, {
                timeout: 5000,
                headers: { 'Accept': 'application/json' }
            });
            console.log('✅ API connection test successful');
        } catch (apiError) {
            console.error('❌ API connection test failed:', apiError.message);
            console.error('API URL:', `${BACKEND_URL}/services`);
        }
        
        await updateStatus('ready');
    });

    client.on('message', async (message) => {
        if (message.from === 'status@broadcast') return;
        
        const userId = message.from;
        let userText = '';
        const userLang = getUserLanguage(userId);
        
        try {
            // Handle voice messages and media
            if (message.hasMedia || message.type === 'ptt' || message.type === 'audio') {
                console.log('🎵 Media message received from:', userId, 'Type:', message.type);
                
                const msgs = userLang ? messages[userLang] : messages.en;
                
                if (message.type === 'ptt' || message.type === 'audio') {
                    // Voice note received - provide helpful response
                    const voiceResponse = userLang === 'ar' 
                        ? "🎤 استلمت رسالتك الصوتية!\n\nيرجى كتابة نص للبحث عن الخدمات مثل:\n• سبا\n• مطعم\n• نقل المطار\n• خدمة الغرف\n• تنظيف\n💡 اكتب RESET لتغيير اللغة"
                        : "🎤 Voice message received!\n\nPlease type text to search for services like:\n• Spa\n• Restaurant\n• Airport transfer\n• Room service\n• Cleaning\n💡 Type RESET to change language";
                    
                    await client.sendMessage(userId, voiceResponse);
                } else {
                    // Other media types (images, documents, videos, etc.)
                    const mediaResponse = userLang === 'ar'
                        ? "📎 استلمت الملف! يرجى إرسال نص للبحث عن الخدمات.\n\n💡 اكتب RESET لتغيير اللغة"
                        : "📎 File received! Please send text to search for services.\n\n💡 Type RESET to change language";
                    
                    await client.sendMessage(userId, mediaResponse);
                }
                return;
            }
            
            // Handle regular text messages
            userText = message.body.trim().toUpperCase();
            
            // Handle language selection
            if (userText === 'EN') {
                setUserLanguage(userId, 'en');
                await client.sendMessage(userId, messages.en.langSet);
                return;
            }
            
            if (userText === 'AR') {
                setUserLanguage(userId, 'ar');
                await client.sendMessage(userId, messages.ar.langSet);
                return;
            }
            
            // Handle reset command
            if (userText === 'RESET' || userText === 'إعادة تعيين') {
                delete userLanguages[userId];
                await client.sendMessage(userId, messages.en.reset);
                return;
            }
            
            // If no language set, show welcome message
            if (!userLang) {
                await client.sendMessage(userId, messages.en.welcome);
                return;
            }
            
            // Handle general queries first
            const queryText = message.body.toLowerCase().trim();
            
            // Handle service list queries (more specific patterns)
            if ((queryText.includes('what') && queryText.includes('service')) || 
                queryText.includes('list') || queryText.includes('show') ||
                queryText.includes('available') || queryText.includes('متوفر') ||
                (queryText.includes('ماذا') && queryText.includes('خدمات')) ||
                queryText === 'services' || queryText === 'خدمات') {
                
                const serviceListResponse = userLang === 'ar' 
                    ? "🏨 الخدمات المتوفرة:\n\n• 🚗 نقل المطار (Airport Transfer)\n• 🛎️ خدمة الغرف (Room Service)\n• 🧴 السبا والعافية (Spa & Wellness)\n• 🍽️ خدمة المطاعم (Restaurant Service)\n• 👔 خدمة الغسيل (Laundry Service)\n• ⏰ تسجيل دخول مبكر (Early Check-in)\n• 🕐 تسجيل خروج متأخر (Late Checkout)\n• 🧳 مساعدة الأمتعة (Luggage Assistance)\n\n💡 اكتب اسم الخدمة للحصول على التفاصيل"
                    : "🏨 Available Services:\n\n• 🚗 Airport Transfer\n• 🛎️ Room Service\n• 🧴 Spa & Wellness\n• 🍽️ Restaurant Service\n• 👔 Laundry Service\n• ⏰ Early Check-in\n• 🕐 Late Checkout\n• 🧳 Luggage Assistance\n\n💡 Type a service name for details";
                
                await client.sendMessage(userId, serviceListResponse);
                return;
            }
            
            // Handle pricing queries
            if (queryText.includes('price') || queryText.includes('cost') || 
                queryText.includes('rate') || queryText.includes('how much') ||
                queryText.includes('سعر') || queryText.includes('تكلفة') || 
                queryText.includes('كم') || queryText.includes('بكم')) {
                
                const pricingResponse = userLang === 'ar'
                    ? "💰 للاستعلام عن الأسعار:\n\nيرجى تحديد الخدمة أولاً (مثل: سبا، نقل المطار، خدمة الغرف) وسأعرض لك السعر والتفاصيل.\n\n💡 اكتب اسم الخدمة للحصول على السعر"
                    : "💰 For pricing information:\n\nPlease specify the service first (e.g., spa, airport transfer, room service) and I'll show you the price and details.\n\n💡 Type a service name to get pricing";
                
                await client.sendMessage(userId, pricingResponse);
                return;
            }
            
            // Validate input length and meaningfulness before searching
            const cleanText = message.body.trim();
            if (cleanText.length < 3) {
                const shortResponse = userLang === 'ar'
                    ? "🤔 يرجى كتابة اسم الخدمة أو سؤال أكثر تفصيلاً\n\n💡 مثال: سبا، نقل المطار، خدمة الغرف"
                    : "🤔 Please type a service name or more detailed question\n\n💡 Example: spa, airport transfer, room service";
                    
                await client.sendMessage(userId, shortResponse);
                return;
            }
            
            // Skip very generic words that don't mean anything
            const genericWords = ['what', 'how', 'where', 'when', 'why', 'hello', 'hi', 'hey', 'ok', 'yes', 'no', 'the', 'and', 'or', 'but', 'for', 'at', 'by', 'to', 'in', 'on', 'with'];
            const arabicGenericWords = ['ماذا', 'كيف', 'أين', 'متى', 'لماذا', 'مرحبا', 'أهلا', 'نعم', 'لا', 'حسنا', 'في', 'على', 'من', 'إلى', 'مع'];
            
            const lowerText = cleanText.toLowerCase();
            if (genericWords.includes(lowerText) || arabicGenericWords.includes(cleanText)) {
                const genericResponse = userLang === 'ar'
                    ? "🤖 أنا هنا لمساعدتك في العثور على خدمات الفندق!\n\n💡 جرب كتابة: سبا، مطعم، نقل، خدمة الغرف"
                    : "🤖 I'm here to help you find hotel services!\n\n💡 Try typing: spa, restaurant, transport, room service";
                    
                await client.sendMessage(userId, genericResponse);
                return;
            }
            
            // Check if input has at least one meaningful word (longer than 2 chars)
            const words = cleanText.split(/\s+/).filter(word => word.length > 2);
            if (words.length === 0) {
                const meaningfulResponse = userLang === 'ar'
                    ? "🤔 يرجى كتابة كلمة واضحة للبحث عن الخدمات\n\n💡 مثال: سبا، مطعم، نقل"
                    : "🤔 Please type a clear word to search for services\n\n💡 Example: spa, restaurant, transport";
                    
                await client.sendMessage(userId, meaningfulResponse);
                return;
            }
            
            // Auto-detect search language based on message content
            const hasArabicChars = /[\u0600-\u06FF]/.test(message.body);
            const searchLang = hasArabicChars ? 'ar' : 'en';
            
            // Add API test for debugging
            console.log('Searching for:', message.body, 'in language:', searchLang);
            console.log('API URL:', `${BACKEND_URL}/service-from-text`);
            
            // Search for services using detected language
            const response = await axios.get(`${BACKEND_URL}/service-from-text`, {
                params: { 
                    text: message.body,
                    lang: searchLang 
                },
                timeout: 5000, // 5 second timeout
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('API Response status:', response.status);
            console.log('API Response data type:', typeof response.data);
            
            const msgs = messages[userLang];
            
            // Handle both single service object and array of services
            let services = [];
            if (response.data) {
                if (Array.isArray(response.data)) {
                    services = response.data;
                } else if (typeof response.data === 'object' && response.data.name) {
                    services = [response.data]; // Convert single object to array
                } else {
                    console.log('Unexpected response format:', response.data);
                    throw new Error('Invalid response format from API');
                }
            }
            
            if (services.length > 0) {
                // Send each service with image (no initial "found" message)
                for (let i = 0; i < Math.min(services.length, 3); i++) {
                    const service = services[i];
                    let serviceText = `✨ ${service.name}\n\n`;
                    serviceText += `${service.description}\n`;
                    if (service.price && parseFloat(service.price) > 0) {
                        serviceText += `\n${msgs.price}: ${service.price} ${userLang === 'ar' ? 'ريال سعودي' : 'SAR'}`;
                    }
                    
                    // Try to send image if available
                    if (service.image_url) {
                        try {
                            const imageUrl = `${BACKEND_URL.replace('/api', '')}${service.image_url}`;
                            const media = await MessageMedia.fromUrl(imageUrl);
                            await client.sendMessage(userId, media, { caption: serviceText });
                        } catch (imageError) {
                            console.log('Failed to send image:', imageError.message);
                            // If image fails, send text only
                            await client.sendMessage(userId, serviceText);
                        }
                    } else {
                        // No image available, send text only
                        await client.sendMessage(userId, serviceText);
                    }
                    
                    // Small delay between messages
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
                
                // Send language reset tip
                await client.sendMessage(userId, '\n💡 Type RESET to change language');
            } else {
                const noResultsMsg = msgs.noResults + '\n\n💡 Type RESET to change language';
                await client.sendMessage(userId, noResultsMsg);
            }
            
        } catch (error) {
            console.error('Message processing error:', error);
            console.error('Error details:', {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status,
                url: error.config?.url
            });
            
            const msgs = userLang ? messages[userLang] : messages.en;
            
            // Send more specific error messages
            let errorMessage = msgs.error;
            if (error.code === 'ECONNREFUSED' || error.code === 'ENOTFOUND') {
                errorMessage = userLang === 'ar' 
                    ? "❌ مشكلة في الاتصال بالخادم. يرجى المحاولة مرة أخرى لاحقاً."
                    : "❌ Server connection issue. Please try again later.";
            } else if (error.response?.status === 500) {
                errorMessage = userLang === 'ar'
                    ? "❌ خطأ في الخادم. يرجى المحاولة مرة أخرى."
                    : "❌ Server error. Please try again.";
            } else if (error.message.includes('Unexpected token')) {
                errorMessage = userLang === 'ar'
                    ? "❌ خطأ في معالجة البيانات. يرجى المحاولة مرة أخرى."
                    : "❌ Data processing error. Please try again.";
            }
            
            await client.sendMessage(userId, errorMessage);
        }
    });

    client.on('disconnected', async () => {
        console.log('❌ WhatsApp bot disconnected');
        isReady = false;
        
        // Clear QR timeout
        if (qrTimeout) {
            clearTimeout(qrTimeout);
            qrTimeout = null;
        }
        
        await updateStatus('disconnected');
        
        // Auto-restart after disconnection (after 5 seconds)
        console.log('🔄 Restarting bot in 5 seconds...');
        setTimeout(() => {
            startBot().catch(console.error);
        }, 5000);
    });

    await client.initialize();
}

// Start the bot
startBot().catch(console.error);

// Keep alive
setInterval(() => {
    console.log('💓 Bot heartbeat:', isReady ? 'Ready' : 'Not Ready');
}, 30000);
