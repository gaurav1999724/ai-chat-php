-- AI Chat Database Schema
-- This file contains the complete database structure for the AI Chat application

-- Create database
CREATE DATABASE IF NOT EXISTS `ai_chat` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ai_chat`;

-- Chat Sessions Table
-- Stores information about chat sessions
CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(255) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages Table
-- Stores all chat messages with sender information
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(255) NOT NULL,
    `sender` ENUM('user', 'ai') NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_sender` (`sender`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Logs Table
-- Stores API interaction logs for debugging and monitoring
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(255) NOT NULL,
    `request_data` TEXT,
    `response_data` TEXT,
    `status_code` INT,
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status_code` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
-- Insert a sample session
INSERT INTO `chat_sessions` (`session_id`) VALUES 
('sample_session_1234567890') 
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Insert sample messages
INSERT INTO `chat_messages` (`session_id`, `sender`, `message`) VALUES 
('sample_session_1234567890', 'user', 'Hello, how are you?'),
('sample_session_1234567890', 'ai', 'Hello! I\'m doing well, thank you for asking. How can I help you today?'),
('sample_session_1234567890', 'user', 'Can you tell me about artificial intelligence?'),
('sample_session_1234567890', 'ai', 'Artificial Intelligence (AI) is a branch of computer science that aims to create systems capable of performing tasks that typically require human intelligence. These tasks include learning, reasoning, problem-solving, perception, and language understanding. AI has applications in various fields including healthcare, finance, transportation, and entertainment.');

-- Create views for easier data access
-- View for recent chat sessions
CREATE OR REPLACE VIEW `recent_sessions` AS
SELECT 
    cs.session_id,
    cs.created_at,
    cs.updated_at,
    COUNT(cm.id) as message_count,
    MAX(cm.created_at) as last_message_time
FROM `chat_sessions` cs
LEFT JOIN `chat_messages` cm ON cs.session_id = cm.session_id
GROUP BY cs.session_id, cs.created_at, cs.updated_at
ORDER BY cs.updated_at DESC;

-- View for session statistics
CREATE OR REPLACE VIEW `session_stats` AS
SELECT 
    session_id,
    COUNT(*) as total_messages,
    SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages,
    SUM(CASE WHEN sender = 'ai' THEN 1 ELSE 0 END) as ai_messages,
    MIN(created_at) as first_message,
    MAX(created_at) as last_message
FROM `chat_messages`
GROUP BY session_id;

-- Create stored procedures for common operations

-- Procedure to get chat history for a session
DELIMITER //
CREATE PROCEDURE `GetChatHistory`(IN sessionId VARCHAR(255), IN messageLimit INT)
BEGIN
    SELECT 
        sender,
        message,
        created_at,
        DATE_FORMAT(created_at, '%H:%i') as time_formatted
    FROM `chat_messages`
    WHERE session_id = sessionId
    ORDER BY created_at ASC
    LIMIT messageLimit;
END //
DELIMITER ;

-- Procedure to clean up old data
DELIMITER //
CREATE PROCEDURE `CleanupOldData`(IN daysOld INT)
BEGIN
    DELETE FROM `chat_sessions` 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL daysOld DAY);
    
    DELETE FROM `api_logs` 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL daysOld DAY);
END //
DELIMITER ;

-- Procedure to get session statistics
DELIMITER //
CREATE PROCEDURE `GetSessionStats`(IN sessionId VARCHAR(255))
BEGIN
    SELECT 
        COUNT(*) as total_messages,
        SUM(CASE WHEN sender = 'user' THEN 1 ELSE 0 END) as user_messages,
        SUM(CASE WHEN sender = 'ai' THEN 1 ELSE 0 END) as ai_messages,
        MIN(created_at) as first_message,
        MAX(created_at) as last_message,
        TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as duration_minutes
    FROM `chat_messages`
    WHERE session_id = sessionId;
END //
DELIMITER ;

-- Create indexes for better performance
CREATE INDEX `idx_messages_session_sender` ON `chat_messages` (`session_id`, `sender`);
CREATE INDEX `idx_messages_created_desc` ON `chat_messages` (`created_at` DESC);
CREATE INDEX `idx_sessions_updated_desc` ON `chat_sessions` (`updated_at` DESC);

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `ai_chat`.* TO 'your_user'@'localhost';

-- Show table information
SHOW TABLES;

-- Show sample data
SELECT 'Chat Sessions:' as info;
SELECT * FROM `chat_sessions` LIMIT 5;

SELECT 'Chat Messages:' as info;
SELECT * FROM `chat_messages` LIMIT 10;

SELECT 'Recent Sessions View:' as info;
SELECT * FROM `recent_sessions` LIMIT 5; 