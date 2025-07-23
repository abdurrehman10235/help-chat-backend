import pkg from 'whatsapp-web.js';
const { Client, LocalAuth, MessageMedia } = pkg;
import qrcode from 'qrcode-terminal';
import QRCode from 'qrcode';
import axios from 'axios';
import fs from 'fs';

// Your Laravel backend URL
const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8000/api';
const WEB_BACKEND_URL = BACKEND_URL.replace('/api', '');

// Initialize WhatsApp client
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './whatsapp-session' // This will persist session data
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
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor'
        ],
        timeout: 60000
    }
});

// Update backend status
async function updateStatus(status, message = null) {
    try {
        await axios.post(`${BACKEND_URL}/whatsapp/status`, {
            status: status,
            message: message
        });
        console.log(`📊 Status updated: ${status}${message ? ` - ${message}` : ''}`);
    } catch (error) {
        console.error('❌ Failed to update status:', error.message);
    }
}

// Send QR code to backend
async function sendQRToBackend(qr) {
    try {
        // Generate QR code as base64 image
        const qrImage = await QRCode.toDataURL(qr, {
            width: 300,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        });
        
        // Extract base64 data (remove data:image/png;base64, prefix)
        const base64Data = qrImage.split(',')[1];
        
        await axios.post(`${BACKEND_URL}/whatsapp/qr`, {
            qr: base64Data
        });
        
        console.log('📱 QR code sent to web interface');
    } catch (error) {
        console.error('❌ Failed to send QR to backend:', error.message);
    }
}

// Generate QR code for login
client.on('qr', async (qr) => {
    console.log('📱 QR code received');
    console.log('🌐 View QR code at: ' + WEB_BACKEND_URL + '/whatsapp');
    
    // Still show in terminal for local development
    if (process.env.NODE_ENV !== 'production') {
        qrcode.generate(qr, { small: true });
    }
    
    // Send to web interface
    await sendQRToBackend(qr);
    await updateStatus('qr_ready', 'QR code generated - scan with WhatsApp');
});

// Bot is ready
client.on('ready', async () => {
    console.log('🤖 WhatsApp Bot is ready!');
    console.log('🔗 Connected to your Laravel backend at:', BACKEND_URL);
    console.log('🌐 Web interface available at: ' + WEB_BACKEND_URL + '/whatsapp');
    
    await updateStatus('connected', 'WhatsApp bot successfully connected');
});

// Handle authentication
client.on('authenticated', async () => {
    console.log('✅ WhatsApp authenticated');
    await updateStatus('connecting', 'Authentication successful, connecting...');
});

// Handle authentication failure
client.on('auth_failure', async (msg) => {
    console.error('❌ Authentication failed:', msg);
    await updateStatus('error', 'Authentication failed: ' + msg);
});

// Handle disconnection
client.on('disconnected', async (reason) => {
    console.log('📴 WhatsApp disconnected:', reason);
    await updateStatus('disconnected', 'WhatsApp disconnected: ' + reason);
});

// Handle incoming messages
client.on('message', async (message) => {
    try {
        // Ignore messages from groups and status updates
        if (message.from.includes('@g.us') || message.from.includes('status@broadcast')) {
            return;
        }

        const userMessage = message.body.toLowerCase().trim();
        const userPhone = message.from.replace('@c.us', '');
        
        console.log(`📱 Message from ${userPhone}: ${userMessage}`);

        // Handle different types of messages
        await handleUserMessage(message, userMessage, userPhone);

    } catch (error) {
        console.error('❌ Error handling message:', error);
        await message.reply('Sorry, I encountered an error. Please try again.');
    }
});

// Main message handler
async function handleUserMessage(message, userMessage, userPhone) {
    // Welcome/Help commands
    if (userMessage.includes('hi') || userMessage.includes('hello') || userMessage.includes('help') || userMessage === '/start') {
        await sendWelcomeMessage(message);
        return;
    }

    // General service inquiries
    if (isGeneralServiceInquiry(userMessage)) {
        await handleGeneralInquiry(message, userMessage);
        return;
    }

    // Pricing inquiries
    if (isPricingInquiry(userMessage)) {
        await handlePricingInquiry(message, userMessage);
        return;
    }

    // Show categories
    if (userMessage.includes('categories') || userMessage.includes('menu') || userMessage === '1') {
        await sendCategories(message);
        return;
    }

    // Show all services
    if (userMessage.includes('all services') || userMessage === '2') {
        await sendAllServices(message);
        return;
    }

    // Language selection
    if (userMessage.includes('arabic') || userMessage.includes('عربي')) {
        await sendCategories(message, 'ar');
        return;
    }

    // Default: Search for service
    await searchService(message, userMessage);
}

// Check if message is a general service inquiry
function isGeneralServiceInquiry(message) {
    const generalQueries = [
        'what services do you offer',
        'what services do you have',
        'what can you help with',
        'what do you offer',
        'what are your services',
        'tell me about your services',
        'what services are available',
        'list your services',
        'show me services',
        'what can i get',
        'what do you provide',
        'services available'
    ];
    
    return generalQueries.some(query => message.includes(query));
}

