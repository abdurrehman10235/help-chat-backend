<?php

/**
 * Test script for Enhanced Elite Hotel Casablanca WhatsApp Bot
 * Testing the new branded welcome with logo and session management
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

// Test the Enhanced Elite Hotel Casablanca bot
echo "🏨✨ Testing Enhanced Elite Hotel Casablanca WhatsApp Bot ✨🏨\n";
echo "================================================================\n\n";

$testPhone = '+1234567890';
$testPhone2 = '+9876543210';

// Test 1: First-time user - should get branded welcome with logo
echo "🌟 TEST 1: First-time user (should get full branded welcome)\n";
testBotMessage($testPhone, 'hi');
sleep(1);

// Test 2: Same user immediately after - should get quick welcome
echo "🔄 TEST 2: Same user again (should get quick welcome)\n";
testBotMessage($testPhone, 'hello');
sleep(1);

// Test 3: Different user - should get branded welcome
echo "👤 TEST 3: Different user (should get full branded welcome)\n";
testBotMessage($testPhone2, 'مرحبا');
sleep(1);

// Test 4: Test hotel services
echo "🏨 TEST 4: Testing hotel services\n";
testBotMessage($testPhone, 'hotel tour');
sleep(1);

testBotMessage($testPhone, 'laundry');
sleep(1);

echo "✅ Enhanced Elite Hotel Casablanca Bot Tests Completed!\n";
