<?php
/**
 * Prova completa di portale e sito collegati al database.
 *
 * Preparazione (database vuoto di prova, MAI quello vero):
 *   1. caricare backend/sql/schema.sql (dentro wolfpress-php)
 *   2. in backend/php/.env: MAIL_DRIVER=log, STRIPE_API_BASE=http://127.0.0.1:8092,
 *      STRIPE_SEGRETO_WEBHOOK=whsec_prova, PORTALE_URL=http://127.0.0.1:8080/portale,
 *      SITO_URL=http://127.0.0.1:8080/sito
 *   3. PASSWORD_DEMO=Password-demo-2026 php bin/importa-demo.php
 *   4. php tests/prova.php
 *
 * Avvia da sola il server PHP e una finta API Stripe.
 */
declare(strict_types=1);

$radice = dirname(__DIR__);
$backend = (require "$radice/app/config.php")['backend'];
$registroEmail = "$backend/storage/email.log";
$registroServer = "$backend/storage/wolfpress-server.log";
@unlink($registroEmail);
$tubi = [];
$ambiente = ['NO_PROXY' => '127.0.0.1', 'no_proxy' => '127.0.0.1'] + getenv();
$server = proc_open([PHP_BINARY, '-d', 'log_errors=1', '-S', '127.0.0.1:8080', 'router.php'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', $registroServer, 'w']], $tubi, $radice, $ambiente);
$stripe = proc_open([PHP_BINARY, '-S', '127.0.0.1:8092', "$backend/tests/finta-stripe.php"], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $tubi);
register_shutdown_function(static function () use ($server, $stripe) { proc_terminate($server); proc_terminate($stripe); });
usleep(700000);

require "$backend/src/bootstrap.php";
Database::esegui('DELETE FROM tentativi');   // la prova si può ripetere senza incappare nel limite per IP

$falliti = 0;
function verifica(string $cosa, bool $ok, mixed $dettaglio = null): void
{
    global $falliti;
    echo ($ok ? '  ok   ' : '  ERRORE ') . $cosa . ($ok ? '' : ' → ' . mb_substr(is_string($dettaglio) ? trim(strip_tags($dettaglio)) : json_encode($dettaglio), 0, 300)) . PHP_EOL;
    $falliti += $ok ? 0 : 1;
}

/** Un browser con i suoi cookie: compila i form leggendo il codice CSRF dalla pagina. */
final class Browser
{
    private string $cookie;
    public int $stato = 0;
    public string $indirizzo = '';
    public string $intestazioni = '';

    public function __construct()
    {
        $this->cookie = tempnam(sys_get_temp_dir(), 'wp');
    }

    public function get(string $percorso, bool $segui = true): string
    {
        return $this->chiama($percorso, null, $segui);
    }

    /** Apre $pagina, ne legge il codice CSRF e invia il form a $azione. */
    public function post(string $pagina, string $azione, array $campi, bool $segui = true): string
    {
        preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $this->get($pagina), $m);
        return $this->chiama($azione, ['_csrf' => $m[1] ?? ''] + $campi, $segui);
    }

    /**
     * Nella prova le pagine sono indicate come "portale/index.php?r=controller/azione&...":
     * qui diventano l'indirizzo vero del file .php, con la stessa regola usata dall'applicazione
     * (tabelle sito/pagine.php e portale/pagine.php).
     */
    public static function indirizzo(string $percorso): string
    {
        global $radice;
        static $tabelle = [];
        if (!preg_match('#^(sito|portale)/index\.php\?r=([a-z-]+)(?:/([a-z-]+))?(?:&(.*))?$#', $percorso, $m)) {
            return $percorso;
        }
        [, $fronte, $controller] = $m;
        $azione = ($m[3] ?? '') !== '' ? $m[3] : 'index';
        $metodo = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $azione))));
        $tabelle[$fronte] ??= require "$radice/$fronte/pagine.php";
        $file = null;
        $query = $m[4] ?? '';
        foreach ($tabelle[$fronte] as $nome => [$c, $a]) {
            if ($c === $controller && $a === $metodo) {
                $file = $nome;
                break;
            }
        }
        if ($file === null) {
            foreach ($tabelle[$fronte] as $nome => [$c]) {
                if ($c === $controller) {
                    $file = $nome;
                    $query = "azione=$azione" . ($query !== '' ? "&$query" : '');
                    break;
                }
            }
        }
        return "$fronte/" . ($file ?? 'index') . '.php' . ($query !== '' ? "?$query" : '');
    }

    public function chiama(string $percorso, ?array $campi, bool $segui, array $intestazioni = [], ?string $grezzo = null, bool $conFile = false): string
    {
        $c = curl_init('http://127.0.0.1:8080/' . self::indirizzo($percorso));
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->cookie, CURLOPT_COOKIEFILE => $this->cookie,
            CURLOPT_FOLLOWLOCATION => $segui, CURLOPT_PROXY => '', CURLOPT_HEADER => true, CURLOPT_HTTPHEADER => $intestazioni,
        ]);
        if ($campi !== null || $grezzo !== null) {
            // Con un file allegato curl invia il form come multipart; altrimenti come normale form.
            curl_setopt($c, CURLOPT_POSTFIELDS, $grezzo ?? ($conFile ? $campi : http_build_query($campi)));
        }
        $risposta = (string) curl_exec($c);
        $this->stato = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
        $this->indirizzo = (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
        $taglio = (int) curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $this->intestazioni = substr($risposta, 0, $taglio);
        curl_close($c);
        return substr($risposta, $taglio);
    }

    public function entra(string $fronte, string $email, string $password): string
    {
        return $fronte === 'portale'
            ? $this->post('portale/index.php?r=accesso', 'portale/index.php?r=accesso/entra', ['email' => $email, 'password' => $password])
            : $this->post('sito/index.php?r=account', 'sito/index.php?r=account/entra', ['email' => $email, 'password' => $password]);
    }
}

function ultimoLink(string $azione): string
{
    global $registroEmail;
    preg_match_all('#http://127\.0\.0\.1:8080/(\S+azione=' . preg_quote($azione, '#') . '&token=[a-f0-9]{64})#', (string) @file_get_contents($registroEmail), $m);
    return (string) end($m[1]);
}

const PW = 'Password-demo-2026';
$s = bin2hex(random_bytes(3));

echo "== Sito pubblico (dati dal database) ==\n";
$visitatore = new Browser();
$home = $visitatore->get('sito/index.php');
verifica('home con gli articoli del database', $visitatore->stato === 200 && str_contains($home, 'La settimana in cinque notizie'), $home);
verifica('le pagine di lettura non creano cookie', !str_contains(strtolower($visitatore->intestazioni), 'set-cookie'));
foreach (['notizie', 'notizie&sezione=Politica', 'notizie&testata=wolfpress', 'notizie/articolo&slug=la-settimana-in-cinque-notizie', 'testate', 'politica',
    'politica/partito&id=alleanza-valdora', 'politica/profilo&livello=comune', 'politica/profilo&livello=senato&tipo=mandato', 'ricerca&q=Valleri',
    'pagine/chi-siamo', 'pagine/privacy', 'account', 'account/registrati', 'account/password', 'sostieni&testata=wolfpress', 'sostieni&politico=comune'] as $pagina) {
    $html = $visitatore->get("sito/index.php?r=$pagina");
    verifica("sito: $pagina", $visitatore->stato === 200, $html);
}
verifica('scheda politico dal database', str_contains($visitatore->get('sito/index.php?r=politica/profilo&livello=comune'), 'Ada Valleri'));
verifica('ricerca trova il politico', str_contains($visitatore->get('sito/index.php?r=ricerca&q=Valleri'), 'Ada Valleri'));
verifica('cartelle interne non raggiungibili', $visitatore->get('bin/importa-demo.php') === 'Accesso negato' && $visitatore->get('app/config.php') === 'Accesso negato'
    && $visitatore->get('backend/php/.env') === 'Accesso negato' && $visitatore->get('sito/controllers/HomeController.php') === 'Accesso negato' && $visitatore->get('portale/parti/topbar.php') === 'Accesso negato');
