<?php
// Suppress any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Include database configuration
    require_once 'config/database.php';
    
    // Test database connection
    $pdo = getDatabaseConnection();
    
    // Test if we can query the database
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'database' => DB_NAME,
            'host' => DB_HOST
        ]);
    } else {
        throw new Exception("Database query test failed");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'debug' => $e->getMessage()
    ]);
}
?> 