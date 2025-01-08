<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training ChatBot - Dashboard</title>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Totale Frasi</h3>
                <p class="text-3xl font-bold text-blue-600">{{ App\Models\TrainingData::count() }}</p>
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

        <!-- Importazione Massiva -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Importazione Massiva</h2>

                <!-- Import File -->
                <form action="{{ route('training.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="file" name="file" accept=".csv,.json"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Importa File
                        </button>
                    </div>
                </form>

                <!-- Template Quick Import -->
                <div class="space-y-4">
                    <h3 class="text-md font-medium text-gray-700">Template Rapidi</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach(['customer_service', 'e_commerce', 'support_tech'] as $template)
                        <form action="{{ route('training.import.template') }}" method="POST">
                            @csrf
                            <input type="hidden" name="template" value="{{ $template }}">
                            <button type="submit"
                                    class="w-full bg-blue-50 text-blue-700 px-4 py-2 rounded-md hover:bg-blue-100">
                                {{ ucfirst(str_replace('_', ' ', $template)) }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-md font-medium text-gray-700">Importa da JSON</h3>
                    <form action="{{ route('training.import.json') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <input type="file"
                                       name="json_file"
                                       accept=".json"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <button type="submit"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                Importa JSON
                            </button>
                        </div>
                    </form>
                    <p class="text-sm text-gray-500">
                        Formato JSON atteso: array di oggetti con campi "text" e "category"
                    </p>
                </div>
            </div>
        </div>

        <!-- Training Data Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Frasi di Training</h2>
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
</body>
</html>
