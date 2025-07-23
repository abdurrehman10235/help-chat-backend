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

// WhatsApp Bot API endpoints
Route::get('/whatsapp/status', [WhatsAppBotController::class, 'getStatus']);
Route::post('/whatsapp/restart', [WhatsAppBotController::class, 'restart']);
Route::post('/whatsapp/status', [WhatsAppBotController::class, 'updateStatus']);
Route::post('/whatsapp/qr', [WhatsAppBotController::class, 'updateQR']);

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

Route::get('/web-whatsapp', function () {
    return response('<!DOCTYPE html>
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
        .status.waiting {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.connected {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                
                let statusClass = "waiting";
                let statusText = data.status;
                
                // Handle different status types
                if (data.status === "ready") {
                    statusClass = "connected";
                    statusText = "✅ Connected and Ready!";
                } else if (data.status === "authenticated") {
                    statusClass = "waiting";
                    statusText = "🔐 Authenticated! Connecting to WhatsApp...";
                } else if (data.status === "loading") {
                    statusClass = "waiting";
                    statusText = `📱 Loading WhatsApp... ${data.extra?.percent || 0}%`;
                } else if (data.status === "qr_expired") {
                    statusClass = "waiting";
                    statusText = "⏰ QR Code Expired - Generating new one...";
                } else if (data.status === "auth_failed") {
                    statusClass = "error";
                    statusText = "❌ Authentication Failed - Restarting...";
                } else if (data.status === "disconnected") {
                    statusClass = "error";
                    statusText = "❌ Disconnected - Reconnecting...";
                } else if (data.status === "qr_ready") {
                    statusClass = "waiting";
                    statusText = "📱 QR Code Ready - Scan within 60 seconds!";
                }
                
                document.getElementById("status").innerHTML = 
                    `<div class="status ${statusClass}"><strong>Status:</strong> ${statusText}<br><strong>Message:</strong> ${data.message || "N/A"}</div>`;
                
                if (data.qr && data.status === "qr_ready") {
                    document.getElementById("qr-container").innerHTML = 
                        `<h3>📱 Scan QR Code (expires in 60 seconds):</h3><img src="data:image/png;base64,${data.qr}" class="qr-code">
                        <p style="color: #666; font-size: 14px;">✅ Take your time - you have 60 seconds to scan<br>📱 Open WhatsApp → Settings → Linked Devices → Link a Device</p>`;
                } else if (data.status === "authenticated" || data.status === "loading") {
                    document.getElementById("qr-container").innerHTML = "<p>🔐 QR Code scanned successfully! Connecting...</p>";
                } else if (data.status === "ready") {
                    document.getElementById("qr-container").innerHTML = "<p>✅ Successfully connected to WhatsApp!</p>";
                } else {
                    document.getElementById("qr-container").innerHTML = "<p>Waiting for QR code...</p>";
                }
            } catch (error) {
                document.getElementById("status").innerHTML = `<div class="status error">Error: ${error.message}</div>`;
            }
        }
        
        // Auto-refresh every 3 seconds for better QR handling
        refreshStatus();
        setInterval(refreshStatus, 3000);
    </script>
</body>
</html>', 200, ['Content-Type' => 'text/html']);
});