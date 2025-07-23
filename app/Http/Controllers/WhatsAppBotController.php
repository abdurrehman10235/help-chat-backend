<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WhatsAppBotController extends Controller
{
    private $statusFile = 'whatsapp_status.json';
    private $qrFile = 'whatsapp_qr.txt';

    public function showQRPage()
    {
        try {
            // Check if view file exists
            if (!view()->exists('whatsapp-qr')) {
                return response()->json([
                    'error' => 'View file not found',
                    'path' => resource_path('views/whatsapp-qr.blade.php'),
                    'exists' => file_exists(resource_path('views/whatsapp-qr.blade.php'))
                ], 500);
            }

            // Check storage permissions
            if (!Storage::exists('')) {
                try {
                    Storage::put('test.txt', 'test');
                    Storage::delete('test.txt');
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Storage not writable',
                        'message' => $e->getMessage()
                    ], 500);
                }
            }

            return view('whatsapp-qr');
            
        } catch (\Exception $e) {
            Log::error('WhatsApp QR page error: ' . $e->getMessage());
            Log::error('Error details: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to load WhatsApp page',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function getStatus()
    {
        try {
            $status = $this->getWhatsAppStatus();
            $qr = $this->getQRCode();
            
            return response()->json([
                'status' => $status['status'] ?? 'unknown',
                'message' => $status['message'] ?? null,
                'qr' => $qr,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp status error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to retrieve status'
            ], 500);
        }
    }

    public function restart()
    {
        try {
            // Clear existing status and QR
            Storage::delete($this->statusFile);
            Storage::delete($this->qrFile);
            
            // Signal bot to restart (you can implement this based on your needs)
            Storage::put('whatsapp_restart.flag', now()->toISOString());
            
            return response()->json([
                'success' => true,
                'message' => 'Bot restart initiated'
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp restart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart bot'
            ], 500);
        }
    }

    private function getWhatsAppStatus()
    {
        if (!Storage::exists($this->statusFile)) {
            return ['status' => 'initializing'];
        }

        $content = Storage::get($this->statusFile);
        $status = json_decode($content, true);
        
        if (!$status) {
            return ['status' => 'unknown'];
        }

        // Check if status is too old (older than 30 seconds)
        if (isset($status['timestamp'])) {
            $timestamp = \Carbon\Carbon::parse($status['timestamp']);
            if ($timestamp->diffInSeconds(now()) > 30) {
                return ['status' => 'timeout', 'message' => 'Status outdated'];
            }
        }

        return $status;
    }

    private function getQRCode()
    {
        if (!Storage::exists($this->qrFile)) {
            return null;
        }

        $qrContent = Storage::get($this->qrFile);
        
        // If it's already base64, return it
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $qrContent)) {
            return $qrContent;
        }
        
        // Otherwise encode it
        return base64_encode($qrContent);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'message' => 'nullable|string'
        ]);

        $status = [
            'status' => $request->status,
            'message' => $request->message,
            'timestamp' => now()->toISOString()
        ];

        Storage::put($this->statusFile, json_encode($status));

        return response()->json(['success' => true]);
    }

    public function updateQR(Request $request)
    {
        $request->validate([
            'qr' => 'required|string'
        ]);

        Storage::put($this->qrFile, $request->qr);

        return response()->json(['success' => true]);
    }
}
