const { Client, LocalAuth } = require('whatsapp-web.js');
const axios = require('axios');
const QRCode = require('qrcode');

const BACKEND_URL = process.env.BACKEND_URL || 'http://localhost:8000/api';

let client;
let isReady = false;

async function updateStatus(status, qr = null) {
    try {
        await axios.post(`${BACKEND_URL.replace('/api', '')}/api/whatsapp/update-status`, {
            status,
            qr,
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        console.log('Status update failed:', error.message);
    }
}

async function startBot() {
    console.log('🚀 Starting WhatsApp Bot...');
    
    client = new Client({
        authStrategy: new LocalAuth(),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu'
            ]
        }
    });

    client.on('qr', async (qr) => {
        console.log('📱 QR Code received');
        try {
            const qrImage = await QRCode.toDataURL(qr);
            await updateStatus('qr_ready', qrImage);
        } catch (error) {
            console.error('QR generation failed:', error);
        }
    });

    client.on('ready', async () => {
        console.log('✅ WhatsApp bot is ready!');
        isReady = true;
        await updateStatus('ready');
    });

    client.on('message', async (message) => {
        if (message.from === 'status@broadcast') return;
        
        try {
            const response = await axios.get(`${BACKEND_URL}/services/search`, {
                params: { query: message.body, lang: 'en' }
            });
            
            let reply = 'مرحباً! إليك ما وجدته:\n\n';
            
            if (response.data.length > 0) {
                response.data.slice(0, 3).forEach((service, index) => {
                    reply += `${index + 1}. ${service.name}\n`;
                    reply += `   ${service.description}\n`;
                    reply += `   السعر: ${service.price} ريال سعودي\n\n`;
                });
            } else {
                reply = 'عذراً، لم أجد خدمات تطابق طلبك. يمكنك المحاولة بكلمات أخرى.';
            }
            
            await client.sendMessage(message.from, reply);
        } catch (error) {
            console.error('Message processing error:', error);
            await client.sendMessage(message.from, 'عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.');
        }
    });

    client.on('disconnected', async () => {
        console.log('❌ WhatsApp bot disconnected');
        isReady = false;
        await updateStatus('disconnected');
    });

    await client.initialize();
}

// Start the bot
startBot().catch(console.error);

// Keep alive
setInterval(() => {
    console.log('💓 Bot heartbeat:', isReady ? 'Ready' : 'Not Ready');
}, 30000);
