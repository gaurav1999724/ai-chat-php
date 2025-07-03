// Global variables
let recognition = null;
let isListening = false;
let currentSessionId = null;
let isGeneratingImage = false;

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
    
    // Format the message to preserve line breaks and formatting
    const formattedMessage = formatMessage(message);
    text.innerHTML = formattedMessage;

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

// Format message to preserve line breaks and basic formatting
function formatMessage(message) {
    if (!message) return '';
    
    // Escape HTML to prevent XSS
    let formatted = message
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    
    // Convert line breaks to <br> tags
    formatted = formatted.replace(/\n/g, '<br>');
    
    // Convert multiple spaces to non-breaking spaces to preserve indentation
    formatted = formatted.replace(/ {2,}/g, function(match) {
        return '&nbsp;'.repeat(match.length);
    });
    
    // Convert code blocks (text between backticks)
    formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
    
    // Convert bold text (text between **)
    formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    
    // Convert italic text (text between *)
    formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    
    // Convert bullet points (lines starting with - or *)
    formatted = formatted.replace(/^[-*]\s+/gm, '• ');
    
    // Convert numbered lists (lines starting with numbers)
    formatted = formatted.replace(/^(\d+)\.\s+/gm, '<strong>$1.</strong> ');
    
    // Convert URLs to clickable links
    formatted = formatted.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    
    return formatted;
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

// Generate image from text prompt
async function generateImage(prompt) {
    if (isGeneratingImage) {
        showNotification('Image generation already in progress', 'warning');
        return;
    }

    if (!prompt || prompt.trim() === '') {
        showNotification('Please provide a text prompt for image generation', 'error');
        return;
    }

    isGeneratingImage = true;
    showNotification('Generating image...', 'info');

    try {
        const response = await fetch('api/generate_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                text: prompt.trim(),
                width: 512,
                height: 512,
                steps: 1,
                session_id: currentSessionId
            })
        });

        if (!response.ok) {
            const responseText = await response.text();
            console.error('Image generation response:', responseText);
            
            try {
                const errorData = JSON.parse(responseText);
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            } catch (parseError) {
                throw new Error(`Server error (${response.status}): ${responseText.substring(0, 100)}...`);
            }
        }

        const responseText = await response.text();
        console.log('Image generation response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid JSON response from image generation server');
        }
        
        if (data.error) {
            throw new Error(data.error);
        }

        // Add the generated image to chat
        addImageToChat(data.image_data, data.prompt);
        showNotification('Image generated successfully!', 'success');

    } catch (error) {
        console.error('Error generating image:', error);
        addMessageToChat('ai', `Sorry, I encountered an error generating the image: ${error.message}`);
        showNotification('Error generating image', 'error');
    } finally {
        isGeneratingImage = false;
    }
}