// Check if message is asking about pricing
function isPricingInquiry(message) {
    const pricingQueries = [
        'how much',
        'what are your rates',
        'what are the rates',
        'pricing',
        'prices',
        'cost',
        'rates',
        'how much does it cost',
        'what does it cost',
        'price list',
        'rate card',
        'charges',
        'fees'
    ];
    
    return pricingQueries.some(query => message.includes(query));
}

// Handle general service inquiries
async function handleGeneralInquiry(message, userMessage) {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=en`);
        const servicesData = response.data;

        let replyText = `🏨 *Here are all the services we offer:*\n\n`;

        // Count total services
        let totalServices = 0;
        Object.values(servicesData).forEach(services => {
            totalServices += services.length;
        });

        replyText += `We have *${totalServices} services* across *4 categories*:\n\n`;

        // List categories with service counts
        Object.entries(servicesData).forEach(([categorySlug, services]) => {
            if (services.length > 0) {
                const categoryName = services[0].category_name;
                replyText += `📂 *${categoryName}* (${services.length} services)\n`;
                
                // Show first 3 services as examples
                services.slice(0, 3).forEach(service => {
                    replyText += `  • ${service.name}\n`;
                });
                
                if (services.length > 3) {
                    replyText += `  • ...and ${services.length - 3} more\n`;
                }
                replyText += '\n';
            }
        });

        replyText += `💡 *How to use:*\n`;
        replyText += `• Type "categories" to browse by category\n`;
        replyText += `• Type "all services" to see complete list\n`;
        replyText += `• Just ask for what you need (e.g., "airport pickup")\n`;
        replyText += `• Ask "what are your rates?" for pricing info\n\n`;
        replyText += `🔍 What would you like to know more about?`;

        await message.reply(replyText);
    } catch (error) {
        console.error('Error handling general inquiry:', error);
        await message.reply('Let me show you our services! Type "all services" to see everything we offer.');
    }
}

// Handle pricing inquiries
async function handlePricingInquiry(message, userMessage) {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=en`);
        const servicesData = response.data;

        let pricingText = `💰 *Our Service Rates:*\n\n`;

        // Organize by price ranges (prices are already in SAR)
        let freeServices = [];
        let lowCost = []; // 1-50 SAR
        let midCost = []; // 51-100 SAR
        let highCost = []; // 101+ SAR

        Object.values(servicesData).forEach(services => {
            services.forEach(service => {
                const price = parseFloat(service.price);
                if (price === 0) {
                    freeServices.push(service);
                } else if (price <= 50) {
                    lowCost.push(service);
                } else if (price <= 100) {
                    midCost.push(service);
                } else {
                    highCost.push(service);
                }
            });
        });

        // Show free services
        if (freeServices.length > 0) {
            pricingText += `🆓 *Complimentary Services:*\n`;
            freeServices.forEach(service => {
                pricingText += `  • ${service.name} - FREE\n`;
            });
            pricingText += '\n';
        }

        // Show paid services by price range
        if (lowCost.length > 0) {
            pricingText += `💵 *Budget Services (1-50 SAR):*\n`;
            lowCost.forEach(service => {
                pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`;
            });
            pricingText += '\n';
        }

        if (midCost.length > 0) {
            pricingText += `💳 *Standard Services (51-100 SAR):*\n`;
            midCost.forEach(service => {
                pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`;
            });
            pricingText += '\n';
        }

        if (highCost.length > 0) {
            pricingText += `💎 *Premium Services (101+ SAR):*\n`;
            highCost.forEach(service => {
                pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`;
            });
            pricingText += '\n';
        }

        pricingText += `📋 *Notes:*\n`;
        pricingText += `• All prices are in Saudi Riyals (SAR)\n`;
        pricingText += `• Prices may vary based on specific requirements\n`;
        pricingText += `• Type any service name for detailed information\n\n`;
        pricingText += `🛎️ Ready to book a service?`;

        await message.reply(pricingText);
    } catch (error) {
        console.error('Error handling pricing inquiry:', error);
        await message.reply('I can help you with pricing! Type "all services" to see our complete price list.');
    }
}

// Send welcome message
async function sendWelcomeMessage(message) {
    const welcomeText = `🏨 *Welcome to Hotel Services Bot!*

I can help you find and book hotel services. Here's what you can do:

*Quick Options:*
1️⃣ View Service Categories
2️⃣ Show All Services
🔍 Search for any service (just type what you need)

*Ask Me Anything:*
• "What services do you offer?" - Get complete overview
• "What are your rates?" - See all pricing information
• "I need airport pickup" - Specific service search
• "Room service menu" - Natural language requests

*Languages:*
🇺🇸 English (default)
🇸🇦 Arabic - type "Arabic" or "عربي"

Just ask me anything about our services and I'll help you find it! 😊`;

    await message.reply(welcomeText);
}

// Send service categories
async function sendCategories(message, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/service-categories?lang=${lang}`);
        const categories = response.data;

        let categoryText = lang === 'ar' ? 
            '🏨 *فئات الخدمات المتاحة:*\n\n' : 
            '🏨 *Available Service Categories:*\n\n';

        categories.forEach((category, index) => {
            categoryText += `${index + 1}️⃣ *${category.name}*\n`;
        });

        categoryText += lang === 'ar' ? 
            '\nاكتب اسم الفئة أو ابحث عن خدمة معينة 🔍' :
            '\nType a category name or search for a specific service 🔍';

        await message.reply(categoryText);
    } catch (error) {
        console.error('Error fetching categories:', error);
        await message.reply('Sorry, I could not load the categories right now.');
    }
}

// Send all services
async function sendAllServices(message, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=${lang}`);
        const servicesData = response.data;

        let servicesText = lang === 'ar' ? 
            '🏨 *جميع الخدمات المتاحة:*\n\n' : 
            '🏨 *All Available Services:*\n\n';

        // Services are grouped by category
        Object.entries(servicesData).forEach(([categorySlug, services]) => {
            if (services.length > 0) {
                const categoryName = services[0].category_name;
                servicesText += `📂 *${categoryName}*\n`;
                
                services.forEach(service => {
                    const price = service.price > 0 ? ` - ${formatPrice(service.price)}` : ' - Free';
                    servicesText += `  • ${service.name}${price}\n`;
                });
                servicesText += '\n';
            }
        });

        servicesText += lang === 'ar' ? 
            'اكتب اسم الخدمة للحصول على التفاصيل 📖' :
            'Type a service name to get details 📖';

        await message.reply(servicesText);
    } catch (error) {
        console.error('Error fetching services:', error);
        await message.reply('Sorry, I could not load the services right now.');
    }
}

