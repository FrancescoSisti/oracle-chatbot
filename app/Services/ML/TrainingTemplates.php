<?php

namespace App\Services\ML;

class TrainingTemplates
{
    public static function getTemplates(): array
    {
        return [
            'customer_service' => self::getCustomerServiceTemplate(),
            'e_commerce' => self::getECommerceTemplate(),
            'support_tech' => self::getTechnicalSupportTemplate(),
            'daily_conversations' => self::getDailyConversationTemplates()
        ];
    }

    public static function getCustomerServiceTemplate(): array
    {
        return [
            // Saluti
            ['text' => 'Buongiorno, avrei bisogno di assistenza', 'category' => 'saluto'],
            ['text' => 'Salve, posso chiedere un\'informazione?', 'category' => 'saluto'],
            ['text' => 'Ciao, ho un problema da risolvere', 'category' => 'saluto'],

            // Ordini
            ['text' => 'Qual è lo stato del mio ordine?', 'category' => 'ordine_stato'],
            ['text' => 'Non ho ricevuto la conferma dell\'ordine', 'category' => 'ordine_stato'],
            ['text' => 'Quando arriverà il mio ordine?', 'category' => 'ordine_stato'],
            ['text' => 'Posso modificare il mio ordine?', 'category' => 'ordine_stato'],

            // Resi
            ['text' => 'Vorrei fare un reso', 'category' => 'reso'],
            ['text' => 'Come posso restituire un prodotto?', 'category' => 'reso'],
            ['text' => 'Procedura per il rimborso', 'category' => 'reso'],
            ['text' => 'Ho ricevuto un prodotto danneggiato', 'category' => 'reso'],

            // Reclami
            ['text' => 'Voglio presentare un reclamo', 'category' => 'reclamo'],
            ['text' => 'Non sono soddisfatto del servizio', 'category' => 'reclamo'],
            ['text' => 'Ho un problema con il prodotto', 'category' => 'reclamo'],

            // Informazioni
            ['text' => 'Vorrei maggiori informazioni', 'category' => 'richiesta_info'],
            ['text' => 'Potete darmi più dettagli?', 'category' => 'richiesta_info'],
            ['text' => 'Non ho capito come funziona', 'category' => 'richiesta_info']
        ];
    }

    public static function getECommerceTemplate(): array
    {
        return [
            // Prodotti
            ['text' => 'Avete questo prodotto disponibile?', 'category' => 'prodotti'],
            ['text' => 'Quando sarà di nuovo disponibile?', 'category' => 'prodotti'],
            ['text' => 'Cercavo un prodotto simile', 'category' => 'prodotti'],
            ['text' => 'Quali sono le caratteristiche?', 'category' => 'prodotti'],

            // Prezzi
            ['text' => 'Quanto costa questo articolo?', 'category' => 'prezzo'],
            ['text' => 'Ci sono sconti o promozioni?', 'category' => 'prezzo'],
            ['text' => 'Prezzo della spedizione?', 'category' => 'prezzo'],
            ['text' => 'Accettate pagamenti rateali?', 'category' => 'prezzo'],

            // Spedizioni
            ['text' => 'Tempi di consegna previsti?', 'category' => 'spedizione'],
            ['text' => 'Spedite all\'estero?', 'category' => 'spedizione'],
            ['text' => 'Costo della spedizione express', 'category' => 'spedizione'],
            ['text' => 'Tracking del mio ordine', 'category' => 'spedizione'],

            // Pagamenti
            ['text' => 'Quali metodi di pagamento accettate?', 'category' => 'pagamento'],
            ['text' => 'Posso pagare alla consegna?', 'category' => 'pagamento'],
            ['text' => 'Come funziona il pagamento rateale?', 'category' => 'pagamento'],
            ['text' => 'Ho problemi con il pagamento', 'category' => 'pagamento']
        ];
    }

    public static function getTechnicalSupportTemplate(): array
    {
        return [
            // Problemi Tecnici
            ['text' => 'Il sito non funziona', 'category' => 'problema_tecnico'],
            ['text' => 'Non riesco ad accedere', 'category' => 'problema_tecnico'],
            ['text' => 'La pagina dà errore', 'category' => 'problema_tecnico'],
            ['text' => 'L\'app si blocca', 'category' => 'problema_tecnico'],

            // Account
            ['text' => 'Come resetto la password?', 'category' => 'account'],
            ['text' => 'Non ricordo le credenziali', 'category' => 'account'],
            ['text' => 'Voglio modificare i miei dati', 'category' => 'account'],
            ['text' => 'Come cancello l\'account?', 'category' => 'account'],

            // Funzionalità
            ['text' => 'Come si usa questa funzione?', 'category' => 'funzionalita'],
            ['text' => 'Non trovo l\'opzione per...', 'category' => 'funzionalita'],
            ['text' => 'Dove posso trovare...?', 'category' => 'funzionalita'],
            ['text' => 'Come si attiva...?', 'category' => 'funzionalita']
        ];
    }

