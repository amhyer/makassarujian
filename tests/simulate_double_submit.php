<?php

$url = 'http://127.0.0.1:8000/webhook/payment';

// Create a mock payload
$payload = json_encode([
    'order_id' => 'TEST-ORDER-123',
    'status' => 'success',
    'gross_amount' => '10000',
    // We can skip signature for testing if we just want to see the 409 response
    // Or it might fail at signature validation if the middleware runs after. Wait, middleware runs BEFORE controller!
    // So the signature is not even checked before the 409 is thrown.
]);

$mh = curl_multi_init();
$handles = [];

// Create 3 concurrent requests
for ($i = 0; $i < 3; $i++) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}

echo "Sending 3 concurrent requests to $url...\n";

// Execute all queries simultaneously
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

echo "\nResults:\n";

foreach ($handles as $index => $ch) {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response = curl_multi_getcontent($ch);
    
    echo "Request " . ($index + 1) . " - HTTP Status: $httpCode\n";
    echo "Response: " . substr($response, 0, 100) . "\n\n";
    
    curl_multi_remove_handle($mh, $ch);
}

curl_multi_close($mh);
