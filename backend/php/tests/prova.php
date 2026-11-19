<?php
/**
 * Prova completa delle API su un database vero (vuoto).
 *
 * Uso (dalla cartella backend/php, con lo schema già caricato):
 *   DB_UTENTE=wolfpress DB_PASSWORD=... php tests/prova.php
 *
 * Avvia da sola il server PHP e una finta API Stripe; le email finiscono
 * in storage/email.log. ATTENZIONE: scrive dati di prova nel database.
 */
declare(strict_types=1);

$radice = dirname(__DIR__);
$ambiente = [
    'APP_ENV' => 'sviluppo', 'APP_URL' => 'http://127.0.0.1:8091', 'FRONTEND_URL' => 'http://127.0.0.1:8080/sito',
    'MAIL_DRIVER' => 'log', 'STRIPE_API_BASE' => 'http://127.0.0.1:8092', 'STRIPE_CHIAVE_SEGRETA' => 'sk_test_finta',
    'STRIPE_SEGRETO_WEBHOOK' => 'whsec_prova',
    'CHIAVE_CIFRATURA' => base64_encode(random_bytes(32)), 'CHIAVE_INDICE' => base64_encode(random_bytes(32)),
    'PEPE_PASSWORD' => base64_encode(random_bytes(32)), 'no_proxy' => '127.0.0.1', 'NO_PROXY' => '127.0.0.1',
] + getenv();
foreach ($ambiente as $nome => $valore) {
    putenv("$nome=$valore");
}
@unlink("$radice/storage/email.log");
$tubi = [];
$api = proc_open([PHP_BINARY, '-S', '127.0.0.1:8091', '-t', "$radice/public", "$radice/public/index.php"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', "$radice/storage/server.log", 'w']], $tubi, null, $ambiente);
$stripe = proc_open([PHP_BINARY, '-S', '127.0.0.1:8092', "$radice/tests/finta-stripe.php"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $tubi);
register_shutdown_function(static function () use ($api, $stripe) { proc_terminate($api); proc_terminate($stripe); });
usleep(700000);

require "$radice/src/bootstrap.php";

$falliti = 0;
function verifica(string $cosa, bool $ok, mixed $dettaglio = null): void
{
    global $falliti;
    echo ($ok ? '  ok   ' : '  ERRORE ') . $cosa . ($ok ? '' : ' → ' . json_encode($dettaglio, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
    $falliti += $ok ? 0 : 1;
}

/** Un "browser" con i suoi cookie e il suo token CSRF. */
final class Cliente
{
    private string $cookie;
    public string $csrf = '';

    public function __construct()
    {
        $this->cookie = tempnam(sys_get_temp_dir(), 'wp');
        $this->csrf = $this->get('csrf')[1]['csrf'];
    }

    public function get(string $percorso): array
    {
        return $this->chiama('GET', $percorso);
    }

    public function post(string $percorso, array $dati = [], array $intestazioni = [], ?string $grezzo = null): array
    {
        return $this->chiama('POST', $percorso, $grezzo ?? json_encode($dati), $intestazioni);
    }

    private function chiama(string $metodo, string $percorso, ?string $corpo = null, array $intestazioni = []): array
    {
        $c = curl_init("http://127.0.0.1:8091/api/$percorso");
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->cookie, CURLOPT_COOKIEFILE => $this->cookie,
            CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_PROXY => '',
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'X-CSRF-Token: ' . $this->csrf], $intestazioni),
        ]);
        if ($corpo !== null) {
            curl_setopt($c, CURLOPT_POSTFIELDS, $corpo);
        }
        $testo = (string) curl_exec($c);
        $stato = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        curl_close($c);
        $dati = json_decode($testo, true);
        if (isset($dati['csrf'])) {
            $this->csrf = $dati['csrf'];
        }
        return [$stato, $dati ?? $testo];
    }
}

/** Ultimo token di verifica finito nel registro delle email. */
function ultimoToken(): string
{
    global $radice;
    preg_match_all('/token=([a-f0-9]{64})/', (string) file_get_contents("$radice/storage/email.log"), $m);
    return (string) end($m[1]);
}

function registra(Cliente $c, string $tipo, string $nome, string $email, array $extra = []): array
{
    return $c->post('registrazione', ['tipo' => $tipo, 'nome' => $nome, 'cognome' => 'Prova', 'email' => $email, 'password' => 'Password-di-prova-1', 'privacy' => true] + $extra);
}

function webhook(Cliente $c, array $evento, string $segreto = 'whsec_prova'): array
{
    $corpo = json_encode($evento);
    $t = time();
    return $c->post('stripe/webhook', [], ['Stripe-Signature: t=' . $t . ',v1=' . hash_hmac('sha256', "$t.$corpo", $segreto)], $corpo);
}

$s = bin2hex(random_bytes(3));            // suffisso: la prova si può ripetere
echo "== Registrazione, cifratura, email ==\n";
$lettore = new Cliente();
[$stato, $r] = (new Cliente())->post('registrazione', []);
verifica('registrazione senza dati rifiutata', $stato === 422, $r);
$senzaCsrf = new Cliente(); $senzaCsrf->csrf = 'sbagliato';
verifica('POST senza token CSRF rifiutata', registra($senzaCsrf, 'user', 'X', "x$s@prova.example")[0] === 403);
verifica('password corta rifiutata', $lettore->post('registrazione', ['nome' => 'A', 'cognome' => 'B', 'email' => "a$s@prova.example", 'password' => 'corta', 'privacy' => true])[0] === 422);
[$stato, $r] = registra($lettore, 'user', 'Lia', "Lettore$s@Prova.example");
verifica('registrazione user', $stato === 200, $r);
$riga = Database::riga('SELECT * FROM generalita WHERE email_hash = ?', [Cifratura::indiceEmail("lettore$s@prova.example")]);
verifica('email non in chiaro nel database', $riga !== null && !str_contains(implode('|', $riga), 'prova.example'));
verifica('email decifrabile con la chiave', Cifratura::decifra($riga['email_cifrata']) === "lettore$s@prova.example");
verifica('password salvata come Argon2id', str_starts_with($riga['password_hash'], '$argon2id$'));
verifica('email di verifica spedita', ultimoToken() !== '');
verifica('accesso negato prima della verifica', $lettore->post('accesso', ['email' => "lettore$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 403);
$token = ultimoToken();
verifica('token salvato solo come hash', Database::valore('SELECT COUNT(*) FROM token_email WHERE token_hash = ?', [$token]) === 0);
verifica('verifica email', $lettore->get("verifica-email?token=$token")[0] === 200);
verifica('token non riutilizzabile', $lettore->get("verifica-email?token=$token")[0] === 400);
[$stato, $r] = registra(new Cliente(), 'user', 'Doppio', "lettore$s@prova.example");
verifica('email doppia: stessa risposta, nessun secondo account', $stato === 200 && (int) Database::valore('SELECT COUNT(*) FROM generalita WHERE email_hash = ?', [$riga['email_hash']]) === 1, $r);
verifica('password errata rifiutata', $lettore->post('accesso', ['email' => "lettore$s@prova.example", 'password' => 'Password-sbagliata-9'])[0] === 401);
[$stato, $r] = $lettore->post('accesso', ['email' => "lettore$s@prova.example", 'password' => 'Password-di-prova-1']);
verifica('accesso user', $stato === 200 && $r['utente']['tipo'] === 'user', $r);
verifica('registrazione come tecnico impossibile', registra(new Cliente(), 'tecnico', 'T', "t$s@prova.example")[0] === 422);

echo "== Ruoli e approvazione ==\n";
putenv('PASSWORD_TECNICO=Password-di-prova-1');
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$radice/bin/crea-tecnico.php") . " Tina Tecnica tecnico$s@prova.example", $uscita, $codice);
verifica('creazione tecnico da terminale', $codice === 0, $uscita);
$tecnico = new Cliente();
verifica('accesso tecnico', $tecnico->post('accesso', ['email' => "tecnico$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 200);

$editore = new Cliente(); $politico = new Cliente(); $giornalista = new Cliente();
registra($editore, 'editore', 'Elio', "editore$s@prova.example", ['ragione_sociale' => 'Edizioni Prova']);
$editore->get('verifica-email?token=' . ultimoToken());
registra($politico, 'politico', 'Pia', "politico$s@prova.example", ['partito' => 'Lista Prova']);
$politico->get('verifica-email?token=' . ultimoToken());
verifica('editore non entra prima dell\'approvazione', $editore->post('accesso', ['email' => "editore$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 403);
verifica('un user non vede gli account in attesa', $lettore->get('tecnico/account-in-attesa')[0] === 403);
[, $r] = $tecnico->get('tecnico/account-in-attesa');
foreach ($r['account'] as $a) {
    $tecnico->post("tecnico/account/{$a['id']}", ['azione' => 'approva']);
}
verifica('accesso editore dopo approvazione', $editore->post('accesso', ['email' => "editore$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 200);
verifica('accesso politico dopo approvazione', $politico->post('accesso', ['email' => "politico$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 200);

echo "== Giornali e articoli ==\n";
[$stato, $r] = $editore->post('giornali', ['nome' => "Gazzetta $s", 'email' => "redazione$s@prova.example", 'prezzo_mensile_cent' => 500]);
verifica('editore crea giornale', $stato === 200, $r);
$giornale = $r['id'];
verifica('user non può creare giornali', $lettore->post('giornali', ['nome' => 'X', 'email' => 'x@prova.example'])[0] === 403);
registra($giornalista, 'giornalista', 'Gino', "giornalista$s@prova.example", ['giornale_id' => $giornale]);
$giornalista->get('verifica-email?token=' . ultimoToken());
[, $r] = $tecnico->get('tecnico/account-in-attesa');
$tecnico->post("tecnico/account/{$r['account'][0]['id']}", ['azione' => 'approva']);
verifica('accesso giornalista', $giornalista->post('accesso', ['email' => "giornalista$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 200);
[$stato, $r] = $giornalista->post("giornali/$giornale/articoli", ['titolo' => 'Primo pezzo', 'corpo' => 'Testo.', 'pubblica' => true]);
verifica('articolo del giornalista va in revisione', $stato === 200 && $r['stato'] === 'in_revisione', $r);
$articolo = $r['id'];
verifica('articolo non ancora pubblico', $lettore->get("giornali/$giornale/articoli")[1]['articoli'] === []);
verifica('il giornalista non può pubblicare', $giornalista->post("articoli-giornali/$articolo/pubblica")[0] === 403);
verifica('l\'editore pubblica', $editore->post("articoli-giornali/$articolo/pubblica")[0] === 200);
verifica('articolo ora pubblico con firma', ($lettore->get("giornali/$giornale/articoli")[1]['articoli'][0]['autore'] ?? '') === 'Gino Prova');
verifica('politico non scrive sul giornale', $politico->post("giornali/$giornale/articoli", ['titolo' => 'X', 'corpo' => 'Y'])[0] === 403);
[$stato, $r] = $politico->post('politici/articoli', ['titolo' => 'Il mio programma', 'corpo' => 'Testo.', 'pubblica' => true]);
verifica('articolo del politico', $stato === 200, $r);
$idPolitico = (int) Database::valore('SELECT p.id FROM politici p JOIN generalita g ON g.id = p.generalita_id WHERE g.email_hash = ?', [Cifratura::indiceEmail("politico$s@prova.example")]);
$idGiornalista = (int) Database::valore('SELECT id FROM giornalisti WHERE giornale_id = ?', [$giornale]);
verifica('articoli del politico visibili', count($lettore->get("politici/$idPolitico/articoli")[1]['articoli']) === 1);

echo "== Eventi ==\n";
[$stato, $r] = $politico->post('eventi', ['titolo' => 'Dibattito', 'inizio' => '2030-01-10T18:00:00+01:00', 'giornali_organizzatori' => [$giornale], 'pubblico' => false]);
verifica('politico crea evento con giornale co-organizzatore', $stato === 200, $r);
$evento = $r['id'];
verifica('co-organizzatore da confermare', (int) Database::valore('SELECT confermato FROM eventi_organizzatori WHERE evento_id = ? AND giornale_id = ?', [$evento, $giornale]) === 0);
verifica('editore non confermato non può invitare', $editore->post("eventi/$evento/inviti", ['giornalisti' => [$idGiornalista]])[0] === 403);
verifica('editore conferma la co-organizzazione', $editore->post("eventi/$evento/conferma")[0] === 200);
[$stato, $r] = $editore->post("eventi/$evento/inviti", ['giornalisti' => [$idGiornalista], 'politici' => [$idPolitico]]);
verifica('invito a giornalista e politico (con email)', $stato === 200 && $r['invitati'] === 2 && str_contains(file_get_contents("$radice/storage/email.log"), 'Invito: Dibattito'), $r);
verifica('user non può invitare', $lettore->post("eventi/$evento/inviti", ['politici' => [$idPolitico]])[0] === 403);
verifica('evento privato: user non invitato rifiutato', $lettore->post("eventi/$evento/partecipa")[0] === 403);
verifica('giornalista accetta l\'invito', $giornalista->post("eventi/$evento/risposta", ['accetta' => true])[0] === 200);
[, $r] = $editore->post('eventi', ['titolo' => 'Festa del giornale', 'inizio' => '2030-02-01 10:00', 'giornali_organizzatori' => [$giornale]]);
verifica('user partecipa a evento pubblico', $lettore->post("eventi/{$r['id']}/partecipa")[0] === 200);
verifica('politico partecipa a evento pubblico', $politico->post("eventi/{$r['id']}/partecipa")[0] === 200);
verifica('elenco eventi pubblici con organizzatori', ($lettore->get('eventi')[1]['eventi'][0]['organizzatori'][0]['nome'] ?? '') === "Gazzetta $s");
verifica('editore senza un suo giornale non crea eventi', $editore->post('eventi', ['titolo' => 'X', 'inizio' => '2030-02-01 10:00', 'giornali_organizzatori' => [999999]])[0] === 422);

echo "== Iscrizioni, ticket ==\n";
verifica('iscrizione al giornale', $lettore->post('iscrizioni', ['giornale_id' => $giornale])[0] === 200);
verifica('iscrizione al politico', $lettore->post('iscrizioni', ['politico_id' => $idPolitico])[0] === 200);
verifica('iscrizione senza destinatario rifiutata', $lettore->post('iscrizioni', [])[0] === 422);
verifica('due iscrizioni registrate', count($lettore->get('iscrizioni')[1]['iscrizioni']) === 2);
[$stato, $r] = $lettore->post('ticket', ['oggetto' => 'Non ricevo la newsletter', 'descrizione' => 'Da ieri.']);
verifica('user apre ticket', $stato === 200, $r);
$ticket = $r['id'];
verifica('un altro utente non vede il ticket', $politico->get("ticket/$ticket")[0] === 404);
verifica('solo il tecnico gestisce il ticket', $lettore->post("ticket/$ticket/gestione", ['stato' => 'chiuso'])[0] === 403);
verifica('tecnico prende in carico', $tecnico->post("ticket/$ticket/gestione", ['stato' => 'in_lavorazione'])[0] === 200);
verifica('tecnico risponde', $tecnico->post("ticket/$ticket/messaggi", ['testo' => 'Verifico subito.'])[0] === 200);
verifica('user legge la risposta', ($lettore->get("ticket/$ticket")[1]['ticket']['messaggi'][0]['ruolo'] ?? '') === 'tecnico');

echo "== Donazioni e abbonamenti (Stripe) ==\n";
verifica('importo con la virgola rifiutato', $lettore->post('donazioni', ['giornale_id' => $giornale, 'importo_centesimi' => 10.5])[0] === 422);
verifica('importo sotto il minimo rifiutato', $lettore->post('donazioni', ['giornale_id' => $giornale, 'importo_centesimi' => 1])[0] === 422);
[$stato, $r] = $lettore->post('donazioni', ['giornale_id' => $giornale, 'importo_centesimi' => 2500]);
verifica('donazione: restituisce l\'indirizzo di Stripe', $stato === 200 && str_starts_with($r['url_pagamento'] ?? '', 'https://checkout.stripe.com/'), $r);
$donazione = Database::riga('SELECT * FROM donazioni WHERE id = ?', [$r['donazione_id']]);
verifica('donazione in attesa finché Stripe non conferma', $donazione['stato'] === 'in_attesa');
$evento = ['id' => "evt_$s", 'type' => 'checkout.session.completed', 'data' => ['object' => [
    'id' => $donazione['stripe_checkout_session_id'], 'payment_status' => 'paid', 'amount_total' => 2500,
    'payment_intent' => "pi_$s", 'metadata' => ['tipo' => 'donazione', 'id' => (string) $donazione['id']],
]]];
$anonimo = new Cliente();
verifica('webhook con firma falsa rifiutato', webhook($anonimo, $evento, 'whsec_sbagliato')[0] === 400);
$manomesso = $evento; $manomesso['id'] = "evt_m$s"; $manomesso['data']['object']['amount_total'] = 100;
webhook($anonimo, $manomesso);
verifica('webhook con importo diverso non segna pagata', Database::valore('SELECT stato FROM donazioni WHERE id = ?', [$donazione['id']]) === 'in_attesa');
verifica('webhook firmato accettato', webhook($anonimo, $evento)[0] === 200);
verifica('donazione pagata', Database::valore('SELECT stato FROM donazioni WHERE id = ?', [$donazione['id']]) === 'pagata');
verifica('ricevuta inviata per email', str_contains(file_get_contents("$radice/storage/email.log"), 'Grazie per la tua donazione'));
verifica('stesso webhook ripetuto: ignorato', webhook($anonimo, $evento)[0] === 200 && (int) Database::valore('SELECT COUNT(*) FROM stripe_eventi WHERE id = ?', ["evt_$s"]) === 1);
verifica('donazione senza account al politico', $anonimo->post('donazioni', ['politico_id' => $idPolitico, 'importo_centesimi' => 1000])[0] === 200);
[$stato, $r] = $lettore->post('abbonamenti', ['giornale_id' => $giornale, 'piano' => 'mensile']);
verifica('abbonamento mensile: indirizzo di Stripe', $stato === 200, $r);
verifica('piano non offerto rifiutato', $lettore->post('abbonamenti', ['giornale_id' => $giornale, 'piano' => 'annuale'])[0] === 404);
$abb = Database::riga('SELECT * FROM abbonamenti WHERE id = ?', [$r['abbonamento_id']]);
webhook($anonimo, ['id' => "evt_a$s", 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => $abb['stripe_checkout_session_id'], 'customer' => "cus_$s", 'subscription' => "sub_$s", 'metadata' => ['tipo' => 'abbonamento', 'id' => (string) $abb['id']]]]]);
verifica('abbonamento attivo dopo il webhook', $lettore->get('abbonamenti')[1]['abbonamenti'][0]['stato'] === 'attivo');
webhook($anonimo, ['id' => "evt_b$s", 'type' => 'customer.subscription.deleted', 'data' => ['object' => ['id' => "sub_$s", 'status' => 'canceled']]]);
verifica('abbonamento annullato da Stripe', $lettore->get('abbonamenti')[1]['abbonamenti'][0]['stato'] === 'annullato');

echo "== Reset password e blocco ==\n";
$lettore->post('password/richiesta', ['email' => "lettore$s@prova.example"]);
verifica('reset: stessa risposta per email sconosciuta', $anonimo->post('password/richiesta', ['email' => "nessuno$s@prova.example"])[0] === 200);
verifica('nuova password', $anonimo->post('password/nuova', ['token' => ultimoToken(), 'password' => 'Nuova-password-22'])[0] === 200);
verifica('la vecchia sessione decade', $lettore->get('me')[0] === 401);
$nuovo = new Cliente();
verifica('accesso con la nuova password', $nuovo->post('accesso', ['email' => "lettore$s@prova.example", 'password' => 'Nuova-password-22'])[0] === 200);
verifica('uscita', $nuovo->post('uscita')[0] === 200 && $nuovo->get('me')[0] === 401);
$forza = new Cliente();
for ($i = 0; $i < 6; $i++) {
    $forza->post('accesso', ['email' => "politico$s@prova.example", 'password' => "Tentativo-errato-$i"]);
}
verifica('account bloccato dopo troppi errori (anche con la password giusta)', $forza->post('accesso', ['email' => "politico$s@prova.example", 'password' => 'Password-di-prova-1'])[0] === 401);

echo PHP_EOL . ($falliti === 0 ? 'TUTTE LE PROVE SUPERATE' : "PROVE FALLITE: $falliti") . PHP_EOL;
exit($falliti === 0 ? 0 : 1);
