#!/bin/bash

echo "🚀 Render Deployment Status Monitor"
echo "===================================="

# Set your Render service URL here (replace with your actual domain)
SERVICE_URL="${RENDER_SERVICE_URL:-your-app-url.onrender.com}"

echo "🌐 Service URL: https://$SERVICE_URL"
echo "📱 WhatsApp QR: https://$SERVICE_URL/whatsapp"
echo "📊 API Status: https://$SERVICE_URL/api/services"
echo ""

# Function to check service health
check_health() {
    local endpoint="$1"
    local description="$2"
    
    echo -n "🔍 Checking $description... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://$SERVICE_URL$endpoint" 2>/dev/null)
    
    if [ "$response" = "200" ]; then
        echo "✅ OK (HTTP $response)"
        return 0
    else
        echo "❌ Failed (HTTP $response)"
        return 1
    fi
}

# Function to check WhatsApp bot status
check_whatsapp_status() {
    echo -n "🤖 Checking WhatsApp bot status... "
    
    response=$(curl -s --max-time 10 "https://$SERVICE_URL/api/whatsapp/status" 2>/dev/null)
    
    if echo "$response" | grep -q "status"; then
        status=$(echo "$response" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
        message=$(echo "$response" | grep -o '"message":"[^"]*"' | cut -d'"' -f4)
        echo "✅ Status: $status"
        if [ ! -z "$message" ]; then
            echo "   📝 Message: $message"
        fi
        return 0
    else
        echo "❌ No response or invalid format"
        return 1
    fi
}

# Run health checks
echo "🏥 Health Checks:"
echo "=================="

check_health "/" "Main Application"
check_health "/api/services" "API Endpoint"
check_health "/whatsapp" "WhatsApp Interface"
check_whatsapp_status

echo ""
echo "📋 Troubleshooting:"
echo "==================="
echo "1. If API fails: Check Laravel logs in Render dashboard"
echo "2. If WhatsApp bot fails: Check supervisor logs"
echo "3. If Chrome issues: Check dockerfile Chrome dependencies"
echo "4. Check Render service logs for detailed error messages"
echo ""

# Instructions for accessing logs
echo "📊 To check logs from Render dashboard:"
echo "1. Go to your Render service dashboard"
echo "2. Click 'Logs' tab"
echo "3. Look for supervisor and WhatsApp bot output"
echo "4. Common error patterns:"
echo "   - 'Chrome not found' → Chrome installation issue"
echo "   - 'ECONNREFUSED' → Network/API connection issue"
echo "   - 'Module not found' → Node.js dependency issue"
echo ""

echo "🔄 Manual restart commands (if needed):"
echo "supervisorctl restart whatsapp-bot"
echo "supervisorctl restart laravel"
echo "supervisorctl status"
