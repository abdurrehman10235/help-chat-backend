<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\WhatsAppBotController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/image/{folder}/{filename}', function ($folder, $filename) {
    $filename = urldecode($filename);
    $path = "public/$folder/$filename";

    if (!Storage::exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    $file = Storage::get($path);
    $mime = Storage::mimeType($path);

    return Response::make($file, 200)
        ->header('Content-Type', $mime)
        ->header('Access-Control-Allow-Origin', '*');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/user/{id}', [UserController::class, 'show']);
Route::post('/verify-id', [UserController::class, 'verifyId']);
Route::post('/users', [UserController::class, 'store']);

// Existing Service routes
Route::get('/service-from-text', [ServiceController::class, 'searchServiceByText']);
Route::get('/service/{slug}', [ServiceController::class, 'show']);
Route::get('/service-slug', [ServiceController::class, 'getSlugByName']);
Route::get('/services', [ServiceController::class, 'index']);
Route::post('/service', [ServiceController::class, 'store']);
Route::post('/service/update/{slug}', [ServiceController::class, 'update']);
Route::delete('/service/{slug}', [ServiceController::class, 'destroy']);

// **NEW**: Endpoint to get service categories (with language support)
Route::get('/service-categories', [ServiceController::class, 'getCategories']);

// WhatsApp Bot API endpoints (Legacy - whatsapp-web.js)
Route::get('/whatsapp/status', [WhatsAppBotController::class, 'getStatus']);
Route::post('/whatsapp/restart', [WhatsAppBotController::class, 'restart']);
Route::post('/whatsapp/status', [WhatsAppBotController::class, 'updateStatus']);
Route::post('/whatsapp/qr', [WhatsAppBotController::class, 'updateQR']);

// WhatsApp Business API Webhook (Official API)
Route::get('/webhook/whatsapp', [App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [App\Http\Controllers\WhatsAppWebhookController::class, 'handle']);

// WhatsApp Management API
Route::get('/whatsapp-business/test-connection', [App\Http\Controllers\WhatsAppManagementController::class, 'testConnection']);
Route::post('/whatsapp-business/send-test', [App\Http\Controllers\WhatsAppManagementController::class, 'sendTestMessage']);
Route::get('/whatsapp-business/webhook-status', [App\Http\Controllers\WhatsAppManagementController::class, 'getWebhookStatus']);
Route::post('/whatsapp-business/clear-users', [App\Http\Controllers\WhatsAppManagementController::class, 'clearAllUserData']);
Route::get('/whatsapp-business/logs', [App\Http\Controllers\WhatsAppManagementController::class, 'getWebhookLogs']);

// Web-like routes moved to API to bypass web middleware issues
Route::get('/web-test', function () {
    return response()->json([
        'status' => 'Laravel is working via API',
        'environment' => 'production',
        'timestamp' => date('Y-m-d H:i:s'),
        'memory_usage' => memory_get_usage(true),
        'php_version' => PHP_VERSION
    ]);
});

Route::get('/web-whatsapp-business', function () {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Business API - Management Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #25D366;
        }
        .btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        .btn:hover {
            background: #128C7E;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .input-group {
            margin: 15px 0;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .response {
            background: #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .success {
            border-left: 4px solid #28a745;
            background: #d4edda;
        }
        .error {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
        }
        .info {
            border-left: 4px solid #17a2b8;
            background: #d1ecf1;
        }
        .webhook-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 WhatsApp Business API</h1>
            <p>Management Dashboard</p>
        </div>

        <div class="webhook-info">
            <h3>🔗 Webhook Configuration</h3>
            <p><strong>Webhook URL:</strong> <code id="webhook-url">Loading...</code></p>
            <p><strong>Verify Token:</strong> <code>webhook_verify_secure_123</code></p>
            <p>Use these values in your Meta Developer Console → WhatsApp → Configuration</p>
        </div>

        <div class="status-card">
            <h3>📊 Connection Status</h3>
            <button class="btn" onclick="testConnection()">Test API Connection</button>
            <button class="btn btn-secondary" onclick="getWebhookStatus()">Get Configuration Status</button>
            <div id="connection-status"></div>
        </div>

        <div class="status-card">
            <h3>💬 Send Test Message</h3>
            <div class="input-group">
                <label>Phone Number (with country code):</label>
                <input type="text" id="test-phone" placeholder="e.g., 1234567890" value="">
            </div>
            <div class="input-group">
                <label>Message:</label>
                <textarea id="test-message" rows="3" placeholder="Enter your test message">👋 Hello! This is a test message from WhatsApp Business API. The hotel service bot is working perfectly!</textarea>
            </div>
            <button class="btn" onclick="sendTestMessage()">Send Test Message</button>
            <div id="test-message-result"></div>
        </div>

        <div class="status-card">
            <h3>🎤 Voice Message Support</h3>
            <p>✅ Voice messages are supported!</p>
            <p>Users can send voice notes and the system will:</p>
            <ul>
                <li>Acknowledge receipt of voice message</li>
                <li>Encourage users to send text or voice for service requests</li>
                <li>Ready for future speech-to-text integration</li>
            </ul>
        </div>

        <div class="status-card">
            <h3>🔧 Management Actions</h3>
            <button class="btn btn-secondary" onclick="clearUserData()">Clear All User Data</button>
            <button class="btn btn-secondary" onclick="getWebhookLogs()">View Webhook Logs</button>
            <div id="management-result"></div>
        </div>

        <div class="status-card">
            <h3>📋 Feature Overview</h3>
            <ul>
                <li>✅ <strong>Official WhatsApp Business API</strong> - No QR codes needed</li>
                <li>✅ <strong>Bilingual Support</strong> - English and Arabic</li>
                <li>✅ <strong>Voice Message Recognition</strong> - Handles voice notes gracefully</li>
                <li>✅ <strong>Service Search</strong> - Intelligent keyword matching</li>
                <li>✅ <strong>Image Support</strong> - Send service images with descriptions</li>
                <li>✅ <strong>User Sessions</strong> - Language preferences stored in cache</li>
                <li>✅ <strong>Error Handling</strong> - Comprehensive logging and fallbacks</li>
                <li>✅ <strong>Rate Limiting Protection</strong> - Delays between messages</li>
            </ul>
        </div>
    </div>

    <script>
        // Set webhook URL
        document.getElementById("webhook-url").textContent = window.location.origin + "/api/webhook/whatsapp";

        async function testConnection() {
            try {
                showLoading("connection-status", "Testing API connection...");
                const response = await fetch("/api/whatsapp-business/test-connection");
                const data = await response.json();
                
                if (data.status === "success") {
                    showResult("connection-status", `
                        <div class="response success">
                            ✅ <strong>Connection Successful!</strong><br>
                            Phone: ${data.phone_number}<br>
                            Name: ${data.verified_name}
                        </div>
                    `);
                } else {
                    showResult("connection-status", `
                        <div class="response error">
                            ❌ <strong>Connection Failed:</strong> ${data.message}
                        </div>
                    `);
                }
            } catch (error) {
                showResult("connection-status", `
                    <div class="response error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `);
            }
        }

        async function getWebhookStatus() {
            try {
                showLoading("connection-status", "Getting webhook status...");
                const response = await fetch("/api/whatsapp-business/webhook-status");
                const data = await response.json();
                
                let envStatus = "";
                for (const [key, value] of Object.entries(data.environment)) {
                    envStatus += `${key}: ${value}<br>`;
                }
                
                showResult("connection-status", `
                    <div class="response info">
                        📋 <strong>Configuration Status:</strong><br>
                        Webhook URL: ${data.webhook_url}<br>
                        <br><strong>Environment Variables:</strong><br>
                        ${envStatus}
                    </div>
                `);
            } catch (error) {
                showResult("connection-status", `
                    <div class="response error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `);
            }
        }

        async function sendTestMessage() {
            const phone = document.getElementById("test-phone").value;
            const message = document.getElementById("test-message").value;
            
            if (!phone || !message) {
                showResult("test-message-result", `
                    <div class="response error">
                        ❌ Please enter both phone number and message
                    </div>
                `);
                return;
            }
            
            try {
                showLoading("test-message-result", "Sending test message...");
                const response = await fetch("/api/whatsapp-business/send-test", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ phone, message })
                });
                
                const data = await response.json();
                
                if (data.status === "success") {
                    showResult("test-message-result", `
                        <div class="response success">
                            ✅ <strong>Message Sent!</strong><br>
                            Message ID: ${data.message_id}
                        </div>
                    `);
                } else {
                    showResult("test-message-result", `
                        <div class="response error">
                            ❌ <strong>Failed to send:</strong> ${data.message}
                        </div>
                    `);
                }
            } catch (error) {
                showResult("test-message-result", `
                    <div class="response error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `);
            }
        }

        async function clearUserData() {
            try {
                showLoading("management-result", "Clearing user data...");
                const response = await fetch("/api/whatsapp-business/clear-users", {
                    method: "POST"
                });
                const data = await response.json();
                
                showResult("management-result", `
                    <div class="response success">
                        ✅ ${data.message}
                    </div>
                `);
            } catch (error) {
                showResult("management-result", `
                    <div class="response error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `);
            }
        }

        async function getWebhookLogs() {
            try {
                showLoading("management-result", "Getting webhook logs...");
                const response = await fetch("/api/whatsapp-business/logs");
                const data = await response.json();
                
                showResult("management-result", `
                    <div class="response info">
                        📝 <strong>Logs Location:</strong><br>
                        ${data.log_location}<br>
                        <br>Check Laravel logs for detailed webhook activity.
                    </div>
                `);
            } catch (error) {
                showResult("management-result", `
                    <div class="response error">
                        ❌ <strong>Error:</strong> ${error.message}
                    </div>
                `);
            }
        }

        function showLoading(elementId, message) {
            document.getElementById(elementId).innerHTML = `
                <div class="response info">
                    ⏳ ${message}
                </div>
            `;
        }

        function showResult(elementId, html) {
            document.getElementById(elementId).innerHTML = html;
        }

        // Auto-test connection on page load
        setTimeout(testConnection, 1000);
    </script>
</body>
</html>';
    
    return response($html, 200, ['Content-Type' => 'text/html']);
});