$visitatore->get('sito/header.php');
verifica('header, menu e footer non si aprono da soli', $visitatore->stato === 404 && $visitatore->get('portale/menu.php') === '' && $visitatore->get('sito/footer.php') === '');
foreach (['sito/index.php', 'sito/notizie.php', 'sito/login.php', 'sito/registrazione.php', 'portale/login.php', 'portale/registrazione.php'] as $file) {
    $html = $visitatore->get($file);
    verifica("$file: pagina completa con header, menu e footer", $visitatore->stato === 200 && str_starts_with($html, '<!doctype html>') && str_contains($html, '<title>')
        && preg_match('/<(nav|aside)\b/', $html) === 1 && str_contains($html, '<footer') && str_contains($html, '</html>'), $html);
}
$visitatore->get('sito/non-esiste.php');
verifica('pagina inesistente: 404', $visitatore->stato === 404);
$html = $visitatore->get('sito/notizie.php?azione=non-esiste');
verifica('azione inesistente: pagina di errore 404 con menu e footer', $visitatore->stato === 404 && str_contains($html, '<footer'), $html);

echo "== Portale: accesso ==\n";
$editore = new Browser();
$editore->get('portale/index.php', false);
verifica('senza accesso si viene mandati al login', $editore->stato === 303 && str_contains($editore->intestazioni, 'login.php'));
verifica('password sbagliata rifiutata', str_contains($editore->entra('portale', 'laura.monti@wolfpress.example', 'Password-sbagliata'), 'Email o password non corretti'));
verifica('accesso senza codice CSRF rifiutato', $editore->chiama('portale/index.php?r=accesso/entra', ['email' => 'laura.monti@wolfpress.example', 'password' => PW], true) !== '' && $editore->stato === 400);
$html = $editore->entra('portale', 'laura.monti@wolfpress.example', PW);
verifica('accesso editore', str_contains($html, 'Bentornato, Laura Monti'), $html);
foreach (['home', 'configuratore', 'cliente', 'editore', 'editore/nuovo', 'articoli/nuovo', 'assistenza', 'assistenza/chat', 'assistenza&nuovo=1'] as $pagina) {
    $html = $editore->get("portale/index.php?r=$pagina");
    verifica("editore: $pagina", $editore->stato === 200, $html);
}
$editore->get('portale/index.php?r=tecnico');
verifica('editore non entra nell\'area tecnica', $editore->stato === 403);

echo "== Redazione ==\n";
$html = $editore->post('portale/index.php?r=articoli/nuovo', 'portale/index.php?r=articoli/salva', ['id' => '', 'titolo' => "Articolo di prova $s", 'sezione' => 'Attualità', 'sommario' => 'Sommario.', 'corpo' => 'Testo di prova.', 'stato' => 'Pubblicato']);
verifica('editore pubblica un articolo', str_contains($html, 'Articolo pubblicato'), $html);
verifica('l\'articolo compare sul sito', str_contains($visitatore->get('sito/index.php?r=notizie'), "Articolo di prova $s"));
$html = $editore->post('portale/index.php?r=editore/nuovo', 'portale/index.php?r=editore/salva', ['id' => '', 'nome' => 'Sara Bianchi', 'email' => "sara$s@prova.example", 'ruolo' => 'Giornalista', 'sezione' => 'Cultura', 'stato' => 'Attivo', 'bio' => '']);
verifica('editore crea un giornalista (email di invito)', str_contains($html, 'riceverà un') && ultimoLink('nuova-password') !== '', $html);
verifica('email del giornalista cifrata nel database', (int) Database::valore("SELECT COUNT(*) FROM generalita WHERE email_cifrata LIKE '%prova.example%'") === 0);
$giornalista = new Browser();
verifica('il giornalista non entra finché non sceglie la password', str_contains($giornalista->entra('portale', "sara$s@prova.example", PW), 'Email o password non corretti'));
$link = ultimoLink('nuova-password');
preg_match('/token=([a-f0-9]{64})/', $link, $t);
$html = $giornalista->post($link, 'portale/index.php?r=accesso/salva-password', ['token' => $t[1], 'password' => PW, 'password2' => PW]);
verifica('il giornalista sceglie la password dal link', str_contains($html, 'Password salvata'), $html);
verifica('accesso giornalista', str_contains($giornalista->entra('portale', "sara$s@prova.example", PW), 'Bentornato, Sara Bianchi'));
foreach (['home', 'giornalista', 'articoli', 'articoli/nuovo'] as $pagina) {
    $html = $giornalista->get("portale/index.php?r=$pagina");
    verifica("giornalista: $pagina", $giornalista->stato === 200, $html);
}
$html = $giornalista->post('portale/index.php?r=articoli/nuovo', 'portale/index.php?r=articoli/salva', ['id' => '', 'titolo' => "Pezzo in revisione $s", 'sezione' => 'Cultura', 'sommario' => '', 'corpo' => 'Testo.', 'stato' => 'Pubblicato']);
verifica('il giornalista non può pubblicare: resta bozza', str_contains($html, 'Bozza salvata') && !str_contains($visitatore->get('sito/index.php?r=notizie'), "Pezzo in revisione $s"), $html);
$altrui = (string) Database::valore("SELECT id FROM articoli_giornali WHERE titolo = ?", ["Articolo di prova $s"]);
$giornalista->get("portale/index.php?r=articoli/modifica&id=$altrui");
verifica('il giornalista non apre gli articoli altrui', $giornalista->stato === 404);
$html = $giornalista->post('portale/index.php?r=giornalista', 'portale/index.php?r=giornalista/evento', ['titolo' => "Conferenza $s", 'data' => '2030-05-10', 'ora' => '11:30', 'luogo' => 'Parma']);
verifica('evento in agenda (tabelle eventi)', str_contains($html, "Conferenza $s") && str_contains($html, '11:30'), $html);
$html = $giornalista->post('portale/index.php?r=giornalista', 'portale/index.php?r=giornalista/messaggio', ['canale' => 'Redazione generale', 'testo' => "Ciao redazione $s"]);
verifica('chat di redazione', str_contains($html, "Ciao redazione $s") && str_contains($editore->get('portale/index.php?r=editore'), "Ciao redazione $s"), $html);
$html = $editore->post('portale/index.php?r=configuratore', 'portale/index.php?r=configuratore/salva', ['testata' => 'WolfPress', 'cliente' => 'Wolf Media', 'colore' => '#123456', 'benvenuto' => "Benvenuti $s", 'email_assistenza' => '', 'moduli' => ['articoli', 'assistenza']]);
verifica('configuratore salvato nel database', str_contains($html, 'Configurazione salvata') && str_contains((string) Database::valore("SELECT impostazioni FROM giornali WHERE slug = 'wolfpress'"), "Benvenuti $s"), $html);

echo "== Assistenza e tecnici ==\n";
$html = $editore->post('portale/index.php?r=assistenza&nuovo=1', 'portale/index.php?r=assistenza/crea', ['mittente' => '', 'oggetto' => "Guasto $s", 'priorita' => 'Alta', 'descrizione' => 'Il sito è lento.']);
verifica('editore apre un ticket', str_contains($html, 'aperto: il team tecnico'), $html);
$codice = (string) Database::valore('SELECT codice FROM ticket WHERE oggetto = ?', ["Guasto $s"]);
$tecnico = new Browser();
verifica('accesso tecnico', str_contains($tecnico->entra('portale', 'nadia.venturi@wolfpress.example', PW), 'Bentornato, Nadia Venturi'));
foreach (['home', 'tecnico', 'tecnico/personale', 'tecnico/personale&id=1', 'tecnico/account', 'tecnico/clienti', 'tecnico/referente', 'tecnico/ticket', 'tecnico/chat', "tecnico/ticket&id=$codice"] as $pagina) {
    $html = $tecnico->get("portale/index.php?r=$pagina");
    verifica("tecnico: $pagina", $tecnico->stato === 200, $html);
}
$html = $tecnico->post("portale/index.php?r=tecnico/ticket&id=$codice", 'portale/index.php?r=tecnico/prendi', ['id' => $codice]);
verifica('tecnico prende in carico', str_contains($html, 'Hai preso in carico'), $html);
$tecnico->post("portale/index.php?r=tecnico/chat&id=$codice", 'portale/index.php?r=tecnico/messaggio', ['id' => $codice, 'testo' => "Stiamo verificando $s"]);
$html = $editore->get("portale/index.php?r=assistenza/chat&id=$codice");
verifica('l\'editore legge la risposta e l\'assegnatario', str_contains($html, "Stiamo verificando $s") && str_contains($editore->get("portale/index.php?r=assistenza&id=$codice"), 'Nadia Venturi'), $html);
$html = $tecnico->post('portale/index.php?r=tecnico', 'portale/index.php?r=tecnico/salva-stato', ['id' => 'wolfpress', 'stato' => 'Manutenzione', 'incidente' => "Aggiornamento $s"]);
verifica('stato del servizio salvato', str_contains($html, 'Manutenzione') && str_contains((string) Database::valore("SELECT dati FROM giornali WHERE slug = 'wolfpress'"), "Aggiornamento $s"), $html);
$html = $tecnico->post('portale/index.php?r=tecnico/personale', 'portale/index.php?r=tecnico/salva-operatore', ['id' => '', 'nome' => 'Ugo Neri', 'email' => "ugo$s@prova.example", 'ruolo' => 'Sviluppatore', 'turno' => 'Notte', 'stato' => 'Attivo']);
verifica('tecnico crea un operatore', str_contains($html, 'Ugo Neri') && str_contains($html, 'riceverà un'), $html);

