#!/bin/bash

echo "🚀 Render Deployment Status Monitor"
echo "===================================="

# Set your Render service URL here (replace with your actual domain)
SERVICE_URL="${RENDER_SERVICE_URL:-laravel-backend-r3ut.onrender.com}"

echo "🌐 Service URL: https://$SERVICE_URL"
echo "🧪 Test endpoint: https://$SERVICE_URL/test"
echo "📱 WhatsApp QR: https://$SERVICE_URL/whatsapp"
echo "� Simple QR: https://$SERVICE_URL/whatsapp-simple"
echo "�📊 API Status: https://$SERVICE_URL/api/services"
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
        echo "   Response: $response"
        return 1
    fi
}

# Function to get detailed test info
check_test_endpoint() {
    echo -n "🧪 Getting Laravel diagnostics... "
    
    response=$(curl -s --max-time 10 "https://$SERVICE_URL/test" 2>/dev/null)
    
    if echo "$response" | grep -q "status"; then
        echo "✅ Laravel working"
        echo "   Details: $response"
        return 0
    else
        echo "❌ Laravel issues detected"
        echo "   Response: $response"
        return 1
    fi
}

# Run health checks
echo "🏥 Health Checks:"
echo "=================="

check_health "/" "Main Application"
check_test_endpoint
check_health "/api/services" "API Endpoint"
check_health "/whatsapp-simple" "Simple WhatsApp Interface"
check_health "/whatsapp" "Full WhatsApp Interface"
check_whatsapp_status

echo ""
echo "📋 Troubleshooting:"
echo "==================="
echo "1. If /test fails: Laravel configuration issue"
echo "2. If /whatsapp-simple works but /whatsapp fails: View/Controller issue"
echo "3. If API fails: Database or seeding issue"
echo "4. If WhatsApp bot status fails: Bot not communicating with Laravel"
echo ""

# Instructions for accessing logs
echo "📊 To check logs from Render dashboard:"
echo "1. Go to your Render service dashboard"
echo "2. Click 'Logs' tab"
echo "3. Look for Laravel setup messages and errors"
echo "4. Common error patterns:"
echo "   - 'APP_KEY not set' → Laravel configuration issue"
echo "   - 'View not found' → Missing view files"
echo "   - 'Storage error' → Permission issues"
echo "   - '500 Server Error' → Check Laravel error logs"
echo ""

echo "🔄 If you see issues, try these URLs for debugging:"
echo "   https://$SERVICE_URL/test - Laravel diagnostics"
echo "   https://$SERVICE_URL/whatsapp-simple - Simplified WhatsApp interface"
