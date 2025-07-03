<?php
// Load production configuration
require_once __DIR__ . '/../production-config.php';

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("OPTIONS request received");
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['message'])) {
    error_log("Invalid input: " . json_encode($input));
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit();
}

$message = trim($input['message']);
$sessionId = $input['session_id'] ?? '';

if (empty($message)) {
    error_log("Message is empty");
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

try {
    // Prepare the request data
    $requestData = [
        'messages' => [
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'model' => 'gpt-4o-mini'
    ];
    
    error_log(json_encode($requestData));
    
    // Initialize Guzzle HTTP Client
    $client = new Client([
        'timeout' => 30,
        'connect_timeout' => 10,
    ]);
    
    // Make the request using Guzzle
    $response = $client->post('https://chatgpt-42.p.rapidapi.com/chat', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-rapidapi-host' => getenv('RAPIDAPI_HOST') ?: 'chatgpt-42.p.rapidapi.com',
            'x-rapidapi-key' => getenv('RAPIDAPI_KEY') ?: 'f57295d344mshf5ddbf0c2dc96aap1c96e4jsnd4fd8f69117f'
        ],
        'json' => $requestData
    ]);

    // Get response body
    $responseBody = $response->getBody()->getContents();
    $httpCode = $response->getStatusCode();

    // Handle HTTP errors
    if ($httpCode !== 200) {
        error_log("API Error: HTTP " . $httpCode . " - " . $responseBody);
        throw new Exception("API service temporarily unavailable");
    }

    // Parse the response
    $responseData = json_decode($responseBody, true);

    if (!$responseData) {
        throw new Exception("Invalid response from API");
    }

    // Extract the AI response
    if (isset($responseData['choices'][0]['message']['content'])) {
        $aiResponse = trim($responseData['choices'][0]['message']['content']);
        
        // Log the interaction
        logInteraction($sessionId, $message, $aiResponse, $responseData);
        
        echo json_encode([
            'success' => true,
            'response' => $aiResponse
        ]);
    } else {
        throw new Exception("Unexpected response format");
    }

} catch (RequestException $e) {
    error_log("Guzzle Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Network error occurred. Please try again.',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("ChatGPT API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Sorry, I encountered an error. Please try again.',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Log the interaction for debugging and monitoring
 */
function logInteraction($sessionId, $userMessage, $aiResponse, $fullResponse) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => $sessionId,
        'user_message' => $userMessage,
        'ai_response' => $aiResponse,
        'full_response' => $fullResponse
    ];
    
    $logFile = __DIR__ . '/../logs/api_interactions.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Write to log file
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
}
?> 