echo "== Registrazione dal portale e approvazione ==\n";
$politico = new Browser();
$html = $politico->post('portale/index.php?r=accesso/registrazione&tipo=politico', 'portale/index.php?r=accesso/registra', ['tipo' => 'politico', 'nome' => 'Pia', 'cognome' => "Verdi$s", 'email' => "pia$s@prova.example", 'livello' => 'regione', 'partito' => 'Lista Prova', 'carica' => 'Candidata', 'territorio' => 'Emilia', 'password' => PW, 'password2' => PW, 'privacy' => '1']);
verifica('registrazione politico', str_contains($html, 'Controlla la tua casella di posta'), $html);
$html = $politico->post('portale/index.php?r=accesso/registrazione', 'portale/index.php?r=accesso/registra', ['tipo' => 'tecnico', 'nome' => 'X', 'cognome' => 'Y', 'email' => "x$s@prova.example", 'password' => PW, 'password2' => PW, 'privacy' => '1']);
verifica('dal portale non ci si registra come tecnico', str_contains($html, 'Scegli il tipo di account'), $html);
verifica('conferma dell\'email dal link', str_contains($politico->get(ultimoLink('verifica')), 'in attesa di approvazione'));
verifica('accesso negato prima dell\'approvazione', str_contains($politico->entra('portale', "pia$s@prova.example", PW), 'in attesa di approvazione'));
verifica('la scheda non è ancora pubblica', !str_contains($visitatore->get('sito/index.php?r=politica'), "Verdi$s"));
$id = (string) Database::valore('SELECT id FROM generalita WHERE cognome = ?', ["Verdi$s"]);
$html = $tecnico->post('portale/index.php?r=tecnico/account', 'portale/index.php?r=tecnico/approva', ['id' => $id, 'azione' => 'approva']);
verifica('il tecnico approva', str_contains($html, 'Account approvato'), $html);
verifica('accesso politico dopo l\'approvazione', str_contains($politico->entra('portale', "pia$s@prova.example", PW), "Bentornato, Pia Verdi$s"));
foreach (['home', 'politico', 'politico&scenario=mandato'] as $pagina) {
    $html = $politico->get("portale/index.php?r=$pagina");
    verifica("politico: $pagina", $politico->stato === 200, $html);
}
$html = $politico->post('portale/index.php?r=politico', 'portale/index.php?r=politico/aggiungi-voce', ['scenario' => 'candidatura', 'titolo' => "Proposta $s", 'testo' => 'Dettagli.']);
verifica('il politico aggiunge una proposta', str_contains($html, "Proposta $s"), $html);
$slug = (string) Database::valore('SELECT p.slug FROM politici p JOIN generalita g ON g.id = p.generalita_id WHERE g.cognome = ?', ["Verdi$s"]);
verifica('la proposta compare sul sito', str_contains($visitatore->get("sito/index.php?r=politica/profilo&livello=$slug"), "Proposta $s"));

$nuovoEditore = new Browser();
$nuovoEditore->post('portale/index.php?r=accesso/registrazione&tipo=editore', 'portale/index.php?r=accesso/registra', ['tipo' => 'editore', 'nome' => 'Rita', 'cognome' => "Blu$s", 'email' => "rita$s@prova.example", 'ragione_sociale' => 'Edizioni Blu', 'password' => PW, 'password2' => PW, 'privacy' => '1']);
$nuovoEditore->get(ultimoLink('verifica'));
$tecnico->post('portale/index.php?r=tecnico/account', 'portale/index.php?r=tecnico/approva', ['id' => (string) Database::valore('SELECT id FROM generalita WHERE cognome = ?', ["Blu$s"]), 'azione' => 'approva']);
$nuovoEditore->entra('portale', "rita$s@prova.example", PW);
$html = $nuovoEditore->get('portale/index.php?r=cliente');
verifica('nuovo editore: deve prima creare la testata', str_contains($html, 'Crea la tua testata'), $html);
$html = $nuovoEditore->post('portale/index.php?r=testata', 'portale/index.php?r=testata/crea', ['nome' => "Corriere $s", 'email' => "redazione$s@prova.example", 'descrizione' => 'Nuovo giornale.']);
verifica('testata creata e configuratore aperto', str_contains($html, 'Testata creata') && str_contains($html, "Corriere $s"), $html);
verifica('la nuova testata compare sul sito', str_contains($visitatore->get('sito/index.php?r=testate'), "Corriere $s"));
$html = $nuovoEditore->get('portale/index.php?r=cliente');
verifica('il nuovo editore apre la sua area e non vede gli articoli di WolfPress', $nuovoEditore->stato === 200 && !str_contains($html, "Articolo di prova $s"), $html);
foreach (['editore', 'assistenza', 'configuratore', 'articoli/nuovo'] as $pagina) {
    $html = $nuovoEditore->get("portale/index.php?r=$pagina");
    verifica("nuovo editore: $pagina", $nuovoEditore->stato === 200, $html);
}
$nuovoEditore->get("portale/index.php?r=articoli/modifica&id=$altrui");
verifica('né può modificarli', $nuovoEditore->stato === 404);
$html = $nuovoEditore->get("portale/index.php?r=assistenza&id=$codice");
verifica('né vede i ticket altrui', $nuovoEditore->stato === 200 && !str_contains($html, "Guasto $s"), $html);

