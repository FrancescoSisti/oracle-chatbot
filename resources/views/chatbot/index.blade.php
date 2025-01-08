<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBot AI - Chat Interface</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-800">ChatBot AI</a>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('training.index') }}" class="text-gray-600 hover:text-gray-900">Training</a>
                        <span class="text-gray-600">|</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-900">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Chat Header -->
            <div class="bg-blue-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h2 class="text-lg font-medium text-white">Assistente Virtuale</h2>
                            <p class="text-blue-100 text-sm">Online</p>
                        </div>
                    </div>
                    <div class="flex items-center text-blue-100 text-sm">
                        <span id="typing-indicator" class="hidden">
                            Sta scrivendo...
                        </span>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="chat-messages" class="h-[calc(100vh-300px)] overflow-y-auto p-6 space-y-4">
                <!-- Messaggio di benvenuto -->
                <div class="flex justify-start">
                    <div class="max-w-sm bg-gray-100 rounded-lg p-4">
                        <p class="text-gray-800">Ciao! Sono il tuo assistente virtuale. Come posso aiutarti oggi?</p>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                <form id="chat-form" class="flex space-x-4">
                    <div class="flex-1">
                        <input type="text"
                               id="message-input"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Scrivi un messaggio..."
                               autocomplete="off">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Invia
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const chatMessages = document.getElementById('chat-messages');
            const typingIndicator = document.getElementById('typing-indicator');

            function addMessage(message, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isUser ? 'justify-end' : 'justify-start'}`;

                messageDiv.innerHTML = `
                    <div class="max-w-sm ${isUser ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800'} rounded-lg p-4">
                        <p>${message}</p>
                    </div>
                `;

                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function showTypingIndicator() {
                typingIndicator.classList.remove('hidden');
            }

            function hideTypingIndicator() {
                typingIndicator.classList.add('hidden');
            }

            async function sendMessage(message) {
                try {
                    const response = await fetch('/chatbot/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.error('Errore:', error);
                    throw error;
                }
            }

            // Gestione dell'invio del messaggio
            function handleSubmit(event) {
                event.preventDefault();

                const message = messageInput.value.trim();
                if (!message) return;

                // Aggiungi il messaggio dell'utente
                addMessage(message, true);
                messageInput.value = '';

                // Mostra l'indicatore di digitazione
                showTypingIndicator();

                // Invia il messaggio al server
                sendMessage(message)
                    .then(data => {
                        setTimeout(() => {
                            hideTypingIndicator();
                            addMessage(data.message);
                        }, 500);
                    })
                    .catch(error => {
                        hideTypingIndicator();
                        addMessage('Mi dispiace, si è verificato un errore nella comunicazione.');
                    });
            }

            // Event Listeners
            chatForm.addEventListener('submit', handleSubmit);

            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSubmit(new Event('submit'));
                }
            });

            // Focus sull'input all'avvio
            messageInput.focus();
        });
    </script>
</body>
</html>