// Add generated image to chat
function addImageToChat(imageData, prompt) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message ai-message';

    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    avatar.innerHTML = '<i class="fas fa-robot"></i>';

    const content = document.createElement('div');
    content.className = 'message-content';

    const text = document.createElement('div');
    text.className = 'message-text';
    text.innerHTML = `<strong>Generated Image:</strong><br>Prompt: "${prompt}"`;

    const imageContainer = document.createElement('div');
    imageContainer.className = 'generated-image-container';
    
    // Handle different response formats
    let imageUrl = null;
    if (imageData.generated_image) {
        imageUrl = imageData.generated_image;
    } else if (imageData.url) {
        imageUrl = imageData.url;
    } else if (imageData.image) {
        imageUrl = imageData.image;
    } else if (imageData.data && imageData.data.url) {
        imageUrl = imageData.data.url;
    }

    if (imageUrl) {
        console.log('Image URL extracted successfully:', imageUrl);
        const img = document.createElement('img');
        img.src = imageUrl;
        img.alt = prompt;
        img.className = 'generated-image';
        img.onload = () => {
            messageDiv.style.opacity = '1';
            console.log('Image loaded successfully');
        };
        img.onerror = () => {
            console.error('Error loading image from URL:', imageUrl);
            text.innerHTML += '<br><em>Error loading image</em>';
        };
        imageContainer.appendChild(img);
    } else {
        console.error('No image URL found in response data:', imageData);
        text.innerHTML += '<br><em>No image data received</em>';
    }

    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = getCurrentTime();

    content.appendChild(text);
    if (imageContainer.children.length > 0) {
        content.appendChild(imageContainer);
    }
    content.appendChild(time);
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Show image generation prompt modal
function showImagePrompt() {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'image-prompt-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-image"></i> Generate Image</h3>
                <button class="modal-close" onclick="closeImagePrompt()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <label for="imagePrompt">Describe the image you want to generate:</label>
                <textarea 
                    id="imagePrompt" 
                    placeholder="e.g., a beautiful sunset over mountains, a cute cat playing with yarn, a futuristic city skyline..."
                    rows="3"
                ></textarea>
                <div class="image-options">
                    <div class="option-group">
                        <label>Size:</label>
                        <select id="imageSize">
                            <option value="512x512">512x512 (Square)</option>
                            <option value="768x512">768x512 (Landscape)</option>
                            <option value="512x768">512x768 (Portrait)</option>
                        </select>
                    </div>
                    <div class="option-group">
                        <label>Quality:</label>
                        <select id="imageSteps">
                            <option value="1">Fast (1 step)</option>
                            <option value="10">Standard (10 steps)</option>
                            <option value="20">High (20 steps)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeImagePrompt()">Cancel</button>
                <button class="btn-primary" onclick="submitImagePrompt()">
                    <i class="fas fa-magic"></i> Generate Image
                </button>
            </div>
        </div>
    `;
    
    // Add modal styles
    const style = document.createElement('style');
    style.textContent = `
        .image-prompt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #666;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #f8f9fa;
            color: #333;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-body label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .modal-body textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .modal-body textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .image-options {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .option-group {
            flex: 1;
        }
        
        .option-group label {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .option-group select {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .modal-footer {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
            justify-content: flex-end;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .image-options {
                flex-direction: column;
            }
            
            .modal-footer {
                flex-direction: column;
            }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(modal);
    
    // Focus on textarea
    setTimeout(() => {
        document.getElementById('imagePrompt').focus();
    }, 100);
}

// Close image prompt modal
function closeImagePrompt() {
    const modal = document.querySelector('.image-prompt-modal');
    if (modal) {
        modal.remove();
    }
}

// Submit image generation prompt
function submitImagePrompt() {
    const prompt = document.getElementById('imagePrompt').value.trim();
    const sizeSelect = document.getElementById('imageSize').value;
    const stepsSelect = document.getElementById('imageSteps').value;
    
    if (!prompt) {
        showNotification('Please enter a description for the image', 'error');
        return;
    }
    
    // Parse size
    const [width, height] = sizeSelect.split('x').map(Number);
    
    // Close modal
    closeImagePrompt();
    
    // Generate image with custom parameters
    generateImageWithParams(prompt, width, height, parseInt(stepsSelect));
}

// Generate image with custom parameters
async function generateImageWithParams(prompt, width, height, steps) {
    if (isGeneratingImage) {
        showNotification('Image generation already in progress', 'warning');
        return;
    }

    isGeneratingImage = true;
    showNotification('Generating image...', 'info');

    try {
        const response = await fetch('api/generate_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                text: prompt,
                width: width,
                height: height,
                steps: steps,
                session_id: currentSessionId
            })
        });

        if (!response.ok) {
            const responseText = await response.text();
            console.error('Image generation response:', responseText);
            
            try {
                const errorData = JSON.parse(responseText);
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            } catch (parseError) {
                throw new Error(`Server error (${response.status}): ${responseText.substring(0, 100)}...`);
            }
        }

        const responseText = await response.text();
        console.log('Image generation response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid JSON response from image generation server');
        }
        
        if (data.error) {
            throw new Error(data.error);
        }

        // Add the generated image to chat
        addImageToChat(data.image_data, data.prompt);
        showNotification('Image generated successfully!', 'success');

    } catch (error) {
        console.error('Error generating image:', error);
        addMessageToChat('ai', `Sorry, I encountered an error generating the image: ${error.message}`);
        showNotification('Error generating image', 'error');
    } finally {
        isGeneratingImage = false;
    }
}

// Export functions for global access
window.sendMessage = sendMessage;
window.toggleMicrophone = toggleMicrophone;
window.stopMicrophone = stopMicrophone;
window.clearChat = clearChat;
window.handleKeyPress = handleKeyPress;
window.generateImage = generateImage;
window.showImagePrompt = showImagePrompt;
window.closeImagePrompt = closeImagePrompt;
window.submitImagePrompt = submitImagePrompt; 