echo "== Lettori: account, iscrizioni, donazioni ==\n";
$lettore = new Browser();
$html = $lettore->post('sito/index.php?r=account/registrati', 'sito/index.php?r=account/registra', ['nome' => 'Lia', 'cognome' => "Rossi$s", 'email' => "lia$s@prova.example", 'password' => PW, 'password2' => PW, 'privacy' => '1']);
verifica('registrazione lettore dal sito', str_contains($html, 'Controlla la tua casella di posta'), $html);
verifica('conferma dell\'email dal link del sito', str_contains($lettore->get(ultimoLink('verifica')), 'ora puoi accedere'));
$html = $lettore->entra('sito', "lia$s@prova.example", PW);
verifica('accesso lettore', str_contains($html, "Lia Rossi$s") && str_contains($html, "lia$s@prova.example"), $html);
$lettore->get('portale/index.php?r=home', false);
verifica('il lettore non entra nel portale', $lettore->stato === 303 && str_contains($lettore->intestazioni, 'sito/account.php'));
$html = $lettore->post('sito/index.php?r=sostieni&testata=wolfpress', 'sito/index.php?r=sostieni/segui', ['testata' => 'wolfpress']);
verifica('il lettore segue una testata', str_contains($html, 'Ora segui WolfPress') && str_contains($html, 'Smetti di seguire'), $html);
$html = $lettore->post('sito/index.php?r=sostieni&politico=comune', 'sito/index.php?r=sostieni/dona', ['politico' => 'comune', 'importo' => '0,10']);
verifica('donazione sotto il minimo rifiutata', str_contains($html, 'importo_centesimi deve essere'), $html);
$lettore->post('sito/index.php?r=sostieni&testata=wolfpress', 'sito/index.php?r=sostieni/dona', ['testata' => 'wolfpress', 'importo' => '12,50', 'messaggio' => 'Forza!'], false);
verifica('donazione: rimando alla pagina di Stripe', $lettore->stato === 303 && preg_match('#Location: https://checkout\.stripe\.com/#i', $lettore->intestazioni) === 1, $lettore->intestazioni);
$donazione = Database::riga('SELECT * FROM donazioni ORDER BY id DESC LIMIT 1');
verifica('donazione registrata in attesa, 1250 centesimi', $donazione['stato'] === 'in_attesa' && (int) $donazione['importo_centesimi'] === 1250, $donazione);
$evento = json_encode(['id' => "evt_w$s", 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => $donazione['stripe_checkout_session_id'], 'payment_status' => 'paid', 'amount_total' => 1250, 'payment_intent' => "pi_w$s", 'metadata' => ['tipo' => 'donazione', 'id' => (string) $donazione['id']]]]]);
$ora = time();
$stripeFinto = new Browser();
$stripeFinto->chiama('sito/stripe-webhook.php', null, false, ['Stripe-Signature: t=' . $ora . ',v1=' . hash_hmac('sha256', "$ora.$evento", 'whsec_falso')], $evento);
verifica('webhook con firma falsa rifiutato', $stripeFinto->stato === 400 && Database::valore('SELECT stato FROM donazioni WHERE id = ?', [$donazione['id']]) === 'in_attesa');
$stripeFinto->chiama('sito/stripe-webhook.php', null, false, ['Stripe-Signature: t=' . $ora . ',v1=' . hash_hmac('sha256', "$ora.$evento", 'whsec_prova')], $evento);
verifica('webhook firmato: donazione pagata', $stripeFinto->stato === 200 && Database::valore('SELECT stato FROM donazioni WHERE id = ?', [$donazione['id']]) === 'pagata');
$html = $lettore->get('sito/index.php?r=account');
verifica('il lettore vede donazione e iscrizione nel suo account', str_contains($html, '12,50') && str_contains($html, 'WolfPress'), $html);
verifica('ricevuta inviata per email', str_contains((string) file_get_contents($registroEmail), 'Grazie per la tua donazione'));
$html = $lettore->post('sito/index.php?r=account', 'sito/index.php?r=account/esci', []);
verifica('uscita', !str_contains($lettore->get('sito/index.php?r=account'), "Rossi$s"));

echo "== Eventi: organizzatori, inviti, partecipanti ==\n";
verifica('agenda del politico letta dalle tabelle degli eventi', str_contains($visitatore->get('sito/index.php?r=politica/profilo&livello=comune'), 'Passeggiata di quartiere'));
$ada = new Browser();
$ada->entra('portale', 'ada.valleri@wolfpress.example', PW);
$idTestata = (string) Database::valore("SELECT id FROM giornali WHERE slug = 'wolfpress'");
$idGiulia = (string) Database::valore("SELECT gi.id FROM giornalisti gi JOIN generalita g ON g.id = gi.generalita_id WHERE g.cognome = 'Serra'");
$idElio = (string) Database::valore("SELECT p.id FROM politici p JOIN generalita g ON g.id = p.generalita_id WHERE g.cognome = 'Selvani'");
foreach ([[$ada, 'politico'], [$editore, 'editore'], [$giornalista, 'giornalista']] as [$chi, $ruolo]) {
    $html = $chi->get('portale/index.php?r=eventi');
    verifica("eventi: pagina per $ruolo", $chi->stato === 200, $html);
}
$tecnico->get('portale/index.php?r=eventi');
verifica('il tecnico non ha la pagina eventi', $tecnico->stato === 403);
$html = $giornalista->post('portale/index.php?r=eventi', 'portale/index.php?r=eventi/crea', ['titolo' => 'X', 'data' => '2031-01-01']);
verifica('un giornalista non può organizzare eventi', str_contains($html, 'Non hai i permessi'), $html);
$html = $ada->post('portale/index.php?r=eventi', 'portale/index.php?r=eventi/crea', ['titolo' => "Dibattito $s", 'data' => '2031-03-12', 'ora' => '18:30', 'luogo' => 'Teatro civico', 'descrizione' => 'Confronto pubblico.', 'pubblico' => '1', 'testate' => [$idTestata]]);
verifica('politico crea un evento con una testata co-organizzatrice', str_contains($html, 'Evento creato') && str_contains($html, 'Da confermare'), $html);
$idEvento = (string) Database::valore('SELECT id FROM eventi WHERE titolo = ?', ["Dibattito $s"]);
$html = $visitatore->get('sito/index.php?r=eventi');
verifica('sul sito: evento pubblico, solo organizzatori confermati', $visitatore->stato === 200 && str_contains($html, "Dibattito $s") && str_contains($html, '<strong>Ada Valleri</strong>'), $html);
$html = $editore->post("portale/index.php?r=eventi/scheda&id=$idEvento", 'portale/index.php?r=eventi/invita', ['id' => $idEvento, 'giornalisti' => [$idGiulia]]);
verifica('co-organizzatore non confermato: non può invitare', str_contains($html, 'Solo un organizzatore confermato'), $html);
$html = $editore->post("portale/index.php?r=eventi/scheda&id=$idEvento", 'portale/index.php?r=eventi/conferma', ['id' => $idEvento]);
verifica('la testata conferma la co-organizzazione', str_contains($html, 'Ora sei tra gli organizzatori'), $html);
verifica('sul sito ora compaiono entrambi gli organizzatori', str_contains($visitatore->get('sito/index.php?r=eventi'), '<strong>Ada Valleri, WolfPress</strong>'));
$html = $editore->post("portale/index.php?r=eventi/scheda&id=$idEvento", 'portale/index.php?r=eventi/invita', ['id' => $idEvento, 'giornalisti' => [$idGiulia], 'politici' => [$idElio]]);
verifica('inviti a un giornalista e a un politico, con email', str_contains($html, 'Inviti spediti per email: 2') && str_contains((string) file_get_contents($registroEmail), "Invito: Dibattito $s"), $html);
$giulia = new Browser();
$giulia->entra('portale', 'giulia.serra@wolfpress.example', PW);
$html = $giulia->get('portale/index.php?r=eventi');
verifica('la giornalista vede l\'invito', str_contains($html, "Dibattito $s") && str_contains($html, 'Accetta'), $html);
$html = $giulia->post('portale/index.php?r=eventi', 'portale/index.php?r=eventi/rispondi', ['id' => $idEvento, 'risposta' => 'accetta']);
verifica('accetta l\'invito e l\'evento entra nella sua agenda', str_contains($html, 'Invito accettato') && str_contains($giulia->get('portale/index.php?r=giornalista'), "Dibattito $s"), $html);
$elio = new Browser();
$elio->entra('portale', 'elio.selvani@wolfpress.example', PW);
$html = $elio->post('portale/index.php?r=eventi', 'portale/index.php?r=eventi/rispondi', ['id' => $idEvento, 'risposta' => 'rifiuta']);
verifica('il politico invitato rifiuta', str_contains($html, 'Invito rifiutato'), $html);
$html = $ada->get("portale/index.php?r=eventi/scheda&id=$idEvento");
verifica('scheda evento: stati degli inviti e partecipanti', $ada->stato === 200 && str_contains($html, 'Giulia Serra') && str_contains($html, 'Accettato') && str_contains($html, 'Rifiutato'), $html);
$lettore->entra('sito', "lia$s@prova.example", PW);
$html = $lettore->post('sito/index.php?r=eventi', 'sito/index.php?r=eventi/partecipa', ['id' => $idEvento]);
verifica('il lettore partecipa dal sito', str_contains($html, 'Partecipazione registrata') && str_contains($lettore->get('sito/index.php?r=account'), "Dibattito $s"), $html);
verifica('tre tipi di partecipanti nelle tabelle', (int) Database::valore('SELECT COUNT(*) FROM eventi_partecipanti WHERE evento_id = ?', [$idEvento]) === 2 && str_contains($ada->get("portale/index.php?r=eventi/scheda&id=$idEvento"), 'Lettore'));
$html = $editore->post('portale/index.php?r=eventi', 'portale/index.php?r=eventi/crea', ['titolo' => "Riunione riservata $s", 'data' => '2031-04-01', 'politici' => [$idElio]]);
verifica('la testata crea un evento riservato con un politico co-organizzatore', str_contains($html, 'Evento creato'), $html);
$idRiservato = (string) Database::valore('SELECT id FROM eventi WHERE titolo = ?', ["Riunione riservata $s"]);
verifica('l\'evento riservato non è sul sito', !str_contains($visitatore->get('sito/index.php?r=eventi'), "Riunione riservata $s"));
$html = $lettore->post('sito/index.php?r=account', 'sito/index.php?r=eventi/partecipa', ['id' => $idRiservato]);
verifica('il lettore non può partecipare a un evento riservato', str_contains($html, 'riservato agli invitati'), $html);
$ada->get("portale/index.php?r=eventi/scheda&id=$idRiservato");
verifica('chi non c\'entra non apre la scheda riservata', $ada->stato === 404);
verifica('il politico indicato la trova tra i suoi, da confermare', str_contains($elio->get('portale/index.php?r=eventi'), "Riunione riservata $s"));
$html = $politico->post('portale/index.php?r=politico', 'portale/index.php?r=politico/aggiungi-evento', ['scenario' => 'candidatura', 'data' => '2031-05-05', 'ora' => '10:00', 'titolo' => "Banchetto $s", 'dettaglio' => 'Piazza Grande']);
verifica('agenda del politico: nuovo appuntamento = evento pubblico', str_contains($html, "Banchetto $s") && str_contains($visitatore->get('sito/index.php?r=eventi'), "Banchetto $s"), $html);
$html = $ada->post("portale/index.php?r=eventi/scheda&id=$idEvento", 'portale/index.php?r=eventi/annulla', ['id' => $idEvento]);
verifica('evento annullato: sparisce dal sito', str_contains($html, 'Evento annullato') && !str_contains($visitatore->get('sito/index.php?r=eventi'), "Dibattito $s"), $html);

