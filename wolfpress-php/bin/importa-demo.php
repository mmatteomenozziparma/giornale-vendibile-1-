<?php
/**
 * Carica nel database i dati dimostrativi di app/data/*.json
 * (testate, giornalisti, operatori, politici, partiti, articoli, ticket, agenda, chat).
 *
 * Uso, dalla cartella wolfpress-php, su un database appena creato con schema.sql:
 *     php bin/importa-demo.php
 *
 * Tutti gli account demo sono creati già attivi con la STESSA password,
 * chiesta a terminale: usarla solo per provare, mai in produzione.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$config = require dirname(__DIR__) . '/app/config.php';
require $config['backend'] . '/src/bootstrap.php';

$leggi = static fn (string $nome): array => json_decode((string) file_get_contents(dirname(__DIR__) . "/app/data/$nome.json"), true);
$json = static fn (array $dati): string => (string) json_encode($dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$dividi = static function (string $completo): array {
    $parti = explode(' ', trim($completo));
    $cognome = count($parti) > 1 ? array_pop($parti) : '';
    return [implode(' ', $parti), $cognome];
};
$utc = static fn (string $locale): string => (new DateTimeImmutable($locale, new DateTimeZone('Europe/Rome')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

if ((int) Database::valore('SELECT COUNT(*) FROM generalita') > 0) {
    fwrite(STDERR, "Il database contiene già degli account: l'importazione si fa solo su un database vuoto.\n");
    exit(1);
}

$password = getenv('PASSWORD_DEMO') ?: '';
if ($password === '') {
    fwrite(STDERR, 'Password per tutti gli account demo (minimo 10 caratteri): ');
    $terminale = stream_isatty(STDIN);
    $terminale && shell_exec('stty -echo');
    $password = rtrim((string) fgets(STDIN), "\r\n");
    $terminale && shell_exec('stty echo');
    fwrite(STDERR, PHP_EOL);
}

try {
    Account::controllaPassword($password, '');
    $hash = Cifratura::hashPassword($password);
    $creati = [];

    Database::transazione(static function () use ($leggi, $json, $dividi, $utc, $hash, &$creati): void {
        /** Crea un account attivo e il suo profilo; restituisce [id generalita, id profilo]. */
        $account = static function (string $tipo, string $completo, string $email, array $profilo) use ($dividi, $hash, &$creati): array {
            [$nome, $cognome] = $dividi($completo);
            $id = Database::inserisci('generalita', [
                'tipo' => $tipo, 'nome' => $nome, 'cognome' => $cognome,
                'email_cifrata' => Cifratura::cifra($email), 'email_hash' => Cifratura::indiceEmail($email),
                'password_hash' => $hash, 'stato' => 'attivo', 'email_verificata_il' => gmdate('Y-m-d H:i:s'),
            ]);
            $creati[] = sprintf('%-12s %-22s %s', $tipo, $completo, $email);
            return [$id, Database::inserisci(Account::TABELLE[$tipo], ['generalita_id' => $id] + $profilo)];
        };
        $indirizzo = static fn (string $nome): string => strtolower(str_replace(' ', '.', (string) iconv('UTF-8', 'ASCII//TRANSLIT', $nome))) . '@wolfpress.example';

        // --- Tecnici (ex "operatori") ---
        $tecnici = [];
        foreach ($leggi('operatori') as $o) {
            [$g, $id] = $account('tecnico', $o['nome'], $indirizzo($o['nome']), [
                'qualifica' => $o['ruolo'], 'turno' => $o['turno'], 'livello' => $o['ruolo'] === 'Responsabile tecnico' ? 'responsabile' : 'operatore',
            ]);
            $tecnici[$o['id']] = ['id' => $id, 'generalita' => $g, 'nome' => $o['nome']];
        }

        // --- Editore e testate ---
        [$editoreG, $editore] = $account('editore', 'Laura Monti', 'laura.monti@wolfpress.example', ['ragione_sociale' => 'Wolf Media']);
        $giornali = [];
        foreach ($leggi('testate') as $t) {
            $g = Database::inserisci('generalita', [
                'tipo' => 'giornale', 'nome' => $t['nome'], 'stato' => 'attivo',
                'email_cifrata' => Cifratura::cifra($t['email']), 'email_hash' => Cifratura::impronta("giornale|{$t['id']}|{$t['email']}"),
            ]);
            $giornali[$t['id']] = Database::inserisci('giornali', [
                'generalita_id' => $g, 'editore_id' => $editore, 'nome' => $t['nome'], 'slug' => $t['id'],
                'descrizione' => $t['descrizione'], 'dominio' => $t['dominio'],
                'dati' => $json([
                    'stato' => $t['stato'], 'nodo' => $t['nodo'], 'backup' => $t['backup'], 'incidente' => $t['incidente'], 'piano' => $t['piano'],
                    'referente' => (string) ($tecnici[$t['referente']]['id'] ?? ''), 'cliente' => $t['cliente'], 'social' => $t['social'],
                ]),
                'impostazioni' => $t['id'] === 'wolfpress' ? $json($leggi('impostazioni')) : null,
            ]);
        }
        $principale = $giornali['wolfpress'];

        // --- Giornalisti della testata principale ---
        $giornalisti = [];
        foreach ($leggi('giornalisti') as $gi) {
            [$g, $id] = $account('giornalista', $gi['nome'], $gi['email'], [
                'giornale_id' => $principale, 'qualifica' => $gi['ruolo'], 'sezione' => $gi['sezione'], 'bio' => $gi['bio'],
            ]);
            if ($gi['stato'] !== 'Attivo') {
                Database::esegui("UPDATE generalita SET stato = 'sospeso' WHERE id = ?", [$g]);
            }
            $giornalisti[$gi['id']] = ['id' => $id, 'generalita' => $g, 'nome' => $gi['nome']];
        }
        $perNome = array_column($giornalisti, 'id', 'nome');

        // --- Articoli ---
        foreach ($leggi('articoli') as $a) {
            $stato = ['Bozza' => 'bozza', 'In revisione' => 'in_revisione', 'Pubblicato' => 'pubblicato'][$a['stato']];
            $quando = $utc($a['data'] . ' 09:00');
            Database::inserisci('articoli_giornali', [
                'giornale_id' => $giornali[$a['testata']], 'giornalista_id' => $perNome[$a['autore']] ?? null, 'firma' => $a['autore'],
                'letture' => $a['letture'], 'titolo' => $a['titolo'], 'slug' => $a['slug'], 'sezione' => $a['sezione'],
                'sommario' => $a['sommario'], 'corpo' => $a['corpo'], 'stato' => $stato,
                'pubblicato_il' => $stato === 'pubblicato' ? $quando : null, 'creato_il' => $quando,
            ]);
        }

        // --- Agenda dei giornalisti (eventi riservati organizzati dalla testata) ---
        foreach ($leggi('eventi') as $e) {
            $chi = $giornalisti[$e['giornalista']];
            $id = Database::inserisci('eventi', [
                'titolo' => $e['titolo'], 'luogo' => $e['luogo'], 'inizio' => $utc($e['data'] . ' ' . ($e['ora'] ?: '00:00')),
                'pubblico' => 0, 'creato_da' => $chi['generalita'],
            ]);
            Database::inserisci('eventi_organizzatori', ['evento_id' => $id, 'giornale_id' => $principale, 'confermato' => 1]);
            Database::inserisci('eventi_partecipanti', ['evento_id' => $id, 'giornalista_id' => $chi['id']]);
        }

        // --- Chat di redazione ---
        $autori = array_column($giornalisti, 'generalita', 'nome');
        foreach ($leggi('messaggi') as $m) {
            Database::inserisci('messaggi_redazione', [
                'giornale_id' => $principale, 'canale' => $m['canale'], 'autore_id' => $autori[$m['autore']] ?? $editoreG,
                'autore' => $m['autore'] === 'Editore' ? 'Laura Monti' : $m['autore'], 'testo' => $m['testo'], 'creato_il' => $utc($m['quando']),
            ]);
        }

        // --- Ticket e relative conversazioni ---
        $primoTecnico = reset($tecnici);
        $tecnicoPerNome = array_column($tecnici, null, 'nome');
        foreach ($leggi('ticket') as $t) {
            $primo = $t['messaggi'][0]['quando'] ?? ($t['aperto_il'] . ' 09:00');
            $id = Database::inserisci('ticket', [
                'codice' => $t['id'], 'aperto_da' => $editoreG, 'giornale_id' => $giornali[$t['testata']] ?? null,
                'tecnico_id' => $tecnicoPerNome[$t['assegnatario']]['id'] ?? null,
                'oggetto' => $t['oggetto'], 'descrizione' => $t['descrizione'], 'mittente' => $t['mittente'], 'video' => $t['video'] ?: null,
                'priorita' => mb_strtolower($t['priorita']),
                'stato' => ['Aperto' => 'aperto', 'In lavorazione' => 'in_lavorazione', 'Risolto' => 'risolto'][$t['stato']],
                'creato_il' => $utc($primo),
            ]);
            // Il primo messaggio è la descrizione, già salvata nel ticket.
            foreach (array_slice($t['messaggi'], 1) as $m) {
                Database::inserisci('ticket_messaggi', [
                    'ticket_id' => $id, 'testo' => $m['testo'], 'creato_il' => $utc($m['quando']),
                    'autore_id' => $m['ruolo'] === 'tecnico' ? ($tecnicoPerNome[$m['autore']]['generalita'] ?? $primoTecnico['generalita']) : $editoreG,
                ]);
            }
        }

        // --- Partiti e politici ---
        foreach ($leggi('partiti') as $p) {
            Database::inserisci('partiti', ['slug' => $p['id'], 'nome' => $p['nome'], 'dati' => $json(array_diff_key($p, ['id' => 1, 'nome' => 1]))]);
        }
        foreach ($leggi('politici') as $p) {
            $account('politico', $p['nome'], $indirizzo($p['nome']), [
                'slug' => $p['id'], 'livello' => $p['id'], 'partito' => $p['partito_nome'], 'biografia' => $p['bio'],
                'carica' => $p['candidatura']['ruolo'], 'territorio' => $p['candidatura']['luogo'],
                'dati' => $json(['partito' => $p['partito'], 'candidatura' => $p['candidatura'], 'mandato' => $p['mandato']]),
            ]);
        }
    });

    echo "Dati demo importati. Account creati (tutti con la password indicata):\n\n" . implode("\n", $creati) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Errore: ' . $e->getMessage() . "\n");
    exit(1);
}