// Search for a specific service
async function searchService(message, searchText, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/service-from-text?text=${encodeURIComponent(searchText)}&lang=${lang}`);
        const service = response.data;

        if (service.message && service.message.includes('No close match')) {
            await message.reply(`🔍 Sorry, I couldn't find a service matching "${searchText}". 

Try typing:
• "categories" to see all categories
• "all services" to see everything
• Be more specific (e.g., "airport transport", "room food")`);
            return;
        }

        // Format service details
        const serviceText = formatServiceDetails(service, lang);
        await message.reply(serviceText);

        // Send image if available
        if (service.image_url) {
            const imageUrl = `${BACKEND_URL.replace('/api', '')}${service.image_url}`;
            try {
                const media = await MessageMedia.fromUrl(imageUrl);
                await message.reply(media);
                console.log(`📸 Sent image: ${imageUrl}`);
            } catch (imgError) {
                console.log('Could not send image:', imgError.message);
                // Try alternative method - send image URL as text
                try {
                    await message.reply(`🖼️ View service image: ${imageUrl}`);
                } catch (fallbackError) {
                    console.log('Could not send image URL either:', fallbackError.message);
                }
            }
        }

    } catch (error) {
        console.error('Error searching service:', error);
        
        if (error.response?.status === 404) {
            await message.reply(`🔍 No service found for "${searchText}". Type "help" to see available options.`);
        } else {
            await message.reply('Sorry, there was an error searching for that service.');
        }
    }
}

// Format service details for WhatsApp
function formatServiceDetails(service, lang = 'en') {
    const priceText = formatPriceText(service.price);
    
    return `🏨 *${service.name}*

📝 ${service.description}

${priceText}

📞 Reply with "book ${service.slug}" to make a booking
🔙 Type "menu" to return to categories`;
}

// Helper function to format price in Saudi Riyals
function formatPrice(sarPrice) {
    const price = parseFloat(sarPrice);
    return price === 0 ? 'Free' : `${price.toFixed(0)} SAR`;
}

// Helper function to format price with currency symbol
function formatPriceText(sarPrice) {
    const price = parseFloat(sarPrice);
    return price > 0 ? `💰 Price: ${formatPrice(sarPrice)}` : '💰 Price: Free';
}

// Initialize the bot with proper error handling
async function initializeBot() {
    try {
        console.log('🚀 Starting WhatsApp Bot...');
        console.log('📱 Make sure your phone is connected to the internet');
        console.log('🔗 Backend URL:', BACKEND_URL);
        console.log('🌐 Web interface will be available at: ' + WEB_BACKEND_URL + '/whatsapp');
        
        await updateStatus('initializing', 'WhatsApp bot starting up...');
        
        client.initialize();
    } catch (error) {
        console.error('❌ Failed to initialize bot:', error);
        await updateStatus('error', 'Failed to initialize: ' + error.message);
    }
}

// Handle graceful shutdown
process.on('SIGINT', async () => {
    console.log('\n🛑 Shutting down WhatsApp bot...');
    await updateStatus('shutdown', 'Bot is shutting down');
    client.destroy();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Received SIGTERM, shutting down WhatsApp bot...');
    await updateStatus('shutdown', 'Bot is shutting down');
    client.destroy();
    process.exit(0);
});

// Start the bot
initializeBot();
