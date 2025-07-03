<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Assistant</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-robot"></i>
                    <span>AI Chat Assistant</span>
                </div>
                <div class="header-actions">
                    <button class="clear-chat-btn" onclick="clearChat()">
                        <i class="fas fa-trash"></i>
                        Clear Chat
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Messages Area -->
        <div class="chat-messages" id="chatMessages">
            <!-- Welcome Message -->
            <div class="message ai-message">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">
                        Hello! I'm your AI assistant. You can type your message or use the microphone to speak. How can I help you today?
                    </div>
                    <div class="message-time">Just now</div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="chat-input-container">
            <div class="input-wrapper">
                <div class="text-input-container">
                    <textarea 
                        id="messageInput" 
                        placeholder="Type your message here..." 
                        rows="1"
                        onkeydown="handleKeyPress(event)"
                    ></textarea>
                    <div class="input-actions">
                        <button class="mic-btn" id="micBtn" onclick="toggleMicrophone()">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Microphone Status -->
                <div class="mic-status" id="micStatus" style="display: none;">
                    <div class="mic-animation">
                        <div class="pulse-ring"></div>
                        <div class="pulse-ring"></div>
                        <div class="pulse-ring"></div>
                    </div>
                    <span>Listening...</span>
                    <button class="stop-mic-btn" onclick="stopMicrophone()">
                        <i class="fas fa-stop"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="loading-indicator" id="loadingIndicator" style="display: none;">
            <div class="typing-dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            <span>AI is thinking...</span>
        </div>
    </div>

    <script src="assets/js/chat.js"></script>
</body>
</html> 