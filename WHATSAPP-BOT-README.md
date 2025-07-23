# WhatsApp Hotel Services Bot

A WhatsApp bot that integrates with your Laravel backend to help guests find and book hotel services.

## 🚀 Quick Start

### Prerequisites
- Node.js installed on your system
- Your Laravel backend running on `http://localhost:8000`
- WhatsApp account
- Chrome/Chromium browser (for puppeteer)

### Step 1: Install Dependencies
```bash
# Install Node.js packages
npm install whatsapp-web.js qrcode-terminal axios
```

### Step 2: Start Your Laravel Backend
```bash
# In your Laravel backend directory
php artisan serve
```

### Step 3: Run the WhatsApp Bot
```bash
# Run the bot
node whatsapp-bot.js
```

### Step 4: Connect WhatsApp
1. A QR code will appear in your terminal
2. Open WhatsApp on your phone
3. Go to Settings > Linked Devices
4. Scan the QR code
5. Bot is now connected! 🎉

## 🤖 Bot Features

### What the bot can do:
- **Natural Language Search**: "I need airport pickup", "wash clothes", "massage"
- **General Inquiries**: "What services do you offer?", "What can you help with?"
- **Pricing Information**: "What are your rates?", "How much does it cost?"
- **Category Browsing**: View services by category
- **Multi-language**: English and Arabic support
- **Service Details**: Get prices in Saudi Riyals (SAR), descriptions, and images
- **Smart Matching**: Enhanced keyword recognition for all services
- **Intelligent Search**: Recognizes synonyms and related terms
- **Image Support**: Automatically sends service images when available

### Bot Commands:
- `hi` / `hello` / `help` - Show welcome message
- `categories` / `menu` - Show service categories  
- `all services` - List all available services
- `arabic` / `عربي` - Switch to Arabic
- Just type what you need using natural language or keywords

### General Queries Support:
**📋 Service Information:**
- "What services do you offer?"
- "What can you help with?"
- "Tell me about your services"
- "What services are available?"
- "What do you provide?"

**💰 Pricing Information:**
- "What are your rates?"
- "How much does it cost?"
- "What are the prices?"
- "Show me your pricing"
- "Rate card please"

### Enhanced Keyword Recognition:
**🚗 Transportation**: airport, pickup, transport, taxi, car, driver, transfer, ride
**🏨 Check-in/out**: early, fast, express, quick, priority, late, extend, stay longer  
**🍽️ Food & Dining**: food, dining, meal, eat, hungry, restaurant, menu, order, delivery
**🧺 Laundry**: wash, clean, dry cleaning, clothes, garments, ironing
**💆 Spa & Wellness**: spa, massage, relax, wellness, therapy, treatment, facial
**🎒 Luggage**: baggage, bags, porter, help carry, store, hold, safe storage
**🥤 Drinks**: welcome drink, beverage, refreshment, juice, water
**🛏️ Room**: room preferences, customize, special needs, requests

## 🔧 Configuration

### Backend URL
Edit the `BACKEND_URL` in `whatsapp-bot.js`:
```javascript
const BACKEND_URL = 'http://localhost:8000/api';
```

### For Production
- Change to your production URL: `https://your-domain.com/api`
- Use PM2 or similar for process management
- Set up proper error logging

## 📱 Example Conversations

**User**: "Hi"
**Bot**: Shows welcome menu with options

**User**: "What services do you offer?"
**Bot**: Lists all 12 services across 4 categories with usage tips

**User**: "What are your rates?"
**Bot**: Shows pricing organized by ranges (Free, 1-50 SAR, 51-100 SAR, 101+ SAR)

**User**: "I need a ride to airport"  
**Bot**: Returns Airport Pickup service with price and details

**User**: "massage please"
**Bot**: Returns Spa service with treatments and pricing

**User**: "wash my clothes"
**Bot**: Returns Laundry service with dry-cleaning options

**User**: "help with bags"
**Bot**: Returns Luggage Assistance service

**User**: "fast check in"
**Bot**: Returns Express Check-in service

**User**: "store my luggage"
**Bot**: Returns Baggage Hold service

**User**: "categories"
**Bot**: Shows Pre-Arrival, Arrival, In-Stay, Departure categories

## 🛠 Technical Details

### API Endpoints Used:
- `GET /api/services?lang=en` - List all services
- `GET /api/service-categories?lang=en` - Get categories
- `GET /api/service-from-text?text=query&lang=en` - Search services
- `GET /api/service/{slug}?lang=en` - Service details
- `GET /api/image/services/{filename}` - Service images

### Files:
- `whatsapp-bot.js` - Main bot code
- `package-bot.json` - Node.js dependencies

## 🔍 Troubleshooting

### Common Issues:
1. **QR Code not showing**: Make sure terminal supports UTF-8
2. **Backend connection failed**: Check if Laravel is running on port 8000
3. **Search not working**: Verify your database is seeded with services
4. **Images not sending**: Check if image URLs are accessible, bot will fallback to sending image URL as text
5. **Prices showing wrong currency**: All prices are stored and displayed in Saudi Riyals (SAR)

### Logs:
The bot logs all messages and errors to console. Watch for:
- ✅ Connection successful messages
- ❌ API call errors  
- 📱 Incoming message logs

## 🚀 Next Steps

### Enhancements you can add:
1. **Booking System**: Add actual booking functionality
2. **User Management**: Store WhatsApp user data
3. **Payment Integration**: Connect with payment APIs
4. **Admin Panel**: Manage bot responses
5. **Analytics**: Track popular services

### Production Deployment:
1. **Render Deployment**: See `RENDER-DEPLOYMENT-GUIDE.md` for complete guide
   - Web-based QR code interface at `/whatsapp`
   - Automatic bot management and restarts
   - Session persistence across deployments
   - Enhanced stability with heartbeat monitoring
   - Improved supervisor configuration for cloud environments
2. Use PM2 for other VPS deployments
3. Set up webhook endpoints for better performance
4. Add rate limiting and security measures
5. Use WhatsApp Business API for official status

## 🔍 Troubleshooting

### Common Issues:
1. **QR Code not showing**: Make sure terminal supports UTF-8
2. **Backend connection failed**: Check if Laravel is running on port 8000
3. **Search not working**: Verify your database is seeded with services
4. **Images not sending**: Check if image URLs are accessible, bot will fallback to sending image URL as text
5. **Prices showing wrong currency**: All prices are stored and displayed in Saudi Riyals (SAR)

### Deployment Troubleshooting:
- **Exit Status 0**: Bot completed initialization but may be exiting early
  - Check for heartbeat messages in logs
  - Verify Chrome dependencies are installed
  - Monitor supervisor restart patterns
- **Exit Status 1**: Critical error during startup
  - Check Chrome installation with `test-chrome.sh`
  - Verify all Node.js dependencies
  - Check memory and CPU limits

### Logs:
The bot logs all messages and errors to console. Watch for:
- ✅ Connection successful messages
- ❌ API call errors  
- 📱 Incoming message logs
- 💓 Heartbeat status updates

## 📞 Support

If you need help:
1. Check Laravel backend logs: `tail -f storage/logs/laravel.log`
2. Check bot console output for errors
3. Test API endpoints directly: `curl http://localhost:8000/api/services`

Happy botting! 🤖✨
