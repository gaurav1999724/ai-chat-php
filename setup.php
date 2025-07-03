<?php
/**
 * AI Chat Setup Script
 * 
 * This script helps you set up the AI Chat application by:
 * 1. Checking system requirements
 * 2. Creating necessary directories
 * 3. Testing database connection
 * 4. Initializing database tables
 */

// Enable error reporting for setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>AI Chat Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .step { margin-bottom: 30px; padding: 20px; border-left: 4px solid #667eea; background: #f8f9fa; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        h1 { color: #667eea; text-align: center; }
        h2 { color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #5a6fd8; }
        code { background: #f1f1f1; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🤖 AI Chat Setup</h1>";

// Step 1: Check PHP Version
echo "<div class='step'>";
echo "<h2>Step 1: PHP Version Check</h2>";
$phpVersion = phpversion();
$requiredVersion = '7.4.0';
if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo "<p class='success'>✅ PHP version $phpVersion is compatible (required: $requiredVersion+)</p>";
} else {
    echo "<p class='error'>❌ PHP version $phpVersion is too old (required: $requiredVersion+)</p>";
    echo "<p>Please upgrade your PHP version to continue.</p>";
}
echo "</div>";

// Step 2: Check Required Extensions
echo "<div class='step'>";
echo "<h2>Step 2: Required Extensions</h2>";
$requiredExtensions = ['curl', 'pdo', 'pdo_mysql', 'json'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ $ext extension is loaded</p>";
    } else {
        echo "<p class='error'>❌ $ext extension is missing</p>";
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "<p class='warning'>Please install the missing extensions: " . implode(', ', $missingExtensions) . "</p>";
}
echo "</div>";

// Step 3: Create Directories
echo "<div class='step'>";
echo "<h2>Step 3: Directory Setup</h2>";
$directories = ['logs', 'logs/api'];
$createdDirs = [];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p class='success'>✅ Created directory: $dir</p>";
            $createdDirs[] = $dir;
        } else {
            echo "<p class='error'>❌ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p class='success'>✅ Directory exists: $dir</p>";
    }
}

// Create log file
$logFile = 'logs/api_interactions.log';
if (!file_exists($logFile)) {
    if (touch($logFile) && chmod($logFile, 0644)) {
        echo "<p class='success'>✅ Created log file: $logFile</p>";
    } else {
        echo "<p class='error'>❌ Failed to create log file: $logFile</p>";
    }
} else {
    echo "<p class='success'>✅ Log file exists: $logFile</p>";
}
echo "</div>";

// Step 4: Database Configuration
echo "<div class='step'>";
echo "<h2>Step 4: Database Configuration</h2>";

// Check if database config exists
if (file_exists('config/database.php')) {
    echo "<p class='success'>✅ Database configuration file exists</p>";
    
    // Try to include and test connection
    try {
        require_once 'config/database.php';
        
        // Test database connection
        $pdo = getDatabaseConnection();
        echo "<p class='success'>✅ Database connection successful</p>";
        
        // Test table creation
        if (initializeDatabase()) {
            echo "<p class='success'>✅ Database tables initialized successfully</p>";
        } else {
            echo "<p class='error'>❌ Failed to initialize database tables</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='info'>Please check your database credentials in <code>config/database.php</code></p>";
    }
} else {
    echo "<p class='error'>❌ Database configuration file not found</p>";
    echo "<p class='info'>Please create <code>config/database.php</code> with your database settings</p>";
}
echo "</div>";

// Step 5: API Configuration
echo "<div class='step'>";
echo "<h2>Step 5: API Configuration</h2>";
if (file_exists('api/chatgpt.php')) {
    echo "<p class='success'>✅ ChatGPT API handler exists</p>";
    echo "<p class='info'>The application is pre-configured with a RapidAPI key</p>";
    echo "<p class='warning'>⚠️ For production use, consider using your own API key</p>";
} else {
    echo "<p class='error'>❌ ChatGPT API handler not found</p>";
}
echo "</div>";

// Step 6: File Permissions
echo "<div class='step'>";
echo "<h2>Step 6: File Permissions</h2>";
$writableFiles = ['logs/', 'logs/api_interactions.log'];
foreach ($writableFiles as $file) {
    if (is_writable($file)) {
        echo "<p class='success'>✅ $file is writable</p>";
    } else {
        echo "<p class='error'>❌ $file is not writable</p>";
        echo "<p class='info'>Run: <code>chmod 755 logs/ && chmod 644 logs/api_interactions.log</code></p>";
    }
}
echo "</div>";

// Step 7: Browser Requirements
echo "<div class='step'>";
echo "<h2>Step 7: Browser Requirements</h2>";
echo "<p class='info'>For the best experience, use a modern browser with Web Speech API support:</p>";
echo "<ul>";
echo "<li>Chrome 66+ (recommended)</li>";
echo "<li>Firefox 60+</li>";
echo "<li>Safari 11+</li>";
echo "<li>Edge 79+</li>";
echo "</ul>";
echo "<p class='warning'>⚠️ Speech-to-text requires HTTPS in production</p>";
echo "</div>";

// Final Instructions
echo "<div class='step success'>";
echo "<h2>🎉 Setup Complete!</h2>";
echo "<p>Your AI Chat application is ready to use!</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Import the database schema: <code>database/ai_chat.sql</code></li>";
echo "<li>Access the application: <a href='index.php' class='btn'>Open Chat</a></li>";
echo "<li>Test the speech-to-text functionality</li>";
echo "<li>Customize the UI and functionality as needed</li>";
echo "</ol>";
echo "</div>";

// Troubleshooting Section
echo "<div class='step info'>";
echo "<h2>🔧 Troubleshooting</h2>";
echo "<p><strong>Common Issues:</strong></p>";
echo "<ul>";
echo "<li><strong>Speech not working:</strong> Use HTTPS or localhost, allow microphone permissions</li>";
echo "<li><strong>Database errors:</strong> Check MySQL service is running and credentials are correct</li>";
echo "<li><strong>API errors:</strong> Verify RapidAPI key and subscription status</li>";
echo "<li><strong>Permission errors:</strong> Ensure logs directory is writable</li>";
echo "</ul>";
echo "<p>For more help, check the <a href='README.md'>README.md</a> file.</p>";
echo "</div>";

echo "</div></body></html>";
?> 