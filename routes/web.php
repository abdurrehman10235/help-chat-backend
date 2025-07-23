<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\WhatsAppBotController;

// Laravel welcome page (simplified)
Route::get('/', function () {
    return '<!DOCTYPE html><html><head><title>Laravel App</title></head><body><h1>Laravel Application is Running</h1><p>API available at <a href="/api/whatsapp/status">/api/whatsapp/status</a></p></body></html>';
});

// Test route for debugging (simplified)
Route::get('/test', function () {
    return response()->json([
        'status' => 'Laravel is working',
        'environment' => 'production',
        'timestamp' => date('Y-m-d H:i:s'),
        'memory_usage' => memory_get_usage(true),
        'php_version' => PHP_VERSION
    ]);
});

// Simple WhatsApp page without complex view logic
Route::get('/whatsapp-simple', function () {
    return '<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Bot Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div style="text-align: center; margin: 50px;">
        <h1>🤖 WhatsApp Bot Status</h1>
        <div id="status">Loading...</div>
        <div id="qr-container" style="margin: 20px;"></div>
        <button onclick="refreshStatus()">Refresh Status</button>
    </div>
    <script>
        async function refreshStatus() {
            try {
                const response = await fetch("/api/whatsapp/status");
                const data = await response.json();
                document.getElementById("status").innerHTML = 
                    `<p><strong>Status:</strong> ${data.status}</p>
                     <p><strong>Message:</strong> ${data.message || "N/A"}</p>
                     <p><strong>Time:</strong> ${data.timestamp}</p>`;
                
                if (data.qr) {
                    document.getElementById("qr-container").innerHTML = 
                        `<h3>Scan QR Code:</h3><img src="data:image/png;base64,${data.qr}" style="max-width: 300px;">`;
                } else {
                    document.getElementById("qr-container").innerHTML = "<p>No QR code available</p>";
                }
            } catch (error) {
                document.getElementById("status").innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            }
        }
        
        // Auto-refresh every 5 seconds
        refreshStatus();
        setInterval(refreshStatus, 5000);
    </script>
</body>
</html>';
});

// WhatsApp Bot QR Code Interface (simplified for production)
Route::get('/whatsapp', function () {
    return '<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp Bot - QR Code</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .qr-code {
            max-width: 300px;
            margin: 20px auto;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
        .refresh-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>WhatsApp Bot</h1>
        <div id="status">Loading...</div>
        <div id="qr-container"></div>
        <button onclick="refreshStatus()" class="refresh-btn">Refresh Status</button>
    </div>
    <script>
        async function refreshStatus() {
            try {
                const response = await fetch("/api/whatsapp/status");
                const data = await response.json();
                document.getElementById("status").innerHTML = 
                    `<div class="status waiting"><strong>Status:</strong> ${data.status}<br><strong>Message:</strong> ${data.message || "N/A"}</div>`;
                
                if (data.qr) {
                    document.getElementById("qr-container").innerHTML = 
                        `<h3>Scan QR Code:</h3><img src="data:image/png;base64,${data.qr}" class="qr-code">`;
                } else {
                    document.getElementById("qr-container").innerHTML = "<p>No QR code available</p>";
                }
            } catch (error) {
                document.getElementById("status").innerHTML = `<div class="status error">Error: ${error.message}</div>`;
            }
        }
        
        // Auto-refresh every 5 seconds
        refreshStatus();
        setInterval(refreshStatus, 5000);
    </script>
</body>
</html>';
});