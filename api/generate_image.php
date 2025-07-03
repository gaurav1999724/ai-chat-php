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

if (!$input || !isset($input['text'])) {
    error_log("Invalid input: " . json_encode($input));
    http_response_code(400);
    echo json_encode(['error' => 'Text prompt is required']);
    exit();
}

$text = trim($input['text']);
$width = $input['width'] ?? 512;
$height = $input['height'] ?? 512;
$steps = $input['steps'] ?? 1;
$sessionId = $input['session_id'] ?? '';

if (empty($text)) {
    error_log("Text prompt is empty");
    http_response_code(400);
    echo json_encode(['error' => 'Text prompt cannot be empty']);
    exit();
}

// Validate dimensions
if ($width < 256 || $width > 1024 || $height < 256 || $height > 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Image dimensions must be between 256 and 1024 pixels']);
    exit();
}

// Validate steps
if ($steps < 1 || $steps > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Steps must be between 1 and 50']);
    exit();
}

try {
    // Prepare the request data
    $requestData = [
        'text' => $text,
        'width' => (int)$width,
        'height' => (int)$height,
        'steps' => (int)$steps
    ];
    
    error_log("Image generation request: " . json_encode($requestData));
    
    // Initialize Guzzle HTTP Client
    $client = new Client([
        'timeout' => 60, // Longer timeout for image generation
        'connect_timeout' => 10,
    ]);
    
    // Make the request using Guzzle
    $response = $client->post('https://chatgpt-42.p.rapidapi.com/texttoimage3', [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-rapidapi-host' => getenv('RAPIDAPI_HOST') ?: 'chatgpt-42.p.rapidapi.com',
            'x-rapidapi-key' => getenv('RAPIDAPI_KEY') ?: 'fe104cc727msh41e7e8a30cd66fcp188aafjsn688437fbe726'
        ],
        'json' => $requestData
    ]);

    // Get response body
    $responseBody = $response->getBody()->getContents();
    $httpCode = $response->getStatusCode();

    // Handle HTTP errors
    if ($httpCode !== 200) {
        error_log("Image API Error: HTTP " . $httpCode . " - " . $responseBody);
        throw new Exception("Image generation service temporarily unavailable");
    }

    // Parse the response
    $responseData = json_decode($responseBody, true);

    if (!$responseData) {
        throw new Exception("Invalid response from image API");
    }

    // Log the interaction
    logImageGeneration($sessionId, $text, $requestData, $responseData);
    
    echo json_encode([
        'success' => true,
        'image_data' => $responseData,
        'prompt' => $text,
        'dimensions' => [
            'width' => $width,
            'height' => $height
        ]
    ]);

} catch (RequestException $e) {
    error_log("Guzzle Image Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Network error occurred during image generation. Please try again.',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Image Generation API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Sorry, I encountered an error generating the image. Please try again.',
        'debug' => $e->getMessage()
    ]);
}

/**
 * Log the image generation interaction for debugging and monitoring
 */
function logImageGeneration($sessionId, $text, $requestData, $responseData) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => $sessionId,
        'prompt' => $text,
        'request_data' => $requestData,
        'response_data' => $responseData
    ];
    
    $logFile = __DIR__ . '/../logs/image_generation.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Write to log file
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
}
?> 