<?php
// Test raw response from the API
$curl = curl_init();

// Prepare the request data
$requestData = [
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Hello, this is a test message.'
        ]
    ],
    'model' => 'gpt-4o-mini'
];

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => "https://chatgpt-42.p.rapidapi.com/chat",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-rapidapi-host: chatgpt-42.p.rapidapi.com",
        "x-rapidapi-key: fe104cc727msh41e7e8a30cd66fcp188aafjsn688437fbe726"
    ],
]);

// Execute the request
$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);

curl_close($curl);

echo "HTTP Code: " . $httpCode . "\n";
echo "cURL Error: " . ($err ?: 'None') . "\n";
echo "Raw Response:\n";
echo $response . "\n";
?> 