echo "== Partiti e articoli dei politici ==\n";
$html = $tecnico->post('portale/index.php?r=tecnico/partiti', 'portale/index.php?r=tecnico/salva-partito', ['id' => '', 'nome' => "Lista Civica $s", 'sezione' => 'Sezione cittadina', 'descrizione' => 'Lista di prova.', 'rete' => "Centro | Circolo del centro\nNord | Circolo nord", 'orari' => 'Lun 9-12', 'email' => "lista$s@prova.example"]);
verifica('il tecnico crea un partito', $tecnico->stato === 200 && str_contains($html, 'salvato'), $html);
$slugPartito = (string) Database::valore('SELECT slug FROM partiti WHERE nome = ?', ["Lista Civica $s"]);
$html = $visitatore->get("sito/index.php?r=politica/partito&id=$slugPartito");
verifica('il partito compare sul sito con i circoli', $visitatore->stato === 200 && str_contains($html, 'Circolo nord'), $html);
$html = $politico->post('portale/index.php?r=politico', 'portale/index.php?r=politico/salva', ['scenario' => 'candidatura', 'ruolo' => 'Candidata', 'luogo' => 'Emilia', 'bio' => 'Bio.', 'contatto' => '', 'email' => '', 'partito' => $slugPartito]);
verifica('il politico sceglie il partito', str_contains($html, 'aggiornata') && str_contains($visitatore->get("sito/index.php?r=politica/profilo&livello=$slug"), "Lista Civica $s"), $html);
$html = $politico->get('portale/index.php?r=politico/articoli');
verifica('politico: pagina articoli', $politico->stato === 200, $html);
$politico->post('portale/index.php?r=politico/articoli', 'portale/index.php?r=politico/salva-articolo', ['id' => '', 'titolo' => "Bozza segreta $s", 'categoria' => '', 'sommario' => '', 'corpo' => 'Testo.', 'stato' => 'Bozza']);
$html = $politico->post('portale/index.php?r=politico/articoli', 'portale/index.php?r=politico/salva-articolo', ['id' => '', 'titolo' => "Il mio impegno $s", 'categoria' => 'Programma', 'sommario' => 'In breve.', 'corpo' => "Primo paragrafo.\n\nSecondo paragrafo.", 'stato' => 'Pubblicato']);
verifica('il politico pubblica un articolo', str_contains($html, 'Articolo pubblicato'), $html);
$html = $visitatore->get("sito/index.php?r=politica/profilo&livello=$slug");
verifica('nella scheda pubblica c\'è l\'articolo pubblicato e non la bozza', str_contains($html, "Il mio impegno $s") && !str_contains($html, "Bozza segreta $s"), $html);
$slugArticolo = (string) Database::valore('SELECT slug FROM articoli_politici WHERE titolo = ?', ["Il mio impegno $s"]);
$html = $visitatore->get("sito/index.php?r=politica/articolo&slug=$slugArticolo");
verifica('pagina dell\'articolo del politico', $visitatore->stato === 200 && str_contains($html, 'Secondo paragrafo'), $html);
$visitatore->get('sito/index.php?r=politica/articolo&slug=' . Database::valore('SELECT slug FROM articoli_politici WHERE titolo = ?', ["Bozza segreta $s"]));
verifica('la bozza non è raggiungibile', $visitatore->stato === 404);
$html = $visitatore->get("sito/index.php?r=politica/partito&id=$slugPartito");
verifica('articolo ed evento del politico tra notizie e appuntamenti del partito', str_contains($html, "Il mio impegno $s") && str_contains($html, "Banchetto $s"), $html);
$idArticoloPia = (string) Database::valore('SELECT id FROM articoli_politici WHERE titolo = ?', ["Il mio impegno $s"]);
$ada->post('portale/index.php?r=politico/articoli', 'portale/index.php?r=politico/elimina-articolo', ['id' => $idArticoloPia]);
verifica('un politico non elimina gli articoli di un altro', Database::valore('SELECT 1 FROM articoli_politici WHERE id = ?', [$idArticoloPia]) !== null);

