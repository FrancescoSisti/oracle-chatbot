<?php

namespace App\Services\ML;

class TrainingTemplates
{
    public static function getTemplates(): array
    {
        return [
            'customer_service' => self::getCustomerServiceTemplate(),
            'e_commerce' => self::getECommerceTemplate(),
            'support_tech' => self::getTechnicalSupportTemplate()
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
}
