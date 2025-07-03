<?php
// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Initialize Guzzle HTTP Client
$client = new Client([
    'timeout' => 30,
    'connect_timeout' => 10,
]);

// Prepare the request data
$requestData = [
    'messages' => [
        [
            'role' => 'user',
            'content' => 'hi'
        ]
    ],
    'model' => 'gpt-4o-mini'
];

try {
    // Make the request using Guzzle
    $response = $client->post('https://chatgpt-42.p.rapidapi.com/chat', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-rapidapi-key' => 'f57295d344mshf5ddbf0c2dc96aap1c96e4jsnd4fd8f69117f',
            'x-rapidapi-host' => 'chatgpt-42.p.rapidapi.com'
        ],
        'json' => $requestData
    ]);

    // Get response body
    $responseBody = $response->getBody()->getContents();
    
    echo $responseBody;
    
} catch (RequestException $e) {
    echo "Request Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 