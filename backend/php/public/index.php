<?php
/**
 * Unico punto d'ingresso delle API WolfPress.
 * La radice del sito web deve essere QUESTA cartella (public/): così
 * src/, .env, storage/ e bin/ non sono raggiungibili dal browser.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

/** [metodo, percorso (regex), operazione, serve il token CSRF?] */
$rotte = [
    ['GET',  'csrf',                              static fn () => ['csrf' => Http::tokenCsrf()]],
    // --- account ---
    ['POST', 'registrazione',                     static fn () => Account::registra()],
    ['POST', 'accesso',                           static fn () => ['utente' => Account::accedi(), 'csrf' => Http::tokenCsrf()]],
    ['POST', 'uscita',                            static function () { Account::esci(); return ['ok' => true]; }],
    ['GET',  'me',                                static fn () => ['utente' => Account::pubblico(Account::richiedi())]],
    ['POST', 'password/richiesta',                static function () { Account::richiediReset(); return ['ok' => true, 'messaggio' => 'Se l\'indirizzo è registrato riceverai un\'email con le istruzioni.']; }],
    ['POST', 'password/nuova',                    static function () { Account::nuovaPassword(); return ['ok' => true]; }],
    // --- giornali e articoli ---
    ['GET',  'giornali',                          static fn () => Api::giornali()],
    ['POST', 'giornali',                          static fn () => Api::creaGiornale()],
    ['GET',  'giornali/(\d+)/articoli',           static fn ($id) => Api::articoliGiornale($id)],
    ['POST', 'giornali/(\d+)/articoli',           static fn ($id) => Api::creaArticoloGiornale($id)],
    ['POST', 'articoli-giornali/(\d+)/pubblica',  static fn ($id) => Api::pubblicaArticoloGiornale($id)],
    ['GET',  'politici',                          static fn () => Api::politici()],
    ['GET',  'politici/(\d+)/articoli',           static fn ($id) => Api::articoliPolitico($id)],
    ['POST', 'politici/articoli',                 static fn () => Api::creaArticoloPolitico()],
    // --- eventi ---
    ['GET',  'eventi',                            static fn () => Api::eventi()],
    ['POST', 'eventi',                            static fn () => Api::creaEvento()],
    ['GET',  'eventi/miei',                       static fn () => Api::mieiEventi()],
    ['POST', 'eventi/(\d+)/conferma',             static fn ($id) => Api::confermaOrganizzazione($id)],
    ['POST', 'eventi/(\d+)/inviti',               static fn ($id) => Api::invita($id)],
    ['POST', 'eventi/(\d+)/risposta',             static fn ($id) => Api::rispondiInvito($id)],
    ['POST', 'eventi/(\d+)/annulla',              static fn ($id) => Api::annullaEvento($id)],
    ['POST', 'eventi/(\d+)/partecipa',            static fn ($id) => Api::partecipa($id)],
    // --- iscrizioni, abbonamenti, donazioni ---
    ['GET',  'iscrizioni',                        static fn () => Api::iscrizioni()],
    ['POST', 'iscrizioni',                        static fn () => Api::iscriviti()],
    ['POST', 'iscrizioni/annulla',                static fn () => Api::annullaIscrizione()],
    ['GET',  'abbonamenti',                       static fn () => Api::abbonamenti()],
    ['POST', 'abbonamenti',                       static fn () => Api::abbonati()],
    ['POST', 'donazioni',                         static fn () => Api::dona()],
    // --- ticket ---
    ['GET',  'ticket',                            static fn () => Api::ticket()],
    ['POST', 'ticket',                            static fn () => Api::creaTicket()],
    ['GET',  'ticket/(\d+)',                      static fn ($id) => Api::dettaglioTicket($id)],
    ['POST', 'ticket/(\d+)/messaggi',             static fn ($id) => Api::rispondiTicket($id)],
    ['POST', 'ticket/(\d+)/gestione',             static fn ($id) => Api::gestisciTicket($id)],
    // --- tecnici ---
    ['GET',  'tecnico/account-in-attesa',         static fn () => Api::accountInAttesa()],
    ['POST', 'tecnico/account/(\d+)',             static fn ($id) => Api::statoAccount($id)],
];

try {
    Http::intestazioni();
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $percorso = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    if (!str_starts_with($percorso, 'api/')) {
        throw new ErroreApi('Risorsa non trovata.', 404);
    }
    $percorso = substr($percorso, 4);

    if ($metodo === 'OPTIONS') {           // preflight CORS
        http_response_code(204);
        exit;
    }

    // Webhook Stripe: nessun cookie, nessun CSRF, vale solo la firma.
    if ($metodo === 'POST' && $percorso === 'stripe/webhook') {
        Http::json(Api::webhookStripe());
    }

    Http::avviaSessione();

    // Link cliccato dall'email di registrazione: risponde con una paginetta di testo.
    if ($metodo === 'GET' && $percorso === 'verifica-email') {
        $stato = Account::verificaEmail((string) ($_GET['token'] ?? ''));
        header('Content-Type: text/plain; charset=utf-8');
        echo $stato === 'attivo'
            ? "Indirizzo confermato: il tuo account è attivo, ora puoi accedere.\n"
            : "Indirizzo confermato: il tuo profilo è in attesa di approvazione, ti avviseremo per email.\n";
        exit;
    }

    $trovatoPercorso = false;
    foreach ($rotte as [$metodoRotta, $modello, $operazione]) {
        if (!preg_match('#^' . $modello . '$#', $percorso, $parti)) {
            continue;
        }
        $trovatoPercorso = true;
        if ($metodoRotta !== $metodo) {
            continue;
        }
        if ($metodo === 'POST') {
            Http::controllaCsrf();
            if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
                throw new ErroreApi('Usa Content-Type: application/json', 415);
            }
        }
        Http::json($operazione(...array_map('intval', array_slice($parti, 1))));
    }
    throw $trovatoPercorso ? new ErroreApi('Metodo non ammesso.', 405) : new ErroreApi('Risorsa non trovata.', 404);
} catch (ErroreApi $e) {
    if ($e->stato === 400 && isset($percorso) && $percorso === 'verifica-email') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo $e->getMessage() . "\n";
        exit;
    }
    Http::json(['errore' => $e->getMessage()], $e->stato);
} catch (PDOException $e) {
    // Vincoli del database trasformati in errori comprensibili, senza dettagli SQL.
    $codice = $e->errorInfo[1] ?? 0;
    if ($codice === 1062) {
        Http::json(['errore' => 'Elemento già presente.'], 409);
    }
    if ($codice === 1452) {
        Http::json(['errore' => 'Uno degli elementi indicati non esiste.'], 422);
    }
    error_log('Errore database: ' . $e->getMessage());
    Http::json(['errore' => 'Errore interno.'], 500);
} catch (Throwable $e) {
    // Il dettaglio resta nel log del server: al client non si rivela nulla.
    error_log('Errore: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Http::json(['errore' => 'Errore interno.'], 500);
}
