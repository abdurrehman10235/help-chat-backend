import pkg from 'whatsapp-web.js';
const { Client, LocalAuth, MessageMedia } = pkg;
import qrcode from 'qrcode-terminal';
import QRCode from 'qrcode';
import axios from 'axios';
import fs from 'fs';

// Your Laravel backend URL
const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8000/api';
const WEB_BACKEND_URL = BACKEND_URL.replace('/api', '');

console.log('🚀 Starting WhatsApp Bot for Render deployment...');
console.log('📊 Environment: ', process.env.NODE_ENV);
console.log('🔗 Backend URL:', BACKEND_URL);
console.log('🌐 Web Backend URL:', WEB_BACKEND_URL);

// Enhanced error handling
process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Unhandled Rejection at:', promise, 'reason:', reason);
});

process.on('uncaughtException', (error) => {
    console.error('❌ Uncaught Exception:', error);
    process.exit(1);
});

// Initialize WhatsApp client with enhanced cloud configuration
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './whatsapp-session'
    }),
    puppeteer: {
        headless: true,
        executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/google-chrome-stable',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process', // Important for cloud environments
            '--disable-gpu',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-background-networking',
            '--memory-pressure-off',
            '--max_old_space_size=4096'
        ],
        timeout: 120000 // Increased timeout for cloud
    }
});

// Update backend status with enhanced error handling
async function updateStatus(status, message = null) {
    try {
        console.log(`📊 Updating status: ${status}${message ? ` - ${message}` : ''}`);
        
        await axios.post(`${BACKEND_URL}/whatsapp/status`, {
            status: status,
            message: message
        }, {
            timeout: 10000,
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        console.log(`✅ Status updated successfully`);
    } catch (error) {
        console.error('❌ Failed to update status:', error.message);
        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', error.response.data);
        }
    }
}

// Send QR code to backend with retry logic
async function sendQRToBackend(qr, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            console.log(`📱 Generating QR code (attempt ${i + 1}/${retries})`);
            
            // Generate QR code as base64 image
            const qrImage = await QRCode.toDataURL(qr, {
                width: 300,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });
            
            // Extract base64 data
            const base64Data = qrImage.split(',')[1];
            
            await axios.post(`${BACKEND_URL}/whatsapp/qr`, {
                qr: base64Data
            }, {
                timeout: 10000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('✅ QR code sent to web interface successfully');
            return;
        } catch (error) {
            console.error(`❌ Failed to send QR (attempt ${i + 1}):`, error.message);
            if (i === retries - 1) {
                console.error('❌ All QR sending attempts failed');
            } else {
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
        }
    }
}

// Bot event handlers with enhanced logging
client.on('qr', async (qr) => {
    console.log('📱 QR code received - length:', qr.length);
    console.log('🌐 View QR code at: ' + WEB_BACKEND_URL + '/whatsapp');
    
    // Still show in terminal for debugging
    if (process.env.NODE_ENV !== 'production') {
        try {
            qrcode.generate(qr, { small: true });
        } catch (err) {
            console.log('Could not display QR in terminal:', err.message);
        }
    }
    
    // Send to web interface
    await sendQRToBackend(qr);
    await updateStatus('qr_ready', 'QR code generated - scan with WhatsApp');
});

client.on('ready', async () => {
    console.log('🤖 WhatsApp Bot is ready!');
    console.log('🔗 Connected to Laravel backend at:', BACKEND_URL);
    console.log('🌐 Web interface available at: ' + WEB_BACKEND_URL + '/whatsapp');
    
    await updateStatus('connected', 'WhatsApp bot successfully connected');
});

client.on('authenticated', async () => {
    console.log('✅ WhatsApp authenticated');
    await updateStatus('connecting', 'Authentication successful, connecting...');
});

client.on('auth_failure', async (msg) => {
    console.error('❌ Authentication failed:', msg);
    await updateStatus('error', 'Authentication failed: ' + msg);
});

client.on('disconnected', async (reason) => {
    console.log('📴 WhatsApp disconnected:', reason);
    await updateStatus('disconnected', 'WhatsApp disconnected: ' + reason);
});

// Enhanced error handling for client
client.on('error', async (error) => {
    console.error('❌ WhatsApp client error:', error);
    await updateStatus('error', 'Client error: ' + error.message);
});

// Handle incoming messages with error protection
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
        try {
            await message.reply('Sorry, I encountered an error. Please try again.');
        } catch (replyError) {
            console.error('❌ Failed to send error reply:', replyError);
        }
    }
});

