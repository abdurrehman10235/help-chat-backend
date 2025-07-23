<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Bot - QR Code</title>
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
        .logo {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .qr-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .qr-code {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
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
        .instructions {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
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
        .refresh-btn:hover {
            background: #128C7E;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #25D366;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>WhatsApp Bot Setup</h1>
        <p class="subtitle">Scan the QR code to connect your WhatsApp</p>
        
        <div id="status" class="status waiting">
            <div class="loading"></div>
            Waiting for QR code...
        </div>
        
        <div class="qr-container">
            <div id="qr-placeholder">
                <p>🔄 Generating QR code...</p>
                <p style="font-size: 14px; color: #666;">This may take a moment</p>
            </div>
        </div>
        
        <button class="refresh-btn" onclick="refreshQR()">🔄 Refresh QR Code</button>
        
        <div class="instructions">
            <h3>📋 How to connect:</h3>
            <ol>
                <li>Open WhatsApp on your phone</li>
                <li>Go to <strong>Settings</strong> → <strong>Linked Devices</strong></li>
                <li>Tap <strong>"Link a Device"</strong></li>
                <li>Scan the QR code above</li>
                <li>Wait for connection confirmation</li>
            </ol>
        </div>
    </div>

    <script>
        let pollInterval;
        
        function updateStatus(message, type = 'waiting') {
            const statusEl = document.getElementById('status');
            statusEl.className = `status ${type}`;
            statusEl.innerHTML = message;
        }
        
        function updateQR(qrData) {
            const container = document.querySelector('.qr-container');
            if (qrData) {
                container.innerHTML = `<img src="data:image/png;base64,${qrData}" class="qr-code" alt="WhatsApp QR Code">`;
            } else {
                container.innerHTML = `
                    <div id="qr-placeholder">
                        <p>❌ QR code not available</p>
                        <p style="font-size: 14px; color: #666;">Click refresh to try again</p>
                    </div>
                `;
            }
        }
        
        async function checkStatus() {
            try {
                const response = await fetch('/api/whatsapp/status');
                const data = await response.json();
                
                if (data.status === 'qr_ready') {
                    updateStatus('📱 QR Code ready - Scan with WhatsApp', 'waiting');
                    updateQR(data.qr);
                } else if (data.status === 'connected') {
                    updateStatus('✅ WhatsApp Connected Successfully!', 'connected');
                    updateQR(null);
                    clearInterval(pollInterval);
                } else if (data.status === 'connecting') {
                    updateStatus('🔄 Connecting to WhatsApp...', 'waiting');
                } else if (data.status === 'error') {
                    updateStatus('❌ Connection Error: ' + (data.message || 'Unknown error'), 'error');
                } else {
                    updateStatus('🔄 Starting WhatsApp bot...', 'waiting');
                }
            } catch (error) {
                updateStatus('❌ Unable to connect to bot service', 'error');
                console.error('Status check failed:', error);
            }
        }
        
        function refreshQR() {
            updateStatus('🔄 Refreshing...', 'waiting');
            fetch('/api/whatsapp/restart', { method: 'POST' })
                .then(() => {
                    setTimeout(checkStatus, 2000);
                })
                .catch(() => {
                    updateStatus('❌ Failed to restart bot', 'error');
                });
        }
        
        // Start polling for status
        checkStatus();
        pollInterval = setInterval(checkStatus, 3000);
        
        // Stop polling after 10 minutes to avoid excessive requests
        setTimeout(() => {
            if (pollInterval) {
                clearInterval(pollInterval);
                updateStatus('⏰ Polling stopped. Click refresh to continue.', 'waiting');
            }
        }, 600000);
    </script>
</body>
</html>
