<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\WhatsAppBotController;

// Test route for debugging
Route::get('/test', function () {
    try {
        return response()->json([
            'status' => 'Laravel is working',
            'environment' => app()->environment(),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(storage_path('framework/cache')),
            'view_path_exists' => file_exists(resource_path('views/whatsapp-qr.blade.php')),
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
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

// WhatsApp Bot QR Code Interface
Route::get('/whatsapp', [WhatsAppBotController::class, 'showQRPage']);

Route::get('/app/{any}', function () {
    return File::get(public_path('app/index.html'));
})->where('any', '.*');

Route::get('/admin/{any}', function () {
    return file_get_contents(public_path('admin/index.html'));
})->where('any', '.*');