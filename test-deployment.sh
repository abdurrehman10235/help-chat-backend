#!/bin/bash

echo "🧪 Testing WhatsApp Bot Deployment Setup"
echo "========================================"

# Test 1: Check if Laravel is running
echo "1. Testing Laravel backend..."
if curl -s "http://localhost:8000/api/services" > /dev/null; then
    echo "   ✅ Laravel backend is running"
else
    echo "   ❌ Laravel backend is not accessible"
    exit 1
fi

# Test 2: Check WhatsApp routes
echo "2. Testing WhatsApp routes..."
if curl -s "http://localhost:8000/whatsapp" | grep -q "WhatsApp Bot"; then
    echo "   ✅ QR code page is accessible"
else
    echo "   ❌ QR code page failed to load"
fi

if curl -s "http://localhost:8000/api/whatsapp/status" | grep -q "status"; then
    echo "   ✅ Status API is working"
else
    echo "   ❌ Status API is not responding"
fi

# Test 3: Check Node.js dependencies
echo "3. Testing Node.js setup..."
if node -e "import('whatsapp-web.js').then(() => console.log('✅ WhatsApp Web.js available')).catch(() => console.log('❌ WhatsApp Web.js missing'))"; then
    :
fi

if node -e "import('qrcode').then(() => console.log('✅ QR Code library available')).catch(() => console.log('❌ QR Code library missing'))"; then
    :
fi

# Test 4: Check file permissions and structure
echo "4. Testing file structure..."
if [ -f "whatsapp-bot-web.js" ]; then
    echo "   ✅ Production bot file exists"
else
    echo "   ❌ Production bot file missing"
fi

if [ -f "app/Http/Controllers/WhatsAppBotController.php" ]; then
    echo "   ✅ WhatsApp controller exists"
else
    echo "   ❌ WhatsApp controller missing"
fi

if [ -f "resources/views/whatsapp-qr.blade.php" ]; then
    echo "   ✅ QR code view exists"
else
    echo "   ❌ QR code view missing"
fi

echo ""
echo "🎉 Deployment setup test completed!"
echo ""
echo "📋 Next steps for Render deployment:"
echo "   1. Push code to GitHub: git add . && git commit -m 'Deploy WhatsApp bot' && git push"
echo "   2. Deploy on Render using the render.yaml configuration"
echo "   3. Set BACKEND_URL environment variable to your Render app URL"
echo "   4. Visit https://your-app.onrender.com/whatsapp to scan QR code"
echo ""
echo "🌐 Local testing:"
echo "   • QR Page: http://localhost:8000/whatsapp"
echo "   • Bot Status: http://localhost:8000/api/whatsapp/status"
echo "   • Start bot: npm run whatsapp"