echo "== Più testate, prezzi e letture ==\n";
$html = $editore->post('portale/index.php?r=home', 'portale/index.php?r=testata/scegli', ['testata' => 'cronache-del-nord']);
$altra = (string) Database::valore("SELECT slug FROM giornali WHERE slug <> 'wolfpress' ORDER BY id LIMIT 1");
$html = $editore->post('portale/index.php?r=home', 'portale/index.php?r=testata/scegli', ['testata' => $altra]);
verifica('l\'editore passa a un\'altra sua testata', str_contains($html, 'Ora lavori su') && !str_contains($html, "Articolo di prova $s"), $html);
$html = $editore->post('portale/index.php?r=home', 'portale/index.php?r=testata/scegli', ['testata' => "corriere-$s"]);
verifica('non può scegliere la testata di un altro editore', !str_contains($html, "Corriere $s"));
$html = $editore->post('portale/index.php?r=home', 'portale/index.php?r=testata/scegli', ['testata' => 'wolfpress']);
verifica('torna alla prima testata', str_contains($html, "Articolo di prova $s"), $html);
$html = $nuovoEditore->post('portale/index.php?r=testata', 'portale/index.php?r=testata/crea', ['nome' => "Gazzetta $s", 'email' => "gazzetta$s@prova.example"]);
verifica('un editore crea una seconda testata', str_contains($html, 'Testata creata') && str_contains($nuovoEditore->get('portale/index.php?r=home'), "Corriere $s"), $html);
$html = $editore->post('portale/index.php?r=configuratore', 'portale/index.php?r=configuratore/salva', ['testata' => 'WolfPress', 'cliente' => 'Wolf Media', 'colore' => '#286347', 'benvenuto' => '', 'email_assistenza' => '', 'moduli' => ['articoli', 'lettori', 'redazione', 'assistenza'], 'prezzo_mensile' => '4,90', 'prezzo_annuale' => '49']);
verifica('l\'editore imposta i prezzi degli abbonamenti', str_contains($html, 'Configurazione salvata') && str_contains($html, 'value="4,9"'), $html);
$html = $lettore->get('sito/index.php?r=sostieni&testata=wolfpress');
verifica('sul sito compaiono i due piani', str_contains($html, '4,90 al mese') && str_contains($html, '49,00 all'), $html);
$lettore->post('sito/index.php?r=sostieni&testata=wolfpress', 'sito/index.php?r=sostieni/abbonati', ['testata' => 'wolfpress', 'piano' => 'mensile'], false);
verifica('abbonamento: rimando a Stripe con il prezzo del database', $lettore->stato === 303 && str_contains($lettore->intestazioni, 'checkout.stripe.com') && (int) Database::valore('SELECT importo_centesimi FROM abbonamenti ORDER BY id DESC LIMIT 1') === 490, $lettore->intestazioni);
$prima = (int) Database::valore('SELECT letture FROM articoli_giornali WHERE id = ?', [$altrui]);
$slugLetto = (string) Database::valore('SELECT slug FROM articoli_giornali WHERE id = ?', [$altrui]);
$visitatore->get("sito/index.php?r=notizie/articolo&slug=$slugLetto");
$visitatore->get("sito/index.php?r=notizie/articolo&slug=$slugLetto");
verifica('le letture degli articoli vengono contate', (int) Database::valore('SELECT letture FROM articoli_giornali WHERE id = ?', [$altrui]) === $prima + 2
    && (int) Database::valore('SELECT letture FROM letture_giornaliere WHERE giornale_id = ? AND giorno = ?', [$idTestata, (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d')]) >= 2);
$html = $editore->get('portale/index.php?r=cliente');
verifica('l\'area cliente mostra le letture reali', $editore->stato === 200 && str_contains($html, 'Ultimi sette giorni'), $html);

echo "== Atlante Civico: clienti, permessi, fascicoli ==\n";
/** Carica un file con un form (multipart), leggendo il codice CSRF dalla pagina indicata. */
$carica = static function (Browser $b, string $pagina, string $azione, array $campi, string $campoFile, string $contenuto, string $nome) {
    preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $b->get($pagina), $m);
    $temporaneo = tempnam(sys_get_temp_dir(), 'wp') . '-' . $nome;
    file_put_contents($temporaneo, $contenuto);
    return $b->chiama($azione, ['_csrf' => $m[1] ?? ''] + $campi + [$campoFile => new CURLFile($temporaneo, null, $nome)], true, [], null, true);
};
$pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
$idComitato = (string) Database::valore("SELECT id FROM clienti_atlante WHERE denominazione = 'Comitato per Ada Valleri'");
$idPartitoCliente = (string) Database::valore("SELECT id FROM clienti_atlante WHERE denominazione = 'Alleanza Valdora'");
$html = $tecnico->get('portale/index.php?r=atlante');
verifica('staff: vede tutti i clienti di Atlante Civico', $tecnico->stato === 200 && str_contains($html, 'Comitato per Ada Valleri') && str_contains($html, 'Lista civica Valleverde Insieme'), $html);
$html = $ada->get('portale/index.php?r=atlante');
verifica('referente: vede solo il cliente per cui ha un mandato verificato', $ada->stato === 200 && str_contains($html, 'Comitato per Ada Valleri') && !str_contains($html, 'Lista civica Valleverde Insieme') && !str_contains($html, 'Nuovo cliente'), $html);
$ada->get("portale/index.php?r=atlante/cliente&id=$idPartitoCliente");
verifica('referente: il cliente di un altro risulta inesistente', $ada->stato === 404);
verifica('il tentativo negato finisce nel registro', (int) Database::valore("SELECT COUNT(*) FROM audit_atlante WHERE cliente_id = ? AND esito = 'negata'", [$idPartitoCliente]) >= 1);
$html = $editore->get('portale/index.php?r=atlante');
verifica('chi non ha mandati non vede nessun cliente', $editore->stato === 200 && !str_contains($html, 'Comitato per Ada Valleri'), $html);
$editore->get('portale/index.php?r=atlante/modelli');
verifica('i modelli MASTER sono solo per lo staff', $editore->stato === 404);
$html = $ada->post("portale/index.php?r=atlante/cliente&id=$idComitato", 'portale/index.php?r=atlante/salva-cliente', ['id' => $idComitato, 'tipo' => 'comitato', 'denominazione' => 'Manomesso']);
verifica('un referente non modifica l\'anagrafica', Database::valore('SELECT denominazione FROM clienti_atlante WHERE id = ?', [$idComitato]) === 'Comitato per Ada Valleri');

$html = $tecnico->post('portale/index.php?r=atlante', 'portale/index.php?r=atlante/salva-cliente', ['id' => '0', 'tipo' => 'candidato', 'denominazione' => "Candidato $s", 'codice_fiscale' => 'RSSMRA80A01H501U', 'partita_iva' => '', 'pec' => "pec$s@prova.example", 'sede' => 'Via Roma 1, Parma']);
$idCliente = (string) Database::valore('SELECT id FROM clienti_atlante WHERE denominazione = ?', ["Candidato $s"]);
verifica('staff crea un cliente', $idCliente !== '' && str_contains($html, 'RSSMRA80A01H501U'), $html);
$riga = Database::riga('SELECT * FROM clienti_atlante WHERE id = ?', [$idCliente]);
verifica('codice fiscale, PEC e sede cifrati nel database', !str_contains(implode('|', array_map('strval', $riga)), 'RSSMRA80') && !str_contains(implode('|', array_map('strval', $riga)), 'Via Roma') && Cifratura::decifra($riga['codice_fiscale_cifrato']) === 'RSSMRA80A01H501U');
$html = $tecnico->post('portale/index.php?r=atlante', 'portale/index.php?r=atlante/salva-cliente', ['id' => '0', 'tipo' => 'candidato', 'denominazione' => "Doppione $s", 'codice_fiscale' => 'rssmra80a01h501u']);
verifica('stesso codice fiscale: cliente doppio rifiutato', str_contains($html, 'Esiste già un cliente'), $html);
$base = "portale/index.php?r=atlante/cliente&id=$idCliente";
$html = $tecnico->post($base, 'portale/index.php?r=atlante/referente', ['cliente' => $idCliente, 'email' => "pia$s@prova.example", 'ruolo' => 'firmatario', 'evidenza' => 'Delega prot. 7']);
verifica('staff collega un referente (account esistente)', str_contains($html, 'Referente aggiunto'), $html);
$politico->get($base);
verifica('referente non ancora verificato: non vede il cliente', $politico->stato === 404);
$idRef = (string) Database::valore('SELECT id FROM referenti_clienti_atlante WHERE cliente_id = ?', [$idCliente]);
$tecnico->post($base, 'portale/index.php?r=atlante/stato-referente', ['cliente' => $idCliente, 'referente' => $idRef, 'cosa' => 'verifica']);
$html = $politico->get($base);
verifica('dopo la verifica del mandato il referente vede il cliente', $politico->stato === 200 && str_contains($html, "Candidato $s") && str_contains($html, 'RSSMRA80A01H501U'), $html);
$html = $tecnico->post($base, 'portale/index.php?r=atlante/sito', ['cliente' => $idCliente, 'sito' => '0', 'nome' => "Sito $s", 'dominio' => "sito$s.example.it", 'tipo_sito' => 'comunicazione_politica']);
$idSito = (string) Database::valore('SELECT id FROM siti_atlante WHERE cliente_id = ?', [$idCliente]);
verifica('staff crea un sito: tutte le funzioni facoltative sono spente', $idSito !== '' && (int) Database::valore('SELECT COUNT(*) FROM funzionalita_siti_atlante WHERE sito_id = ?', [$idSito]) === 0, $html);
$html = $tecnico->post($base, 'portale/index.php?r=atlante/stato-sito', ['cliente' => $idCliente, 'sito' => $idSito, 'stato' => 'attivo']);
verifica('il sito non si attiva se il cliente non è verificato', str_contains($html, 'solo dopo la verifica del cliente'), $html);
$tecnico->post($base, 'portale/index.php?r=atlante/stato-cliente', ['cliente' => $idCliente, 'stato' => 'verificato']);
$tecnico->post($base, 'portale/index.php?r=atlante/stato-sito', ['cliente' => $idCliente, 'sito' => $idSito, 'stato' => 'attivo']);
$riga = Database::riga('SELECT stato, ambiente, attivato_il FROM siti_atlante WHERE id = ?', [$idSito]);
verifica('cliente verificato: il sito va in produzione con data di attivazione', $riga['stato'] === 'attivo' && $riga['ambiente'] === 'produzione' && $riga['attivato_il'] !== null, $riga);
$html = $politico->post($base, 'portale/index.php?r=atlante/richiedi-funzione', ['cliente' => $idCliente, 'sito' => $idSito, 'codice' => 'newsletter']);
verifica('il referente chiede la newsletter: resta «richiesta», non attiva', str_contains($html, 'Richiesta inviata') && Database::valore("SELECT stato FROM funzionalita_siti_atlante WHERE sito_id = ? AND codice = 'newsletter'", [$idSito]) === 'richiesta', $html);
$politico->post($base, 'portale/index.php?r=atlante/stato-funzione', ['cliente' => $idCliente, 'sito' => $idSito, 'codice' => 'newsletter', 'stato' => 'attiva']);
verifica('il referente non può attivarsela da solo', Database::valore("SELECT stato FROM funzionalita_siti_atlante WHERE sito_id = ? AND codice = 'newsletter'", [$idSito]) === 'richiesta');
$tecnico->post($base, 'portale/index.php?r=atlante/stato-funzione', ['cliente' => $idCliente, 'sito' => $idSito, 'codice' => 'newsletter', 'stato' => 'attiva', 'evidenza' => 'Verifica consensi del 20/11']);
$riga = Database::riga("SELECT * FROM funzionalita_siti_atlante WHERE sito_id = ? AND codice = 'newsletter'", [$idSito]);
verifica('lo staff la attiva: restano chi ha autorizzato e quando', $riga['stato'] === 'attiva' && $riga['autorizzato_da'] !== null && $riga['attivato_il'] !== null, $riga);
$html = $tecnico->post($base, 'portale/index.php?r=atlante/offerta', ['cliente' => $idCliente, 'codice_offerta' => "OFF-$s", 'sito_id' => $idSito, 'attivazione' => '450', 'canone' => '39,90', 'periodicita' => 'mensile']);
$idOfferta = (string) Database::valore('SELECT id FROM offerte_atlante WHERE codice_offerta = ?', ["OFF-$s"]);
verifica('offerta creata con importi in centesimi', (int) Database::valore('SELECT canone_centesimi FROM offerte_atlante WHERE id = ?', [$idOfferta]) === 3990 && str_contains($html, '39,90'), $html);
$tecnico->post($base, 'portale/index.php?r=atlante/stato-offerta', ['cliente' => $idCliente, 'offerta' => $idOfferta, 'stato' => 'accettata']);
verifica('lo staff non può accettare l\'offerta al posto del cliente', Database::valore('SELECT stato FROM offerte_atlante WHERE id = ?', [$idOfferta]) === 'bozza');
$tecnico->post($base, 'portale/index.php?r=atlante/stato-offerta', ['cliente' => $idCliente, 'offerta' => $idOfferta, 'stato' => 'inviata']);
$html = $politico->post($base, 'portale/index.php?r=atlante/stato-offerta', ['cliente' => $idCliente, 'offerta' => $idOfferta, 'stato' => 'accettata']);
verifica('il firmatario accetta l\'offerta inviata', Database::valore('SELECT stato FROM offerte_atlante WHERE id = ?', [$idOfferta]) === 'accettata', $html);
$tecnico->post($base, 'portale/index.php?r=atlante/salva-campo', ['cliente' => $idCliente, 'chiave' => 'iban', 'valore' => 'IT60X0542811101000000123456', 'riservato' => '1']);
verifica('campo riservato cifrato nel database', (int) Database::valore("SELECT COUNT(*) FROM campi_clienti_atlante WHERE cliente_id = ? AND valore_pubblico IS NULL AND valore_cifrato NOT LIKE '%IT60X%'", [$idCliente]) === 1);
$tecnico->post($base, 'portale/index.php?r=atlante/verifica', ['cliente' => $idCliente, 'oggetto' => 'dpia', 'sito_id' => $idSito]);
$idVerifica = (string) Database::valore('SELECT id FROM verifiche_atlante WHERE cliente_id = ?', [$idCliente]);
$html = $tecnico->post($base, 'portale/index.php?r=atlante/esito-verifica', ['cliente' => $idCliente, 'verifica' => $idVerifica, 'esito' => 'conclusa', 'motivazione' => '']);
verifica('una verifica non si chiude senza motivazione', str_contains($html, 'Campo obbligatorio: motivazione') && Database::valore('SELECT esito FROM verifiche_atlante WHERE id = ?', [$idVerifica]) === 'da_fare', $html);
$tecnico->post($base, 'portale/index.php?r=atlante/esito-verifica', ['cliente' => $idCliente, 'verifica' => $idVerifica, 'esito' => 'conclusa', 'motivazione' => 'DPIA non necessaria: nessun trattamento su larga scala.', 'evidenza' => 'Verbale 12/2026']);
verifica('verifica conclusa con motivazione e validatore', Database::valore('SELECT validata_da FROM verifiche_atlante WHERE id = ? AND esito = ?', [$idVerifica, 'conclusa']) !== null);

echo "== Atlante Civico: modelli, documenti, firme ==\n";
$html = $tecnico->post('portale/index.php?r=atlante/modelli', 'portale/index.php?r=atlante/modello', ['codice' => "contratto-$s", 'titolo' => "Contratto $s", 'categoria' => 'contratto', 'destinatario' => 'candidato']);
$idModello = (string) Database::valore('SELECT id FROM modelli_documentali_master WHERE titolo = ?', ["Contratto $s"]);
verifica('staff aggiunge un modello al catalogo', $idModello !== '' && str_contains($html, "Contratto $s"), $html);
$html = $carica($tecnico, 'portale/index.php?r=atlante/modelli', 'portale/index.php?r=atlante/versione', ['modello' => $idModello, 'versione' => '1.0'], 'file', '<?php echo 1;', 'finto.pdf');
verifica('un file che non è un PDF viene rifiutato anche se si chiama .pdf', str_contains($html, 'Tipo di file non ammesso'), $html);
$html = $carica($tecnico, 'portale/index.php?r=atlante/modelli', 'portale/index.php?r=atlante/versione', ['modello' => $idModello, 'versione' => '1.0'], 'file', $pdf, 'modello.pdf');
$versione = Database::riga('SELECT * FROM versioni_modelli_master WHERE modello_id = ?', [$idModello]);
verifica('versione caricata: impronta SHA-256 del contenuto', $versione !== null && $versione['sha256'] === hash('sha256', $pdf), $html);
$fileArchivio = "$backend/storage/" . substr($versione['file_rif'], 0, strrpos($versione['file_rif'], '.')) . '.bin';
verifica('il file in archivio è cifrato', is_file($fileArchivio) && !str_contains((string) file_get_contents($fileArchivio), '%PDF'));
verifica('l\'archivio non è raggiungibile dal web', $visitatore->get('backend/php/storage/' . basename(dirname($fileArchivio)) . '/' . basename($fileArchivio)) === 'Accesso negato');
$tecnico->post($base, 'portale/index.php?r=atlante/crea-fascicolo', ['cliente' => $idCliente, 'codice' => "F-$s", 'titolo' => "Fascicolo $s", 'sito_id' => $idSito]);
$idFascicolo = (string) Database::valore('SELECT id FROM fascicoli_atlante WHERE codice = ?', [strtoupper("F-$s")]);
$fasc = "portale/index.php?r=atlante/fascicolo&id=$idFascicolo";
$html = $tecnico->post($fasc, 'portale/index.php?r=atlante/documento', ['fascicolo' => $idFascicolo, 'versione' => $versione['id']]);
verifica('una versione non approvata non genera documenti', str_contains($html, 'solo versioni approvate'), $html);
$tecnico->post('portale/index.php?r=atlante/modelli', 'portale/index.php?r=atlante/stato-versione', ['versione' => $versione['id'], 'stato' => 'da_verificare']);
$tecnico->post('portale/index.php?r=atlante/modelli', 'portale/index.php?r=atlante/stato-versione', ['versione' => $versione['id'], 'stato' => 'approvata']);
$html = $tecnico->post($fasc, 'portale/index.php?r=atlante/documento', ['fascicolo' => $idFascicolo, 'versione' => $versione['id']]);
$idDoc = (string) Database::valore('SELECT id FROM documenti_atlante WHERE fascicolo_id = ?', [$idFascicolo]);
verifica('documento creato dalla versione approvata', $idDoc !== '' && str_contains($html, "Contratto $s"), $html);
$html = $tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'approva']);
verifica('non si approva un documento senza file e senza verifica', str_contains($html, 'va verificato'), $html);
$carica($tecnico, $fasc, 'portale/index.php?r=atlante/carica-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc], 'file', $pdf . "compilato $s", 'contratto.pdf');
$tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'verifica']);
$tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'approva']);
verifica('file caricato, verificato e approvato: pronto per la firma', Database::valore('SELECT stato FROM documenti_atlante WHERE id = ?', [$idDoc]) === 'pronto_firma');
$html = $tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'firmato']);
verifica('non è «firmato» senza le firme verificate', str_contains($html, 'Servono una firma del cliente e una del fornitore'), $html);
foreach (['cliente', 'fornitore'] as $parte) {
    $carica($tecnico, $fasc, 'portale/index.php?r=atlante/firma', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'parte' => $parte, 'firmato_il' => date('Y-m-d')], 'prova', $pdf . "firma $parte", 'firma.pdf');
}
$tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'firmato']);
verifica('firme registrate ma non verificate: ancora non firmato', Database::valore('SELECT stato FROM documenti_atlante WHERE id = ?', [$idDoc]) === 'pronto_firma');
foreach (Database::righe('SELECT id FROM firme_documenti_atlante WHERE documento_id = ?', [$idDoc]) as $f) {
    $tecnico->post($fasc, 'portale/index.php?r=atlante/verifica-firma', ['fascicolo' => $idFascicolo, 'firma' => $f['id']]);
}
$tecnico->post($fasc, 'portale/index.php?r=atlante/avanza-documento', ['fascicolo' => $idFascicolo, 'documento' => $idDoc, 'cosa' => 'firmato']);
verifica('con le due firme verificate il documento è firmato', Database::valore('SELECT stato FROM documenti_atlante WHERE id = ?', [$idDoc]) === 'firmato');
$scaricato = $politico->get("portale/index.php?r=atlante/scarica&cosa=documento&id=$idDoc");
verifica('il firmatario scarica il documento (decifrato)', $politico->stato === 200 && $scaricato === $pdf . "compilato $s");
$ada->get("portale/index.php?r=atlante/scarica&cosa=documento&id=$idDoc");
verifica('il referente di un altro cliente non lo scarica', $ada->stato === 404);
$politico->get("portale/index.php?r=atlante/scarica&cosa=versione&id={$versione['id']}");
verifica('il modello MASTER non è scaricabile dai referenti', $politico->stato === 404);
$tecnico->post($base, 'portale/index.php?r=atlante/stato-referente', ['cliente' => $idCliente, 'referente' => $idRef, 'cosa' => 'revoca']);
$politico->get($fasc);
verifica('mandato revocato: accesso tolto subito', $politico->stato === 404);
$html = $tecnico->get($base);
verifica('registro delle operazioni visibile allo staff', str_contains($html, 'Registro delle operazioni') && str_contains($html, 'scaricamento') && (int) Database::valore('SELECT COUNT(*) FROM audit_atlante WHERE cliente_id = ?', [$idCliente]) > 15, $html);

