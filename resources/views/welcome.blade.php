<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBot AI - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Auth Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-gray-800">ChatBot AI</span>
                    </div>
                </div>
                <div class="flex items-center">
                    @auth
                        <span class="text-gray-600 mr-4">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-gray-600 hover:text-gray-900">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-gray-600 hover:text-gray-900">Login</a>
                        <a href="{{ route('register') }}"
                           class="ml-4 text-gray-600 hover:text-gray-900">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen flex flex-col items-center justify-center">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">ChatBot AI Dashboard</h1>
            <p class="text-lg text-gray-600">Sistema di Intelligenza Artificiale per il Supporto Clienti</p>
        </div>

        <!-- Navigation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl w-full px-4">
            <!-- ChatBot Card -->
            <a href="{{ route('chatbot.index') }}"
               class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-semibold text-gray-800">Chat Interface</h2>
                        <p class="text-gray-600 mt-1">Interagisci con il ChatBot</p>
                    </div>
                </div>
            </a>

            <!-- Training Interface Card -->
            @auth
                <a href="{{ route('training.index') }}"
                   class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-gray-800">Training Interface</h2>
                            <p class="text-gray-600 mt-1">Gestisci il training del ChatBot</p>
                        </div>
                    </div>
                </a>
            @else
                <div class="bg-gray-50 rounded-lg shadow-md p-6 flex items-center justify-center">
                    <p class="text-gray-600">Effettua il login per accedere al training</p>
                </div>
            @endauth
        </div>

        <!-- Stats Section -->
        <div class="mt-12 w-full max-w-4xl px-4">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistiche del Sistema</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-gray-600">Training Data</p>
                        <p class="text-2xl font-bold text-blue-600">{{ App\Models\TrainingData::count() }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600">Dati Verificati</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ App\Models\TrainingData::where('is_verified', true)->count() }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600">Confidenza Media</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{ number_format(App\Models\TrainingData::avg('confidence_score') ?? 0, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
