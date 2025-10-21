document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const chatIcon = document.getElementById('chatIcon');
    const chatbotWidget = document.getElementById('chatbotWidget');
    const closeBtn = document.getElementById('closeBtn');
    const chatMessages = document.getElementById('chatMessages');
    const userInput = document.getElementById('userInput');
    const sendBtn = document.getElementById('sendBtn');
    
    // Toggle chatbot visibility when clicking the chat icon
    chatIcon.addEventListener('click', function() {
        chatbotWidget.classList.toggle('active');
        
        // Remove notification dot when chat is opened
        const notificationDot = document.querySelector('.notification-dot');
        if (notificationDot) {
            notificationDot.style.display = 'none';
        }
    });
    
    // Close chatbot when clicking the close button
    closeBtn.addEventListener('click', function() {
        chatbotWidget.classList.remove('active');
    });
    
    // Send message when clicking the send button
    sendBtn.addEventListener('click', sendMessage);
    
    // Send message when pressing Enter key
    userInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Function to send and display messages
    function sendMessage() {
        const message = userInput.value.trim();
        
        if (message) {
            // Add user message to chat
            addMessageToChat(message, 'user');
            
            // Clear input field
            userInput.value = '';
            
            // Simulate bot typing
            setTimeout(() => {
                getBotResponse(message);
            }, 500);
        }
    }
    
    // Function to add messages to the chat
    function addMessageToChat(message, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        
        if (sender === 'user') {
            messageDiv.classList.add('user-message');
            messageDiv.innerHTML = `
                <i class="fas fa-user"></i>
                <p>${message}</p>
            `;
        } else {
            messageDiv.classList.add('bot-message');
            messageDiv.innerHTML = `
                <i class="fas fa-robot"></i>
                <p>${message}</p>
            `;
        }
        
        chatMessages.appendChild(messageDiv);
        
        // Scroll to the bottom of the chat
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Function to get bot response
    function getBotResponse(message) {
        // In a real implementation, this would call your Python backend
        // For now, we'll use a simple simulation
        fetch('/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            addMessageToChat(data.response, 'bot');
        })
        .catch(error => {
            console.error('Error:', error);
            addMessageToChat('Lo siento, hubo un error al procesar tu mensaje.', 'bot');
        });
    }
    
    // Initially hide the chatbot widget
    chatbotWidget.classList.remove('active');
});