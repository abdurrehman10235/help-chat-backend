<?php

/**
 * Test script for Elite Casablanca WhatsApp Bot
 * This simulates WhatsApp webhook messages to test the new conversation flow
 */

function testBotMessage($from, $message, $type = 'text') {
    $url = 'http://localhost:8000/api/webhook/whatsapp';
    
    $payload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messages' => [
                                [
                                    'from' => $from,
                                    'id' => 'test_' . uniqid(),
                                    'timestamp' => time(),
                                    'type' => $type,
                                    'text' => [
                                        'body' => $message
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: WhatsApp/Test'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "=== Testing: '$message' (from: $from) ===\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    echo "==========================================\n\n";
    
    return $response;
}

// Test the Elite Casablanca bot conversation flow
echo "🏨 Testing Elite Casablanca WhatsApp Bot\n";
echo "========================================\n\n";

$testPhone = '+1234567890';

// Test 1: Welcome message
testBotMessage($testPhone, 'hi');

// Test 2: Hotel Tour request
testBotMessage($testPhone, 'hotel tour');

// Test 3: Restaurant request
testBotMessage($testPhone, 'restaurant');

// Test 4: Room Service request
testBotMessage($testPhone, 'room service');

// Test 5: Reception services
testBotMessage($testPhone, 'reception');

// Test 6: Wake-up call request
testBotMessage($testPhone, 'wake up call');

// Test 7: Time input for wake-up call
testBotMessage($testPhone, 'tomorrow at 7 AM');

// Test 8: Back to main menu
testBotMessage($testPhone, 'main menu');

// Test 9: Explore Jeddah
testBotMessage($testPhone, 'explore jeddah');

// Test 10: Arabic test
testBotMessage($testPhone, 'مرحبا');

echo "✅ All tests completed!\n";
