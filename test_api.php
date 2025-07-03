<?php
// Suppress any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');

try {
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
                'content' => 'Hello, this is a test message.'
            ]
        ],
        'model' => 'gpt-4o-mini'
    ];

    // Make the request using Guzzle
    $response = $client->post('https://chatgpt-42.p.rapidapi.com/chat', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-rapidapi-host' => 'chatgpt-42.p.rapidapi.com',
            'x-rapidapi-key' => 'f57295d344mshf5ddbf0c2dc96aap1c96e4jsnd4fd8f69117f'
        ],
        'json' => $requestData
    ]);

    // Get response body
    $responseBody = $response->getBody()->getContents();
    $httpCode = $response->getStatusCode();

    // Handle HTTP errors
    if ($httpCode !== 200) {
        throw new Exception("API Error: HTTP " . $httpCode . " - " . $responseBody);
    }

    // Parse the response
    $responseData = json_decode($responseBody, true);

    if (!$responseData) {
        throw new Exception("Invalid JSON response from API");
    }

    // Extract the AI response
    if (isset($responseData['choices'][0]['message']['content'])) {
        $aiResponse = trim($responseData['choices'][0]['message']['content']);
        
        echo json_encode([
            'success' => true,
            'response' => $aiResponse,
            'http_code' => $httpCode,
            'raw_response_length' => strlen($responseBody)
        ]);
    } else {
        throw new Exception("Unexpected response format: " . json_encode($responseData));
    }

} catch (RequestException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Network error occurred',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API test failed',
        'debug' => $e->getMessage()
    ]);
}
?> 