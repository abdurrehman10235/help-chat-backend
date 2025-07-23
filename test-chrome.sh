#!/bin/bash

echo "🔧 Testing Chrome installation and Puppeteer compatibility..."

# Test 1: Check if Chrome is installed
echo "📱 Checking Chrome installation..."
if command -v google-chrome-stable >/dev/null 2>&1; then
    echo "✅ Chrome is installed: $(google-chrome-stable --version)"
else
    echo "❌ Chrome is not installed or not in PATH"
    exit 1
fi

# Test 2: Test Chrome with basic arguments
echo "🧪 Testing Chrome startup..."
google-chrome-stable --version --no-sandbox --disable-setuid-sandbox --headless >/dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Chrome can start with basic arguments"
else
    echo "❌ Chrome failed to start with basic arguments"
    exit 1
fi

# Test 3: Create and run simple Puppeteer test
echo "🎭 Testing Puppeteer..."
cat > /tmp/puppeteer-test.js << 'EOF'
import puppeteer from 'puppeteer';

(async () => {
    try {
        console.log('Starting Puppeteer test...');
        const browser = await puppeteer.launch({
            headless: true,
            executablePath: '/usr/bin/google-chrome-stable',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-gpu'
            ],
            timeout: 30000
        });
        
        const page = await browser.newPage();
        await page.goto('data:text/html,<h1>Test</h1>');
        const title = await page.title();
        console.log('✅ Puppeteer test passed, page title:', title);
        
        await browser.close();
        process.exit(0);
    } catch (error) {
        console.error('❌ Puppeteer test failed:', error.message);
        process.exit(1);
    }
})();
EOF

cd /var/www
timeout 60 node /tmp/puppeteer-test.js

if [ $? -eq 0 ]; then
    echo "✅ All tests passed! Chrome and Puppeteer are working correctly."
    echo "🚀 WhatsApp bot should be able to run successfully."
else
    echo "❌ Puppeteer test failed. Check Chrome dependencies."
    exit 1
fi
