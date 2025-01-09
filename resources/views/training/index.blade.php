<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Oracle AI - Training Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-800">Oracle AI</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('chatbot.index') }}" class="text-gray-600 hover:text-gray-900">Chat</a>
                    <span class="text-gray-600">|</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Totale Frasi</h3>
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ App\Models\TrainingData::count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Frasi Verificate</h3>
                <p class="text-3xl font-bold text-green-600">
                    {{ App\Models\TrainingData::where('is_verified', true)->count() }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confidenza Media</h3>
                <p class="text-3xl font-bold text-purple-600">
                    {{ number_format(App\Models\TrainingData::avg('confidence_score') ?? 0, 2) }}%
                </p>
            </div>
        </div>

        <!-- Training Form -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Aggiungi Nuova Frase</h2>
                <form action="{{ route('training.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="text" class="block text-sm font-medium text-gray-700 mb-1">Frase di esempio</label>
                            <input type="text" name="text" id="text" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                            <select name="category" id="category" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="saluto">Saluto</option>
                                <option value="richiesta_info">Richiesta Informazioni</option>
                                <option value="prezzo">Prezzo</option>
                                <option value="richiesta_aiuto">Richiesta Aiuto</option>
                                <option value="orario">Orario</option>
                                <option value="prenotazione">Prenotazione</option>
                                <option value="reclamo">Reclamo</option>
                                <option value="ringraziamento">Ringraziamento</option>
                                <option value="prodotti">Prodotti</option>
                                <option value="spedizione">Spedizione</option>
                                <option value="pagamento">Pagamento</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Aggiungi al Training
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Training Templates -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Training e Importazione</h2>
            </div>
            <div class="p-6 space-y-8">
                <!-- Training Controls -->
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-purple-900 mb-4">Training Avanzato</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch Size</label>
                            <input type="number" id="batch-size" value="32" min="1" max="128"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Epochs</label>
                            <input type="number" id="epochs" value="10" min="1" max="100"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Validation Split</label>
                            <input type="number" id="validation-split" value="0.2" min="0.1" max="0.5" step="0.1"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                    </div>
                    <button id="start-training"
                            class="w-full bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Avvia Training
                    </button>

                    <!-- Training Status -->
                    <div id="pretraining-status" class="mt-6 hidden">
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-6">
                                    <div>
                                        <span class="text-sm font-medium text-purple-800">Stato:</span>
                                        <span id="training-state" class="ml-2 text-sm text-purple-600">In corso...</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-purple-800">Esempi Processati:</span>
                                        <span id="processed-examples" class="ml-2 text-sm text-purple-600">0</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-purple-800">Confidenza Media:</span>
                                        <span id="avg-confidence" class="ml-2 text-sm text-purple-600">0%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block text-purple-600">
                                            Progresso
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span id="training-percentage" class="text-xs font-semibold inline-block text-purple-600">0%</span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-purple-200">
                                    <div id="training-progress" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-600" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Templates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Daily Conversations -->
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <svg class="h-6 w-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900">Conversazioni Quotidiane</h3>
                            </div>
                            <p class="text-gray-600 mb-4">Importa esempi di dialoghi comuni per migliorare la naturalezza delle conversazioni.</p>
                            <form action="{{ route('training.import.daily') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors duration-200">
                                    Importa Conversazioni
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Historical Events -->
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <svg class="h-6 w-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900">Eventi Storici</h3>
                            </div>
                            <form action="{{ route('training.import.wiki') }}" method="POST" class="space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Anno</label>
                                        <select name="year"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @for ($y = date('Y'); $y >= 1900; $y--)
                                                <option value="{{ $y }}">{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Numero di eventi</label>
                                        <select name="limit"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="10">10 eventi</option>
                                            <option value="25">25 eventi</option>
                                            <option value="50">50 eventi</option>
                                            <option value="100" selected>100 eventi</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                                        Importa Eventi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Training Metrics -->
                <div id="training-metrics" class="hidden fade-in">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Metriche di Training</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-sm text-purple-600 font-medium">Accuracy</p>
                                <p class="text-2xl font-bold text-purple-900" id="metric-accuracy">-</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-blue-600 font-medium">Loss</p>
                                <p class="text-2xl font-bold text-blue-900" id="metric-loss">-</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <p class="text-sm text-green-600 font-medium">Esempi Totali</p>
                                <p class="text-2xl font-bold text-green-900" id="metric-examples">-</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <p class="text-sm text-yellow-600 font-medium">Epochs</p>
                                <p class="text-2xl font-bold text-yellow-900" id="metric-epochs">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pre-training con C4 Dataset -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Pre-training con C4 Dataset</h2>

                <form id="pretraining-form" action="{{ route('training.pretrain') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="examples" class="block text-sm font-medium text-gray-700">Numero di esempi</label>
                            <select name="examples" id="examples" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md">
                                <option value="1000">1,000 esempi</option>
                                <option value="5000">5,000 esempi</option>
                                <option value="10000">10,000 esempi</option>
                                <option value="20000">20,000 esempi</option>
                                <option value="50000">50,000 esempi</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label for="chunks" class="block text-sm font-medium text-gray-700">Numero di chunks</label>
                            <select name="chunks" id="chunks" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md">
                                <option value="10">10 chunks</option>
                                <option value="20">20 chunks</option>
                                <option value="40">40 chunks</option>
                                <option value="80">80 chunks</option>
                            </select>
                        </div>
                        <div class="flex-1 pt-6">
                            <button type="submit" id="start-pretraining" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Avvia Pre-training
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Training Status -->
                <div id="pretraining-status" class="mt-6 hidden">
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-6">
                                <div>
                                    <span class="text-sm font-medium text-purple-800">Stato:</span>
                                    <span id="training-state" class="ml-2 text-sm text-purple-600">In corso...</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-purple-800">Esempi Processati:</span>
                                    <span id="processed-examples" class="ml-2 text-sm text-purple-600">0</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-purple-800">Confidenza Media:</span>
                                    <span id="avg-confidence" class="ml-2 text-sm text-purple-600">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative pt-1">
                            <div class="flex mb-2 items-center justify-between">
                                <div>
                                    <span class="text-xs font-semibold inline-block text-purple-600">
                                        Progresso
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span id="training-percentage" class="text-xs font-semibold inline-block text-purple-600">0%</span>
                                </div>
                            </div>
                            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-purple-200">
                                <div id="training-progress" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-purple-600" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Data Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Frasi di Training</h2>
            </div>
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text"
                               placeholder="Cerca frasi..."
                               class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <svg class="h-5 w-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Tutte le categorie</option>
                        <option value="saluto">Saluto</option>
                        <option value="conversazione">Conversazione</option>
                        <!-- ... altre categorie ... -->
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frase</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confidenza</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($trainingData as $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-normal">
                                <div class="text-sm text-gray-900">{{ $data->text }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $data->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ number_format($data->confidence_score, 2) }}%</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($data->is_verified)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Verificato
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        In attesa
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium space-x-2">
                                @if(!$data->is_verified)
                                    <form action="{{ route('training.verify', $data) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900">Verifica</button>
                                    </form>
                                @endif
                                <form action="{{ route('training.delete', $data) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 ml-2">Elimina</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $trainingData->links() }}
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="fixed bottom-4 right-4">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    </div>
    @endif

    <!-- Training Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startTrainingBtn = document.getElementById('start-training');
            const metricsContainer = document.getElementById('training-metrics');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                console.error('CSRF token non trovato');
                return;
            }

            startTrainingBtn.addEventListener('click', async function() {
                try {
                    startTrainingBtn.disabled = true;
                    startTrainingBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Training in corso...
                    `;

                    const response = await fetch('{{ route("training.train-model") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            batch_size: parseInt(document.getElementById('batch-size').value),
                            epochs: parseInt(document.getElementById('epochs').value),
                            validation_split: parseFloat(document.getElementById('validation-split').value)
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        metricsContainer.classList.remove('hidden');
                        updateMetrics(data.metrics);
                    }
                } catch (error) {
                    console.error('Errore durante il training:', error);
                    alert('Si è verificato un errore durante il training. Controlla la console per i dettagli.');
                } finally {
                    startTrainingBtn.disabled = false;
                    startTrainingBtn.innerHTML = 'Avvia Training';
                }
            });

            function updateMetrics(metrics) {
                document.getElementById('metric-accuracy').textContent =
                    (metrics.accuracy * 100).toFixed(2) + '%';
                document.getElementById('metric-loss').textContent =
                    metrics.loss.toFixed(4);
                document.getElementById('metric-examples').textContent =
                    metrics.total_examples;
                document.getElementById('metric-epochs').textContent =
                    metrics.epochs;
            }
        });
    </script>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pretrainingForm = document.getElementById('pretraining-form');
            const startPretrainingBtn = document.getElementById('start-pretraining');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                console.error('CSRF token non trovato');
                return;
            }

            pretrainingForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    startPretrainingBtn.disabled = true;
                    startPretrainingBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Pre-training in corso...
                    `;

                    const formData = new FormData(pretrainingForm);
                    const response = await fetch('{{ route("training.pretrain") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            examples: formData.get('examples'),
                            chunks: formData.get('chunks')
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    if (data.success) {
                        document.getElementById('pretraining-status').classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Errore durante l\'avvio del pre-training:', error);
                    alert('Si è verificato un errore durante l\'avvio del pre-training. Controlla la console per i dettagli.');
                }
            });
        });

        function updateTrainingStatus() {
            console.log('Richiesta stato training...'); // Debug log
            fetch('/api/training/status')
                .then(response => {
                    console.log('Risposta ricevuta:', response.status); // Debug log
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dati ricevuti:', data); // Debug log
                    const trainingStatus = document.getElementById('pretraining-status');
                    if (!trainingStatus) {
                        console.error('Elemento pretraining-status non trovato');
                        return;
                    }

                    if (data.is_training) {
                        console.log('Training in corso, aggiorno pannello...'); // Debug log
                        trainingStatus.classList.remove('hidden');
                        document.getElementById('processed-examples').textContent = data.processed_examples;
                        document.getElementById('avg-confidence').textContent =
                            `${(data.avg_confidence * 100).toFixed(1)}%`;
                        const percentage = (data.processed_examples / data.total_examples * 100).toFixed(1);
                        document.getElementById('training-progress').style.width = `${percentage}%`;
                        document.getElementById('training-percentage').textContent = `${percentage}%`;
                    } else {
                        console.log('Training non in corso, nascondo pannello'); // Debug log
                        trainingStatus.classList.add('hidden');
                        startPretrainingBtn.disabled = false;
                        startPretrainingBtn.innerHTML = 'Avvia Pre-training';
                    }
                })
                .catch(error => {
                    console.error('Errore nel recupero dello stato del training:', error);
                });
        }

        // Aggiorna lo stato ogni 2 secondi
        setInterval(updateTrainingStatus, 2000);
        // Prima chiamata immediata
        updateTrainingStatus();
    </script>
    @endpush
</body>
</html>