    public static function getDailyConversationTemplates(): array
    {
        return [
            // Saluti e convenevoli
            ['text' => 'Buongiorno', 'category' => 'saluto'],
            ['text' => 'Ciao, come stai?', 'category' => 'saluto'],
            ['text' => 'Salve, come va?', 'category' => 'saluto'],
            ['text' => 'Buonasera', 'category' => 'saluto'],
            ['text' => 'Ciao, è bello rivederti', 'category' => 'saluto'],

            // Conversazione generale
            ['text' => 'Che tempo fa oggi?', 'category' => 'conversazione'],
            ['text' => 'Come è andata la giornata?', 'category' => 'conversazione'],
            ['text' => 'Hai programmi per il weekend?', 'category' => 'conversazione'],
            ['text' => 'Che ne pensi di questo tempo?', 'category' => 'conversazione'],

            // Stati d'animo
            ['text' => 'Sono molto felice oggi', 'category' => 'stato_emotivo'],
            ['text' => 'Mi sento un po\' giù', 'category' => 'stato_emotivo'],
            ['text' => 'Sono stanco', 'category' => 'stato_emotivo'],
            ['text' => 'Oggi è una bellissima giornata', 'category' => 'stato_emotivo'],

            // Richieste di aiuto
            ['text' => 'Potresti aiutarmi?', 'category' => 'richiesta_aiuto'],
            ['text' => 'Ho bisogno di un consiglio', 'category' => 'richiesta_aiuto'],
            ['text' => 'Non so cosa fare', 'category' => 'richiesta_aiuto'],

            // Ringraziamenti
            ['text' => 'Grazie mille', 'category' => 'ringraziamento'],
            ['text' => 'Ti ringrazio', 'category' => 'ringraziamento'],
            ['text' => 'Sei stato molto gentile', 'category' => 'ringraziamento'],

            // Congedi
            ['text' => 'Arrivederci', 'category' => 'congedo'],
            ['text' => 'A presto', 'category' => 'congedo'],
            ['text' => 'Buona giornata', 'category' => 'congedo'],
            ['text' => 'Ci vediamo', 'category' => 'congedo'],

            // Scuse
            ['text' => 'Mi dispiace', 'category' => 'scusa'],
            ['text' => 'Scusami tanto', 'category' => 'scusa'],
            ['text' => 'Non volevo', 'category' => 'scusa'],

            // Richieste di opinione
            ['text' => 'Cosa ne pensi?', 'category' => 'richiesta_opinione'],
            ['text' => 'Qual è la tua opinione?', 'category' => 'richiesta_opinione'],
            ['text' => 'Mi interessa il tuo parere', 'category' => 'richiesta_opinione'],

            // Richieste di chiarimento
            ['text' => 'Non ho capito bene', 'category' => 'richiesta_chiarimento'],
            ['text' => 'Puoi ripetere?', 'category' => 'richiesta_chiarimento'],
            ['text' => 'Non sono sicuro di aver compreso', 'category' => 'richiesta_chiarimento'],

            // Domande sulle capacità
            ['text' => 'cosa sai fare?', 'category' => 'capacita'],
            ['text' => 'quali sono le tue capacità?', 'category' => 'capacita'],
            ['text' => 'come puoi aiutarmi?', 'category' => 'capacita'],
            ['text' => 'cosa puoi fare per me?', 'category' => 'capacita'],
            ['text' => 'in cosa sei specializzato?', 'category' => 'capacita'],
            ['text' => 'quali sono le tue funzioni?', 'category' => 'capacita'],
            ['text' => 'dimmi cosa sai fare', 'category' => 'capacita'],
            ['text' => 'che tipo di assistenza fornisci?', 'category' => 'capacita'],
            ['text' => 'come funzioni?', 'category' => 'capacita'],
            ['text' => 'quali sono i tuoi compiti?', 'category' => 'capacita']
        ];
    }
}
