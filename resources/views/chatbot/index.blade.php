<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oracle AI - Il tuo assistente personale</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-animation {
            animation: slideIn 0.3s ease-out forwards;
        }

        /* Nascondi la scrollbar ma mantieni la funzionalità */
        #chat-messages {
            scrollbar-width: thin;
            scrollbar-color: rgba(107, 114, 128, 0.3) transparent;
            -webkit-overflow-scrolling: touch;
        }

        #chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        #chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        #chat-messages::-webkit-scrollbar-thumb {
            background-color: rgba(107, 114, 128, 0.3);
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden">
    <!-- Navigation Fixed -->
    <nav class="bg-white shadow-lg fixed top-0 left-0 right-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-800">Oracle AI</span>
                    </a>
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

    <!-- Main Chat Container -->
    <div class="fixed inset-0 pt-16 pb-4 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl h-full bg-white rounded-xl shadow-xl overflow-hidden border border-gray-100 flex flex-col">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-10 w-10 text-white opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h2 class="text-xl font-semibold text-white">Oracle</h2>
                            <div class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                                <p class="text-purple-100 text-sm">Online</p>
                            </div>
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
            <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
                <!-- Messaggio di benvenuto -->
                <div class="flex justify-start">
                    <div class="max-w-sm bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-gray-800">
                            @auth
                                Ciao {{ Auth::user()->name }}! Sono Oracle, come posso aiutarti oggi?
                            @else
                                Ciao! Sono Oracle, come posso aiutarti oggi?
                            @endauth
                        </p>
                    </div>
                </div>
                <!-- Legenda per la correzione -->
                <div class="flex justify-center">
                    <div class="bg-purple-50 rounded-xl p-4 shadow-sm border border-purple-100 max-w-lg">
                        <div class="flex items-center mb-2">
                            <svg class="h-5 w-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium text-purple-800">Come aiutarmi a migliorare:</span>
                        </div>
                        <div class="text-sm text-purple-700 space-y-4">
                            <!-- Sistema di feedback rapido -->
                            <div>
                                <span class="font-medium">Feedback rapido:</span>
                                <p class="mt-1">Ogni messaggio avrà dei pulsanti di feedback:</p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <button class="text-gray-500 hover:text-green-500">👍</button>
                                    <button class="text-gray-500 hover:text-red-500">👎</button>
                                    <button class="text-xs bg-purple-100 px-2 py-1 rounded hover:bg-purple-200">Correggi</button>
                                    <button onclick="showTeachMenu(this)" class="text-xs bg-green-100 px-2 py-1 rounded hover:bg-green-200 text-green-600 transition-colors">Insegna</button>
                                </div>
                            </div>

                            <!-- Correzione con categorie -->
                            <div>
                                <span class="font-medium">Correzione dettagliata:</span>
                                <p>Usa <code class="bg-purple-100 px-1 rounded">correggi: categoria1, categoria2</code> per specificare più significati.</p>
                            </div>

                            <!-- Categorie disponibili -->
                            <div>
                                <span class="font-medium">Categorie disponibili:</span>
                                <div class="grid grid-cols-2 gap-2 mt-1">
                                    <div>
                                        <span class="font-medium text-purple-800">Interazioni base:</span>
                                        <ul class="mt-1 space-y-1">
                                            <li><code class="bg-purple-100 px-1 rounded">saluto</code> - saluti generici</li>
                                            <li><code class="bg-purple-100 px-1 rounded">congedo</code> - saluti di addio</li>
                                            <li><code class="bg-purple-100 px-1 rounded">ringraziamento</code> - ringraziamenti</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <span class="font-medium text-purple-800">Richieste:</span>
                                        <ul class="mt-1 space-y-1">
                                            <li><code class="bg-purple-100 px-1 rounded">richiesta_info</code> - domande generiche</li>
                                            <li><code class="bg-purple-100 px-1 rounded">richiesta_aiuto</code> - richieste di assistenza</li>
                                            <li><code class="bg-purple-100 px-1 rounded">richiesta_stato</code> - domande sullo stato/benessere</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <span class="font-medium text-purple-800">Conversazione:</span>
                                        <ul class="mt-1 space-y-1">
                                            <li><code class="bg-purple-100 px-1 rounded">smalltalk</code> - chiacchiere informali</li>
                                            <li><code class="bg-purple-100 px-1 rounded">opinione</code> - richieste di pareri</li>
                                            <li><code class="bg-purple-100 px-1 rounded">emozione</code> - espressioni emotive</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <span class="font-medium text-purple-800">Funzionalità:</span>
                                        <ul class="mt-1 space-y-1">
                                            <li><code class="bg-purple-100 px-1 rounded">capacita</code> - domande sulle mie capacità</li>
                                            <li><code class="bg-purple-100 px-1 rounded">chiarimento</code> - richieste di chiarimento</li>
                                            <li><code class="bg-purple-100 px-1 rounded">feedback</code> - dare feedback</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Esempio -->
                            <div>
                                <span class="font-medium">Esempi:</span>
                                <ul class="mt-1 space-y-1">
                                    <li>"come va?" → <code class="bg-purple-100 px-1 rounded">correggi: saluto, richiesta_stato</code></li>
                                    <li>"mi piace parlare con te" → <code class="bg-purple-100 px-1 rounded">correggi: smalltalk, emozione, feedback</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 px-6 py-4 bg-white flex-shrink-0">
                <form id="chat-form" class="flex space-x-4">
                    <div class="flex-1">
                        <input type="text"
                               id="message-input"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm"
                               placeholder="Scrivi un messaggio..."
                               autocomplete="off">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-sm transition-all duration-200">
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
                messageDiv.className = `flex ${isUser ? 'justify-end' : 'justify-start'} message-animation`;

                messageDiv.innerHTML = `
                    <div class="max-w-sm ${isUser ? 'bg-purple-600 text-white shadow-md' : 'bg-white text-gray-800 shadow-sm border border-gray-100'} rounded-xl p-4 ${isUser ? 'rounded-br-sm' : 'rounded-bl-sm'}">
                        <p>${message}</p>
                        ${!isUser ? `
                            <div class="flex items-center space-x-2 mt-2 text-sm">
                                <button onclick="handleFeedback(this, 'positive')" class="text-gray-400 hover:text-green-500 transition-colors">👍</button>
                                <button onclick="handleFeedback(this, 'negative')" class="text-gray-400 hover:text-red-500 transition-colors">👎</button>
                                <button onclick="showCorrectionMenu(this)" class="text-xs bg-gray-100 px-2 py-1 rounded hover:bg-gray-200 text-gray-600 transition-colors">Correggi</button>
                                <button onclick="showTeachMenu(this)" class="text-xs bg-green-100 px-2 py-1 rounded hover:bg-green-200 text-green-600 transition-colors">Insegna</button>
                            </div>
                            <div class="correction-menu hidden mt-2 bg-white rounded-lg shadow-lg border border-gray-200 p-4">
                                <div class="grid grid-cols-2 gap-2">
                                    <!-- Interazioni base -->
                                    <div>
                                        <span class="text-xs font-medium text-purple-800 block mb-1">Interazioni base</span>
                                        <div class="space-y-1">
                                            <button onclick="addCategory(this, 'saluto')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Saluto</button>
                                            <button onclick="addCategory(this, 'congedo')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Congedo</button>
                                            <button onclick="addCategory(this, 'ringraziamento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Ringraziamento</button>
                                            <button onclick="addCategory(this, 'scuse')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Scuse</button>
                                        </div>
                                    </div>
                                    <!-- Richieste -->
                                    <div>
                                        <span class="text-xs font-medium text-purple-800 block mb-1">Richieste</span>
                                        <div class="space-y-1">
                                            <button onclick="addCategory(this, 'richiesta_info')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Informazioni</button>
                                            <button onclick="addCategory(this, 'richiesta_aiuto')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Aiuto</button>
                                            <button onclick="addCategory(this, 'richiesta_stato')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Stato/Benessere</button>
                                            <button onclick="addCategory(this, 'richiesta_conferma')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Conferma</button>
                                        </div>
                                    </div>
                                    <!-- Conversazione -->
                                    <div>
                                        <span class="text-xs font-medium text-purple-800 block mb-1">Conversazione</span>
                                        <div class="space-y-1">
                                            <button onclick="addCategory(this, 'smalltalk')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Chiacchiere</button>
                                            <button onclick="addCategory(this, 'opinione')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Opinione</button>
                                            <button onclick="addCategory(this, 'emozione')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Emozione</button>
                                            <button onclick="addCategory(this, 'apprezzamento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Apprezzamento</button>
                                        </div>
                                    </div>
                                    <!-- Funzionalità -->
                                    <div>
                                        <span class="text-xs font-medium text-purple-800 block mb-1">Funzionalità</span>
                                        <div class="space-y-1">
                                            <button onclick="addCategory(this, 'capacita')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Capacità</button>
                                            <button onclick="addCategory(this, 'chiarimento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Chiarimento</button>
                                            <button onclick="addCategory(this, 'feedback')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Feedback</button>
                                            <button onclick="addCategory(this, 'suggerimento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-purple-50 text-gray-700">Suggerimento</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="text-xs text-gray-500 selected-categories"></div>
                                    <div class="space-x-2">
                                        <button onclick="cancelCorrection(this)" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">Annulla</button>
                                        <button onclick="submitCorrection(this)" class="text-xs px-3 py-1 bg-purple-600 hover:bg-purple-700 rounded text-white">Conferma</button>
                                    </div>
                                </div>
                            </div>
                            <div class="teach-menu hidden mt-2 bg-white rounded-lg shadow-lg border border-gray-200 p-4">
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-gray-700 block mb-1">La tua espressione:</label>
                                        <input type="text" class="teach-expression w-full text-sm px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Scrivi la tua versione...">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-xs font-medium text-gray-700 block mb-1">Scegli le categorie:</span>
                                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                                <!-- Interazioni base -->
                                                <div class="space-y-1">
                                                    <button onclick="addTeachCategory(this, 'saluto')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Saluto</button>
                                                    <button onclick="addTeachCategory(this, 'congedo')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Congedo</button>
                                                    <button onclick="addTeachCategory(this, 'ringraziamento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Ringraziamento</button>
                                                    <button onclick="addTeachCategory(this, 'scuse')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Scuse</button>
                                                </div>
                                                <!-- Richieste -->
                                                <div class="space-y-1">
                                                    <button onclick="addTeachCategory(this, 'richiesta_info')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Informazioni</button>
                                                    <button onclick="addTeachCategory(this, 'richiesta_aiuto')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Aiuto</button>
                                                    <button onclick="addTeachCategory(this, 'richiesta_stato')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Stato/Benessere</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-700 block mb-1">Altre categorie:</span>
                                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                                <!-- Conversazione -->
                                                <div class="space-y-1">
                                                    <button onclick="addTeachCategory(this, 'smalltalk')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Chiacchiere</button>
                                                    <button onclick="addTeachCategory(this, 'opinione')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Opinione</button>
                                                    <button onclick="addTeachCategory(this, 'emozione')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Emozione</button>
                                                </div>
                                                <!-- Funzionalità -->
                                                <div class="space-y-1">
                                                    <button onclick="addTeachCategory(this, 'capacita')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Capacità</button>
                                                    <button onclick="addTeachCategory(this, 'chiarimento')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Chiarimento</button>
                                                    <button onclick="addTeachCategory(this, 'feedback')" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-green-50 text-gray-700">Feedback</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 selected-teach-categories"></div>
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="cancelTeach(this)" class="text-xs px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-gray-600">Annulla</button>
                                        <button onclick="submitTeach(this)" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-white">Insegna</button>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;

                chatMessages.appendChild(messageDiv);

                // Scroll to bottom with smooth animation
                requestAnimationFrame(() => {
                    chatMessages.scrollTo({
                        top: chatMessages.scrollHeight,
                        behavior: 'smooth'
                    });
                });
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
                            addMessage(data.message || data.response);
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

            // Gestione del feedback
            window.handleFeedback = function(button, type) {
                const buttons = button.parentElement.querySelectorAll('button');
                buttons.forEach(b => b.classList.remove('text-green-500', 'text-red-500'));

                if (type === 'positive') {
                    button.classList.add('text-green-500');
                } else {
                    button.classList.add('text-red-500');
                }

                // Ottieni il messaggio dal bottone
                const message = button.closest('.max-w-sm').querySelector('p').textContent;
                // Invia il feedback al server
                sendFeedback(type, message);
            };

            // Gestione del menu di correzione
            let selectedCategories = new Set();
            let activeMenu = null;

            window.showCorrectionMenu = function(button) {
                // Chiudi il menu precedente se esiste
                if (activeMenu) {
                    activeMenu.classList.add('hidden');
                    selectedCategories.clear();
                }

                const menu = button.parentElement.nextElementSibling;
                menu.classList.remove('hidden');
                activeMenu = menu;

                // Chiudi il menu se si clicca fuori
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target) && e.target !== button) {
                        menu.classList.add('hidden');
                        selectedCategories.clear();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            };

            window.addCategory = function(button, category) {
                const messageContainer = button.closest('.max-w-sm');
                const selectedCategoriesDiv = messageContainer.querySelector('.selected-categories');

                if (selectedCategories.has(category)) {
                    selectedCategories.delete(category);
                    button.classList.remove('bg-purple-100');
                } else {
                    selectedCategories.add(category);
                    button.classList.add('bg-purple-100');
                }

                // Aggiorna il display delle categorie selezionate
                selectedCategoriesDiv.textContent = Array.from(selectedCategories).join(', ');
            };

            window.cancelCorrection = function(button) {
                const menu = button.closest('.correction-menu');
                menu.classList.add('hidden');
                selectedCategories.clear();
                activeMenu = null;
            };

            window.submitCorrection = function(button) {
                if (selectedCategories.size === 0) {
                    return;
                }

                const messageContainer = button.closest('.max-w-sm');
                const message = messageContainer.querySelector('p').textContent;
                const categories = Array.from(selectedCategories).join(',');

                // Invia la correzione
                sendMessage(`correggi: ${categories}`)
                    .then(data => {
                        setTimeout(() => {
                            hideTypingIndicator();
                            addMessage(data.message || data.response);
                            // Chiudi il menu
                            cancelCorrection(button);
                        }, 500);
                    })
                    .catch(error => {
                        hideTypingIndicator();
                        addMessage('Mi dispiace, si è verificato un errore nella correzione.');
                        cancelCorrection(button);
                    });
            };

            async function sendFeedback(type, message) {
                try {
                    const response = await fetch('/chatbot/feedback', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ type, message })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    if (data.message) {
                        // Mostra il messaggio di conferma come un nuovo messaggio del bot
                        addMessage(data.message);
                    }
                } catch (error) {
                    console.error('Errore nell\'invio del feedback:', error);
                }
            }

            // Gestione del menu di insegnamento
            let selectedTeachCategories = new Set();
            let activeTeachMenu = null;

            window.showTeachMenu = function(button) {
                // Chiudi il menu precedente se esiste
                if (activeTeachMenu) {
                    activeTeachMenu.classList.add('hidden');
                    selectedTeachCategories.clear();
                }

                // Trova il menu di insegnamento più vicino
                const menu = button.closest('.max-w-sm, .bg-purple-50').querySelector('.teach-menu');
                if (!menu) return;

                menu.classList.remove('hidden');
                activeTeachMenu = menu;

                // Prendi il messaggio del bot come esempio se disponibile
                const messageElement = button.closest('.max-w-sm')?.querySelector('p');
                if (messageElement) {
                    const botMessage = messageElement.textContent;
                    menu.querySelector('.teach-expression').value = '';
                    menu.querySelector('.teach-expression').placeholder = `Es: "${botMessage}"`;
                }

                // Chiudi il menu se si clicca fuori
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target) && e.target !== button) {
                        menu.classList.add('hidden');
                        selectedTeachCategories.clear();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            };

            window.addTeachCategory = function(button, category) {
                const messageContainer = button.closest('.max-w-sm');
                const selectedCategoriesDiv = messageContainer.querySelector('.selected-teach-categories');

                if (selectedTeachCategories.has(category)) {
                    selectedTeachCategories.delete(category);
                    button.classList.remove('bg-green-100');
                } else {
                    selectedTeachCategories.add(category);
                    button.classList.add('bg-green-100');
                }

                // Aggiorna il display delle categorie selezionate
                selectedCategoriesDiv.textContent = Array.from(selectedTeachCategories).join(', ');
            };

            window.cancelTeach = function(button) {
                const menu = button.closest('.teach-menu');
                menu.classList.add('hidden');
                selectedTeachCategories.clear();
                activeTeachMenu = null;
            };

            window.submitTeach = function(button) {
                const menu = button.closest('.teach-menu');
                const expression = menu.querySelector('.teach-expression').value.trim();

                if (!expression || selectedTeachCategories.size === 0) {
                    return;
                }

                const categories = Array.from(selectedTeachCategories).join(',');

                // Invia il comando di apprendimento
                sendMessage(`impara: ${expression} -> ${categories}`)
                    .then(data => {
                        setTimeout(() => {
                            hideTypingIndicator();
                            addMessage(data.message || data.response);
                            // Chiudi il menu
                            cancelTeach(button);
                        }, 500);
                    })
                    .catch(error => {
                        hideTypingIndicator();
                        addMessage('Mi dispiace, si è verificato un errore nel processo di apprendimento.');
                        cancelTeach(button);
                    });
            };

            // Inizializza gli eventi quando il DOM è caricato
            document.addEventListener('DOMContentLoaded', function() {
                // Focus sull'input quando la pagina è caricata
                messageInput.focus();

                // Scroll to bottom quando la pagina è caricata
                scrollToBottom();
            });
        });
    </script>
</body>
</html>
