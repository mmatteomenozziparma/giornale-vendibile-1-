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
date_default_timezone_set('Europe/Rome');

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
            [$politicoG, $politicoId] = $politiciDemo[$p['id']] = $account('politico', $p['nome'], $indirizzo($p['nome']), [
                'slug' => $p['id'], 'livello' => $p['id'], 'partito' => $p['partito_nome'], 'biografia' => $p['bio'],
                'carica' => $p['candidatura']['ruolo'], 'territorio' => $p['candidatura']['luogo'],
                'dati' => $json(['partito' => $p['partito'], 'candidatura' => array_diff_key($p['candidatura'], ['agenda' => 1]), 'mandato' => array_diff_key($p['mandato'], ['agenda' => 1])]),
            ]);
            // L'agenda del politico diventa un elenco di eventi pubblici organizzati da lui.
            $visti = [];
            foreach (array_merge($p['candidatura']['agenda'], $p['mandato']['agenda']) as $e) {
                if (isset($visti[$e['data'] . $e['titolo']])) {
                    continue;
                }
                $visti[$e['data'] . $e['titolo']] = true;
                // "Piazza del Tiglio · ore 18:00" → luogo e ora separati.
                $ora = preg_match('/ore (\d{1,2}):(\d{2})/', $e['dettaglio'], $m) ? sprintf('%02d:%s', $m[1], $m[2]) : '00:00';
                $id = Database::inserisci('eventi', [
                    'titolo' => $e['titolo'], 'luogo' => trim((string) preg_replace('/\s*·?\s*ore \d{1,2}:\d{2}/', '', $e['dettaglio']), ' ·'),
                    'inizio' => $utc($e['data'] . ' ' . $ora), 'pubblico' => 1, 'creato_da' => $politicoG,
                ]);
                Database::inserisci('eventi_organizzatori', ['evento_id' => $id, 'politico_id' => $politicoId, 'confermato' => 1]);
            }
        }

        // --- Letture dell'ultima settimana della testata principale (per il grafico dell'area cliente) ---
        $giorni = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
        $letture = $leggi('impostazioni')['letture'];
        for ($i = 0; $i < 7; $i++) {
            $giorno = strtotime("-$i days");
            Database::inserisci('letture_giornaliere', ['giornale_id' => $principale, 'giorno' => date('Y-m-d', $giorno), 'letture' => $letture[$giorni[(int) date('w', $giorno)]] ?? 0]);
        }

        // --- Atlante Civico: clienti, referenti, sito, funzioni, offerta, fascicolo, verifiche, modelli ---
        $staff = reset($tecnici)['generalita'];
        $adesso = gmdate('Y-m-d H:i:s');
        [$adaG, $adaId] = $politiciDemo['comune'];
        $partito = Database::inserisci('clienti_atlante', ['tipo' => 'partito', 'denominazione' => 'Alleanza Valdora', 'stato' => 'verificato', 'verificato_da' => $staff, 'verificato_il' => $adesso]);
        $comitato = Database::inserisci('clienti_atlante', ['tipo' => 'comitato', 'denominazione' => 'Comitato per Ada Valleri', 'rappresentante_id' => $adaG, 'stato' => 'verificato', 'verificato_da' => $staff, 'verificato_il' => $adesso]);
        Database::inserisci('clienti_atlante', ['tipo' => 'lista_civica', 'denominazione' => 'Lista civica Valleverde Insieme', 'stato' => 'da_verificare']);
        // Ada è la firmataria del proprio comitato (mandato verificato): è l'unico cliente che vede.
        Database::inserisci('referenti_clienti_atlante', ['cliente_id' => $comitato, 'generalita_id' => $adaG, 'ruolo' => 'firmatario', 'mandato_evidenza_rif' => 'Atto costitutivo del comitato (demo)', 'verificato_il' => $adesso]);
        Database::inserisci('referenti_clienti_atlante', ['cliente_id' => $comitato, 'generalita_id' => $adaG, 'ruolo' => 'comunicazione', 'verificato_il' => $adesso]);
        $sito = Database::inserisci('siti_atlante', [
            'cliente_id' => $comitato, 'beneficiario_cliente_id' => $partito, 'politico_id' => $adaId,
            'nome' => 'Ada Valleri · candidatura comunale', 'slug' => 'ada-valleri', 'dominio' => 'adavalleri.example', 'stato' => 'collaudo',
        ]);
        Database::inserisci('funzionalita_siti_atlante', ['sito_id' => $sito, 'codice' => 'cms', 'stato' => 'attiva', 'autorizzato_da' => $staff, 'attivato_il' => $adesso]);
        Database::inserisci('funzionalita_siti_atlante', ['sito_id' => $sito, 'codice' => 'newsletter', 'stato' => 'richiesta']);
        Database::inserisci('offerte_atlante', ['cliente_id' => $comitato, 'sito_id' => $sito, 'codice_offerta' => 'DEMO-2026-01', 'attivazione_centesimi' => 45000, 'canone_centesimi' => 3900, 'periodicita' => 'mensile', 'stato' => 'inviata']);
        Database::inserisci('campi_clienti_atlante', ['cliente_id' => $comitato, 'chiave' => 'slogan', 'valore_pubblico' => 'Una città che si attraversa a piedi']);
        Database::inserisci('fascicoli_atlante', ['cliente_id' => $comitato, 'sito_id' => $sito, 'codice' => 'F-DEMO-01', 'titolo' => 'Avvio del sito di candidatura', 'creato_da' => $staff]);
        Database::inserisci('verifiche_atlante', ['cliente_id' => $comitato, 'sito_id' => $sito, 'oggetto' => 'gdpr_ruoli']);
        Database::inserisci('verifiche_atlante', ['cliente_id' => $comitato, 'sito_id' => $sito, 'oggetto' => 'cookie']);
        foreach ([
            ['CONTRATTO-SITO', 'Contratto di fornitura del sito', 'contratto', 'tutti', null],
            ['DPA', 'Accordo sul trattamento dei dati (DPA)', 'privacy', 'tutti', null],
            ['INFORMATIVA-NEWSLETTER', 'Informativa per la newsletter', 'privacy', 'tutti', 'Solo se è attiva la newsletter'],
            ['TRASPARENZA-PUBBLICITA', 'Avviso di trasparenza per la pubblicità politica', 'pubblicita_politica', 'candidato', 'Solo se è attiva la pubblicità politica'],
        ] as [$codice, $titolo, $categoria, $destinatario, $condizione]) {
            Database::inserisci('modelli_documentali_master', ['codice' => $codice, 'titolo' => $titolo, 'categoria' => $categoria, 'destinatario' => $destinatario, 'condizione_utilizzo' => $condizione]);
        }
    });

    echo "Dati demo importati. Account creati (tutti con la password indicata):\n\n" . implode("\n", $creati) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Errore: ' . $e->getMessage() . "\n");
    exit(1);
}
