<?php
// Suppress any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$requiredFields = ['session_id', 'sender', 'message'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is required"]);
        exit();
    }
}

$sessionId = trim($input['session_id']);
$sender = trim($input['sender']);
$message = trim($input['message']);

// Validate sender
if (!in_array($sender, ['user', 'ai'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sender must be either "user" or "ai"']);
    exit();
}

try {
    // Initialize database if needed
    if (!initializeDatabase()) {
        throw new Exception("Database initialization failed");
    }
    
    // Save the message
    if (saveMessage($sessionId, $sender, $message)) {
        echo json_encode([
            'success' => true,
            'message' => 'Message saved successfully'
        ]);
    } else {
        throw new Exception("Failed to save message");
    }
    
} catch (Exception $e) {
    error_log("Error saving message: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save message',
        'debug' => $e->getMessage()
    ]);
}
?> 