// All the message handling functions (keeping them the same)
async function handleUserMessage(message, userMessage, userPhone) {
    if (userMessage.includes('hi') || userMessage.includes('hello') || userMessage.includes('help') || userMessage === '/start') {
        await sendWelcomeMessage(message);
        return;
    }

    if (isGeneralServiceInquiry(userMessage)) {
        await handleGeneralInquiry(message, userMessage);
        return;
    }

    if (isPricingInquiry(userMessage)) {
        await handlePricingInquiry(message, userMessage);
        return;
    }

    if (userMessage.includes('categories') || userMessage.includes('menu') || userMessage === '1') {
        await sendCategories(message);
        return;
    }

    if (userMessage.includes('all services') || userMessage === '2') {
        await sendAllServices(message);
        return;
    }

    if (userMessage.includes('arabic') || userMessage.includes('عربي')) {
        await sendCategories(message, 'ar');
        return;
    }

    await searchService(message, userMessage);
}

function isGeneralServiceInquiry(message) {
    const generalQueries = [
        'what services do you offer', 'what services do you have', 'what can you help with',
        'what do you offer', 'what are your services', 'tell me about your services',
        'what services are available', 'list your services', 'show me services',
        'what can i get', 'what do you provide', 'services available'
    ];
    return generalQueries.some(query => message.includes(query));
}

function isPricingInquiry(message) {
    const pricingQueries = [
        'how much', 'what are your rates', 'what are the rates', 'pricing', 'prices',
        'cost', 'rates', 'how much does it cost', 'what does it cost', 'price list',
        'rate card', 'charges', 'fees'
    ];
    return pricingQueries.some(query => message.includes(query));
}

async function handleGeneralInquiry(message, userMessage) {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=en`, { timeout: 10000 });
        const servicesData = response.data;

        let replyText = `🏨 *Here are all the services we offer:*\n\n`;
        let totalServices = 0;
        Object.values(servicesData).forEach(services => {
            totalServices += services.length;
        });

        replyText += `We have *${totalServices} services* across *4 categories*:\n\n`;

        Object.entries(servicesData).forEach(([categorySlug, services]) => {
            if (services.length > 0) {
                const categoryName = services[0].category_name;
                replyText += `📂 *${categoryName}* (${services.length} services)\n`;
                
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

async function handlePricingInquiry(message, userMessage) {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=en`, { timeout: 10000 });
        const servicesData = response.data;

        let pricingText = `💰 *Our Service Rates:*\n\n`;
        let freeServices = [], lowCost = [], midCost = [], highCost = [];

        Object.values(servicesData).forEach(services => {
            services.forEach(service => {
                const price = parseFloat(service.price);
                if (price === 0) freeServices.push(service);
                else if (price <= 50) lowCost.push(service);
                else if (price <= 100) midCost.push(service);
                else highCost.push(service);
            });
        });

        if (freeServices.length > 0) {
            pricingText += `🆓 *Complimentary Services:*\n`;
            freeServices.forEach(service => pricingText += `  • ${service.name} - FREE\n`);
            pricingText += '\n';
        }

        if (lowCost.length > 0) {
            pricingText += `💵 *Budget Services (1-50 SAR):*\n`;
            lowCost.forEach(service => pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`);
            pricingText += '\n';
        }

        if (midCost.length > 0) {
            pricingText += `💳 *Standard Services (51-100 SAR):*\n`;
            midCost.forEach(service => pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`);
            pricingText += '\n';
        }

        if (highCost.length > 0) {
            pricingText += `💎 *Premium Services (101+ SAR):*\n`;
            highCost.forEach(service => pricingText += `  • ${service.name} - ${formatPrice(service.price)}\n`);
            pricingText += '\n';
        }

        pricingText += `📋 *Notes:*\n• All prices are in Saudi Riyals (SAR)\n• Prices may vary based on specific requirements\n• Type any service name for detailed information\n\n🛎️ Ready to book a service?`;

        await message.reply(pricingText);
    } catch (error) {
        console.error('Error handling pricing inquiry:', error);
        await message.reply('I can help you with pricing! Type "all services" to see our complete price list.');
    }
}

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

