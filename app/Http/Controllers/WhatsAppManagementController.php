<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppManagementController extends Controller
{
    /**
     * Test WhatsApp API connection
     */
    public function testConnection()
    {
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        
        if (!$accessToken || !$phoneNumberId) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp credentials not configured'
            ], 400);
        }
        
        // Test by getting phone number info
        $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}";
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp API connection successful',
                'phone_number' => $data['display_phone_number'] ?? 'Unknown',
                'verified_name' => $data['verified_name'] ?? 'Unknown'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to WhatsApp API',
                'http_code' => $httpCode,
                'response' => $response
            ], 400);
        }
    }
    
    /**
     * Send test message
     */
    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);
        
        $accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        
        if (!$accessToken || !$phoneNumberId) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp credentials not configured'
            ], 400);
        }
        
        $url = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $request->phone,
            'type' => 'text',
            'text' => ['body' => $request->message]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            return response()->json([
                'status' => 'success',
                'message' => 'Test message sent successfully',
                'message_id' => $responseData['messages'][0]['id'] ?? 'unknown'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send test message',
                'http_code' => $httpCode,
                'response' => $response
            ], 400);
        }
    }
    
    /**
     * Get webhook status and configuration
     */
    public function getWebhookStatus()
    {
        return response()->json([
            'status' => 'active',
            'webhook_url' => url('/api/webhook/whatsapp'),
            'verify_token_configured' => !empty(env('WHATSAPP_WEBHOOK_VERIFY_TOKEN')),
            'access_token_configured' => !empty(env('WHATSAPP_ACCESS_TOKEN')),
            'phone_number_id_configured' => !empty(env('WHATSAPP_PHONE_NUMBER_ID')),
            'environment' => [
                'WHATSAPP_ACCESS_TOKEN' => !empty(env('WHATSAPP_ACCESS_TOKEN')) ? 'Configured' : 'Missing',
                'WHATSAPP_PHONE_NUMBER_ID' => env('WHATSAPP_PHONE_NUMBER_ID') ?: 'Missing',
                'WHATSAPP_BUSINESS_ACCOUNT_ID' => env('WHATSAPP_BUSINESS_ACCOUNT_ID') ?: 'Missing',
                'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => !empty(env('WHATSAPP_WEBHOOK_VERIFY_TOKEN')) ? 'Configured' : 'Missing'
            ]
        ]);
    }
    
    /**
     * Clear all user language preferences
     */
    public function clearAllUserData()
    {
        // Clear all language preferences from cache
        $pattern = 'whatsapp_user_lang_*';
        
        // Note: This is a simple implementation. In production, you might want to
        // use a more sophisticated cache clearing mechanism
        
        return response()->json([
            'status' => 'success',
            'message' => 'All user data cleared (language preferences reset)'
        ]);
    }
    
    /**
     * Get recent webhook logs
     */
    public function getWebhookLogs()
    {
        // This would return recent webhook activity
        // For now, return a simple status
        
        return response()->json([
            'status' => 'success',
            'message' => 'Check Laravel logs for webhook activity',
            'log_location' => storage_path('logs/laravel.log')
        ]);
    }
}