echo "== Menu per contesto ==\n";
$html = $tecnico->get('portale/index.php?r=tecnico');
verifica('portale, tecnico: contesto WolfPress con le tre schede', str_contains($html, 'wp-contesto-wolfpress') && str_contains($html, 'Piattaforma del fornitore') && substr_count($html, 'Contesti del portale') === 1 && str_contains($html, '>Atlante Civico</a>') && str_contains($html, '>Giornali</a>'), $html);
verifica('portale, tecnico: «Clienti e testate» è nel contesto Giornali', str_contains($tecnico->get('portale/index.php?r=tecnico/clienti'), 'wp-contesto-giornale'));
$html = $tecnico->get('portale/index.php?r=atlante/modelli');
verifica('portale, tecnico: Atlante Civico con le sue voci', str_contains($html, 'wp-contesto-atlante') && str_contains($html, 'Modelli documentali') && str_contains($html, 'Clienti e fascicoli') && !str_contains($html, 'Personale tecnico'), $html);
$html = $ada->get('portale/index.php?r=politico');
verifica('portale, politico: solo Atlante Civico', str_contains($html, 'wp-contesto-atlante') && str_contains($html, '<strong>Atlante Civico</strong>') && !str_contains($html, 'Contesti del portale'), $html);
$html = $editore->get('portale/index.php?r=cliente');
verifica('portale, editore: contesto del giornale con il nome della testata', str_contains($html, 'wp-contesto-giornale') && str_contains($html, '<strong>WolfPress</strong>') && str_contains($html, 'Account giornalisti') && str_contains($html, 'I miei ticket'), $html);
$html = $visitatore->get('sito/index.php');
verifica('sito: barra dei tre mondi e contesto WolfPress', str_contains($html, 'wp-mondi') && str_contains($html, 'wp-contesto-wolfpress') && str_contains($html, 'Aree del sito'), $html);
$html = $visitatore->get('sito/index.php?r=politica');
verifica('sito: Atlante Civico ha marchio e voci proprie', str_contains($html, 'wp-contesto-atlante') && str_contains($html, '<strong>Atlante Civico</strong>') && str_contains($html, 'Candidati ed eletti') && str_contains($html, 'Alleanza Valdora'), $html);
$html = $visitatore->get('sito/index.php?r=notizie&testata=wolfpress');
verifica('sito: dentro una testata il menu ha il suo nome e le sue sezioni', str_contains($html, 'wp-contesto-giornale') && str_contains($html, 'Segui o sostieni') && str_contains($html, 'notizie.php?testata=wolfpress&amp;sezione='), $html);
$html = $visitatore->get('sito/index.php?r=notizie/articolo&slug=la-settimana-in-cinque-notizie');
verifica('sito: anche l\'articolo sta nel contesto della sua testata', str_contains($html, 'wp-contesto-giornale') && str_contains($html, 'Tutte le testate'), $html);
verifica('sito: l\'elenco delle testate è il contesto Giornali', str_contains($visitatore->get('sito/index.php?r=testate'), '<strong>Giornali</strong>'));
$lettore->entra('sito', "lia$s@prova.example", PW);
$lettore->post('sito/index.php?r=sostieni&politico=comune', 'sito/index.php?r=sostieni/segui', ['politico' => 'comune']);
verifica('seguire non iscrive alla newsletter', (int) Database::valore('SELECT newsletter FROM iscrizioni ORDER BY id DESC LIMIT 1') === 0);

echo "== Sospensione ==\n";
$tecnico->post('portale/index.php?r=tecnico/personale&id=1', 'portale/index.php?r=tecnico/salva-operatore', ['id' => '1', 'nome' => 'Marco Bellori', 'ruolo' => 'Sistemista', 'turno' => '08:00–16:00', 'stato' => 'Sospeso']);
verifica('un operatore sospeso non entra più', str_contains((new Browser())->entra('portale', 'marco.bellori@wolfpress.example', PW), 'Account sospeso'));

$avvisi = preg_grep('/PHP (Warning|Notice|Deprecated|Fatal|Parse)|Portale:|Sito:/', file($registroServer) ?: []);
verifica('nessun errore o avviso PHP nel registro del server', $avvisi === [], array_slice(array_values($avvisi), 0, 5));

echo PHP_EOL . ($falliti === 0 ? 'TUTTE LE PROVE SUPERATE' : "PROVE FALLITE: $falliti") . PHP_EOL;
exit($falliti === 0 ? 0 : 1);