async function sendCategories(message, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/service-categories?lang=${lang}`, { timeout: 10000 });
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

async function sendAllServices(message, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/services?lang=${lang}`, { timeout: 10000 });
        const servicesData = response.data;

        let servicesText = lang === 'ar' ? 
            '🏨 *جميع الخدمات المتاحة:*\n\n' : 
            '🏨 *All Available Services:*\n\n';

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

async function searchService(message, searchText, lang = 'en') {
    try {
        const response = await axios.get(`${BACKEND_URL}/service-from-text?text=${encodeURIComponent(searchText)}&lang=${lang}`, { timeout: 10000 });
        const service = response.data;

        if (service.message && service.message.includes('No close match')) {
            await message.reply(`🔍 Sorry, I couldn't find a service matching "${searchText}". 

Try typing:
• "categories" to see all categories
• "all services" to see everything
• Be more specific (e.g., "airport transport", "room food")`);
            return;
        }

        const serviceText = formatServiceDetails(service, lang);
        await message.reply(serviceText);

        if (service.image_url) {
            const imageUrl = `${BACKEND_URL.replace('/api', '')}${service.image_url}`;
            try {
                const media = await MessageMedia.fromUrl(imageUrl);
                await message.reply(media);
                console.log(`📸 Sent image: ${imageUrl}`);
            } catch (imgError) {
                console.log('Could not send image:', imgError.message);
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

function formatServiceDetails(service, lang = 'en') {
    const priceText = formatPriceText(service.price);
    
    return `🏨 *${service.name}*

📝 ${service.description}

${priceText}

📞 Reply with "book ${service.slug}" to make a booking
🔙 Type "menu" to return to categories`;
}

function formatPrice(sarPrice) {
    const price = parseFloat(sarPrice);
    return price === 0 ? 'Free' : `${price.toFixed(0)} SAR`;
}

function formatPriceText(sarPrice) {
    const price = parseFloat(sarPrice);
    return price > 0 ? `💰 Price: ${formatPrice(sarPrice)}` : '💰 Price: Free';
}

// Initialize the bot with enhanced error handling and retry logic
async function initializeBot(retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            console.log(`🚀 Initializing WhatsApp Bot (attempt ${i + 1}/${retries})...`);
            console.log('📱 Make sure your phone is connected to the internet');
            console.log('🔗 Backend URL:', BACKEND_URL);
            console.log('🌐 Web interface will be available at: ' + WEB_BACKEND_URL + '/whatsapp');
            
            await updateStatus('initializing', 'WhatsApp bot starting up...');
            
            client.initialize();
            
            // Wait a bit to see if initialization succeeds
            await new Promise(resolve => setTimeout(resolve, 5000));
            
            console.log('✅ Bot initialization started successfully');
            return;
            
        } catch (error) {
            console.error(`❌ Failed to initialize bot (attempt ${i + 1}):`, error);
            await updateStatus('error', `Failed to initialize (attempt ${i + 1}): ${error.message}`);
            
            if (i === retries - 1) {
                console.error('❌ All initialization attempts failed');
                process.exit(1);
            } else {
                console.log(`⏳ Retrying in 10 seconds...`);
                await new Promise(resolve => setTimeout(resolve, 10000));
            }
        }
    }
}

// Handle graceful shutdown
async function gracefulShutdown(signal) {
    console.log(`\n🛑 Received ${signal}, shutting down WhatsApp bot...`);
    try {
        await updateStatus('shutdown', `Bot is shutting down (${signal})`);
        if (client) {
            await client.destroy();
        }
    } catch (error) {
        console.error('Error during shutdown:', error);
    }
    process.exit(0);
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

// Start the bot
initializeBot().catch(error => {
    console.error('❌ Fatal error during bot initialization:', error);
    process.exit(1);
});
