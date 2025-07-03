// Global variables
let recognition = null;
let isListening = false;
let currentSessionId = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeSpeechRecognition();
    initializeAutoResize();
    generateSessionId();
});

// Generate unique session ID
function generateSessionId() {
    currentSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Initialize speech recognition
function initializeSpeechRecognition() {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onstart = function() {
            console.log('Speech recognition started');
            isListening = true;
            updateMicrophoneUI(true);
        };

        recognition.onresult = function(event) {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            // Update input field with interim results
            if (interimTranscript) {
                document.getElementById('messageInput').value = finalTranscript + interimTranscript;
            }

            // If we have final results, send the message
            if (finalTranscript) {
                document.getElementById('messageInput').value = finalTranscript;
                setTimeout(() => {
                    sendMessage();
                }, 500);
            }
        };

        recognition.onerror = function(event) {
            console.error('Speech recognition error:', event.error);
            isListening = false;
            updateMicrophoneUI(false);
            showNotification('Speech recognition error: ' + event.error, 'error');
        };

        recognition.onend = function() {
            console.log('Speech recognition ended');
            isListening = false;
            updateMicrophoneUI(false);
        };
    } else {
        console.warn('Speech recognition not supported');
        document.getElementById('micBtn').style.display = 'none';
    }
}

// Initialize auto-resize for textarea
function initializeAutoResize() {
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

// Toggle microphone
function toggleMicrophone() {
    if (!recognition) {
        showNotification('Speech recognition not supported in this browser', 'error');
        return;
    }

    if (isListening) {
        stopMicrophone();
    } else {
        startMicrophone();
    }
}

// Start microphone
function startMicrophone() {
    try {
        recognition.start();
    } catch (error) {
        console.error('Error starting speech recognition:', error);
        showNotification('Error starting microphone', 'error');
    }
}

// Stop microphone
function stopMicrophone() {
    if (recognition && isListening) {
        recognition.stop();
    }
}

// Update microphone UI
function updateMicrophoneUI(listening) {
    const micBtn = document.getElementById('micBtn');
    const micStatus = document.getElementById('micStatus');
    const textInputContainer = document.querySelector('.text-input-container');

    if (listening) {
        micBtn.classList.add('listening');
        micStatus.style.display = 'flex';
        textInputContainer.style.display = 'none';
    } else {
        micBtn.classList.remove('listening');
        micStatus.style.display = 'none';
        textInputContainer.style.display = 'flex';
    }
}

// Handle key press in textarea
function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

// Send message
async function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();

    if (!message) {
        return;
    }

    // Disable send button
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;

    // Add user message to chat
    addMessageToChat('user', message);

    // Clear input
    messageInput.value = '';
    messageInput.style.height = 'auto';

    // Show loading indicator
    showLoadingIndicator(true);

    try {
        // Send to API
        const response = await sendToAPI(message);
        
        // Add AI response to chat
        addMessageToChat('ai', response);
        
        // Save to database
        saveToDatabase('user', message);
        saveToDatabase('ai', response);

    } catch (error) {
        console.error('Error sending message:', error);
        addMessageToChat('ai', 'Sorry, I encountered an error. Please try again.');
        showNotification('Error sending message', 'error');
    } finally {
        // Hide loading indicator and re-enable send button
        showLoadingIndicator(false);
        sendBtn.disabled = false;
    }
}

// Send message to API
async function sendToAPI(message) {
    const response = await fetch('api/chatgpt.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            session_id: currentSessionId
        })
    });

    if (!response.ok) {
        // Try to get the response text to see what the server is actually returning
        const responseText = await response.text();
        console.error('Server response:', responseText);
        
        // Try to parse as JSON if possible
        try {
            const errorData = JSON.parse(responseText);
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        } catch (parseError) {
            // If it's not JSON, it's likely HTML error output
            throw new Error(`Server error (${response.status}): ${responseText.substring(0, 100)}...`);
        }
    }

    const responseText = await response.text();
    console.log('Raw response:', responseText);
    
    let data;
    try {
        data = JSON.parse(responseText);
    } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Response text:', responseText);
        throw new Error('Invalid JSON response from server');
    }
    
    if (data.error) {
        throw new Error(data.error);
    }

    return data.response;
}

// Add message to chat UI
function addMessageToChat(sender, message) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    
    if (sender === 'user') {
        avatar.innerHTML = '<i class="fas fa-user"></i>';
    } else {
        avatar.innerHTML = '<i class="fas fa-robot"></i>';
    }

    const content = document.createElement('div');
    content.className = 'message-content';

    const text = document.createElement('div');
    text.className = 'message-text';
    text.textContent = message;

    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = getCurrentTime();

    content.appendChild(text);
    content.appendChild(time);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);

    chatMessages.appendChild(messageDiv);

    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Save message to database
async function saveToDatabase(sender, message) {
    try {
        await fetch('api/save_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: currentSessionId,
                sender: sender,
                message: message
            })
        });
    } catch (error) {
        console.error('Error saving to database:', error);
    }
}

// Show/hide loading indicator
function showLoadingIndicator(show) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (show) {
        loadingIndicator.style.display = 'flex';
        loadingIndicator.classList.add('slide-up');
    } else {
        loadingIndicator.style.display = 'none';
    }
}

// Get current time
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Clear chat
function clearChat() {
    if (confirm('Are you sure you want to clear the chat?')) {
        const chatMessages = document.getElementById('chatMessages');
        
        // Keep only the welcome message
        const welcomeMessage = chatMessages.querySelector('.ai-message');
        chatMessages.innerHTML = '';
        chatMessages.appendChild(welcomeMessage);
        
        // Generate new session ID
        generateSessionId();
        
        showNotification('Chat cleared successfully', 'success');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
    `;
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#212529';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    // Add to body
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export functions for global access
window.sendMessage = sendMessage;
window.toggleMicrophone = toggleMicrophone;
window.stopMicrophone = stopMicrophone;
window.clearChat = clearChat;
window.handleKeyPress = handleKeyPress; 