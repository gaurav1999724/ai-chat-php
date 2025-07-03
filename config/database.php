<?php
/**
 * Database Configuration
 * 
 * This file contains database connection settings and helper functions
 * for the AI Chat application.
 */

// Database configuration - Environment-based
if (getenv('DB_HOST')) {
    // Production environment (AWS)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'ai_chat');
    define('DB_USER', getenv('DB_USER') ?: 'ai_chat_user');
    define('DB_PASS', getenv('DB_PASS') ?: 'your_secure_password');
} else {
    // Local development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ai_chat');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * 
 * @return PDO Database connection
 * @throws Exception If connection fails
 */
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Initialize database tables
 * 
 * @return bool True if successful, false otherwise
 */
function initializeDatabase() {
    try {
        $pdo = getDatabaseConnection();
        
        // Create sessions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_session_id (session_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create messages table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) NOT NULL,
                sender ENUM('user', 'ai') NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_session_id (session_id),
                INDEX idx_sender (sender),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create API logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) NOT NULL,
                request_data TEXT,
                response_data TEXT,
                status_code INT,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_session_id (session_id),
                INDEX idx_created_at (created_at),
                INDEX idx_status_code (status_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Save or update session
 * 
 * @param string $sessionId Session ID
 * @return bool True if successful, false otherwise
 */
function saveSession($sessionId) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO chat_sessions (session_id) 
            VALUES (:session_id) 
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute(['session_id' => $sessionId]);
    } catch (Exception $e) {
        error_log("Error saving session: " . $e->getMessage());
        return false;
    }
}

/**
 * Save message to database
 * 
 * @param string $sessionId Session ID
 * @param string $sender 'user' or 'ai'
 * @param string $message Message content
 * @return bool True if successful, false otherwise
 */
function saveMessage($sessionId, $sender, $message) {
    try {
        $pdo = getDatabaseConnection();
        
        // First, ensure session exists
        saveSession($sessionId);
        
        // Save the message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (session_id, sender, message) 
            VALUES (:session_id, :sender, :message)
        ");
        
        return $stmt->execute([
            'session_id' => $sessionId,
            'sender' => $sender,
            'message' => $message
        ]);
    } catch (Exception $e) {
        error_log("Error saving message: " . $e->getMessage());
        return false;
    }
}

/**
 * Get chat history for a session
 * 
 * @param string $sessionId Session ID
 * @param int $limit Maximum number of messages to return
 * @return array Array of messages
 */
function getChatHistory($sessionId, $limit = 50) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT sender, message, created_at 
            FROM chat_messages 
            WHERE session_id = :session_id 
            ORDER BY created_at ASC 
            LIMIT :limit
        ");
        
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting chat history: " . $e->getMessage());
        return [];
    }
}

/**
 * Log API interaction
 * 
 * @param string $sessionId Session ID
 * @param array $requestData Request data
 * @param array $responseData Response data
 * @param int $statusCode HTTP status code
 * @param string $errorMessage Error message if any
 * @return bool True if successful, false otherwise
 */
function logApiInteraction($sessionId, $requestData = null, $responseData = null, $statusCode = null, $errorMessage = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO api_logs (session_id, request_data, response_data, status_code, error_message) 
            VALUES (:session_id, :request_data, :response_data, :status_code, :error_message)
        ");
        
        return $stmt->execute([
            'session_id' => $sessionId,
            'request_data' => $requestData ? json_encode($requestData) : null,
            'response_data' => $responseData ? json_encode($responseData) : null,
            'status_code' => $statusCode,
            'error_message' => $errorMessage
        ]);
    } catch (Exception $e) {
        error_log("Error logging API interaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old sessions and messages
 * 
 * @param int $daysOld Number of days old to consider for cleanup
 * @return bool True if successful, false otherwise
 */
function cleanupOldData($daysOld = 30) {
    try {
        $pdo = getDatabaseConnection();
        
        // Delete old sessions (this will cascade to messages)
        $stmt = $pdo->prepare("
            DELETE FROM chat_sessions 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        
        $stmt->execute(['days' => $daysOld]);
        
        // Delete old API logs
        $stmt = $pdo->prepare("
            DELETE FROM api_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        
        $stmt->execute(['days' => $daysOld]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error cleaning up old data: " . $e->getMessage());
        return false;
    }
}
?> 