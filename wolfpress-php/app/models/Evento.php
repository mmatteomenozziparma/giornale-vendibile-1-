<?php
/**
 * Eventi (tabelle "eventi", "eventi_organizzatori", "eventi_invitati", "eventi_partecipanti").
 *
 *  - organizzatori: un politico, una testata o entrambi insieme;
 *  - invitati: politici o giornalisti;
 *  - partecipanti: politici, giornalisti o lettori.
 *
 * Le regole (chi può creare, invitare, confermare, annullare) sono nel backend: classe Api.
 * Qui ci sono le letture per le schermate e l'agenda personale del giornalista.
 */
declare(strict_types=1);

final class Evento
{
    private const NOME_POLITICO = "TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, '')))";

    private static function riga(array $r): array
    {
        $ora = ora_locale($r['inizio'], 'H:i');
        return [
            'id' => (string) $r['id'],
            'titolo' => $r['titolo'],
            'descrizione' => (string) $r['descrizione'],
            'luogo' => (string) $r['luogo'],
            'data' => ora_locale($r['inizio'], 'Y-m-d'),
            'ora' => $ora === '00:00' ? '' : $ora,
            'pubblico' => (int) $r['pubblico'] === 1,
            'stato' => $r['stato'],
            'creato_da' => (int) $r['creato_da'],
        ] + array_diff_key($r, array_flip(['id', 'titolo', 'descrizione', 'luogo', 'inizio', 'fine', 'pubblico', 'stato', 'creato_da', 'creato_il']));
    }

    private static function leggi(string $sql, array $parametri = []): array
    {
        return array_map([self::class, 'riga'], Database::righe($sql, $parametri));
    }

    /** Colonna e id con cui chi è collegato compare tra invitati e partecipanti. */
    private static function io(): array
    {
        return [['politico' => 'politico_id', 'giornalista' => 'giornalista_id', 'user' => 'user_id'][Auth::ruolo()] ?? '', Auth::profiloId()];
    }

    /** Condizione SQL "sono tra gli organizzatori" per il politico o per la testata dell'editore collegato. */
    private static function mioOrganizzatore(): array
    {
        return Auth::ruolo() === 'politico'
            ? ['o.politico_id = ?', Auth::profiloId()]
            : ['o.giornale_id = ?', Testata::dbId(Auth::testata())];
    }

    // ------------------------------------------------------------
    // Portale
    // ------------------------------------------------------------

    /** Eventi organizzati dal politico o dalla testata collegata ("confermato" = 0 se devo ancora accettare). */
    public static function organizzati(): array
    {
        if (!in_array(Auth::ruolo(), ['politico', 'editore'], true)) {
            return [];
        }
        [$dove, $id] = self::mioOrganizzatore();
        return self::leggi(
            "SELECT e.*, o.confermato FROM eventi e JOIN eventi_organizzatori o ON o.evento_id = e.id
             WHERE $dove AND e.stato = 'programmato' ORDER BY e.inizio, e.id",
            [$id]
        );
    }

    /** Inviti ricevuti dal politico o dal giornalista collegato. */
    public static function inviti(): array
    {
        [$colonna, $id] = self::io();
        if (!in_array($colonna, ['politico_id', 'giornalista_id'], true)) {
            return [];
        }
        return self::leggi(
            "SELECT e.*, i.stato AS invito FROM eventi e JOIN eventi_invitati i ON i.evento_id = e.id
             WHERE i.`$colonna` = ? AND e.stato = 'programmato' ORDER BY e.inizio, e.id",
            [$id]
        );
    }

    /** Eventi a cui partecipa chi è collegato. */
    public static function partecipazioni(): array
    {
        [$colonna, $id] = self::io();
        if ($colonna === '') {
            return [];
        }
        return self::leggi(
            "SELECT e.* FROM eventi e JOIN eventi_partecipanti p ON p.evento_id = e.id
             WHERE p.`$colonna` = ? AND e.stato = 'programmato' ORDER BY e.inizio, e.id",
            [$id]
        );
    }

    /**
     * Scheda completa di un evento con organizzatori, invitati e partecipanti.
     * Null se l'evento è riservato e chi è collegato non c'entra.
     */
    public static function scheda(string $id): ?array
    {
        if (!ctype_digit($id)) {
            return null;
        }
        $evento = self::leggi("SELECT * FROM eventi WHERE id = ? AND stato = 'programmato'", [(int) $id])[0] ?? null;
        if ($evento === null) {
            return null;
        }
        $evento['organizzatori'] = Database::righe(
            "SELECT o.politico_id, o.giornale_id, o.confermato, COALESCE(j.nome, " . self::NOME_POLITICO . ") AS nome,
                    IF(o.giornale_id IS NULL, 'Politico', 'Testata') AS tipo, j.editore_id
             FROM eventi_organizzatori o
             LEFT JOIN giornali j ON j.id = o.giornale_id
             LEFT JOIN politici p ON p.id = o.politico_id LEFT JOIN generalita g ON g.id = p.generalita_id
             WHERE o.evento_id = ? ORDER BY o.id",
            [(int) $id]
        );
        $persone = static fn (string $tabella, string $stato): array => Database::righe(
            "SELECT x.politico_id, x.giornalista_id, " . ($tabella === 'eventi_partecipanti' ? 'x.user_id' : 'NULL AS user_id') . ", $stato AS stato,
                    TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS nome,
                    CASE g.tipo WHEN 'politico' THEN 'Politico' WHEN 'giornalista' THEN 'Giornalista' ELSE 'Lettore' END AS tipo
             FROM `$tabella` x
             LEFT JOIN politici p ON p.id = x.politico_id
             LEFT JOIN giornalisti gi ON gi.id = x.giornalista_id
             " . ($tabella === 'eventi_partecipanti' ? 'LEFT JOIN `user` u ON u.id = x.user_id' : '') . "
             JOIN generalita g ON g.id = COALESCE(p.generalita_id, gi.generalita_id" . ($tabella === 'eventi_partecipanti' ? ', u.generalita_id' : '') . ")
             WHERE x.evento_id = ? ORDER BY x.id",
            [(int) $id]
        );
        $evento['invitati'] = $persone('eventi_invitati', 'x.stato');
        $evento['partecipanti'] = $persone('eventi_partecipanti', "''");

        // Che cosa è, per questo evento, chi è collegato.
        [$colonna, $mio] = self::io();
        $evento['organizzo'] = false;       // sono organizzatore confermato
        $evento['da_confermare'] = false;   // mi hanno indicato come co-organizzatore
        foreach ($evento['organizzatori'] as $o) {
            $sonoIo = (Auth::ruolo() === 'politico' && (int) $o['politico_id'] === Auth::profiloId())
                || (Auth::ruolo() === 'editore' && (int) $o['editore_id'] === Auth::profiloId());
            $evento['organizzo'] = $evento['organizzo'] || ($sonoIo && (int) $o['confermato'] === 1);
            $evento['da_confermare'] = $evento['da_confermare'] || ($sonoIo && (int) $o['confermato'] === 0);
        }
        $evento['mio_invito'] = '';
        foreach ($evento['invitati'] as $i) {
            if ($colonna !== '' && $colonna !== 'user_id' && (int) $i[$colonna] === $mio) {
                $evento['mio_invito'] = $i['stato'];
            }
        }
        $evento['partecipo'] = false;
        foreach ($evento['partecipanti'] as $p) {
            $evento['partecipo'] = $evento['partecipo'] || ($colonna !== '' && (int) $p[$colonna] === $mio);
        }

        $coinvolto = $evento['organizzo'] || $evento['da_confermare'] || $evento['mio_invito'] !== '' || $evento['partecipo'] || $evento['creato_da'] === Auth::id();
        return $evento['pubblico'] || $coinvolto ? $evento : null;
    }

    /** Politici attivi che si possono indicare come co-organizzatori o invitare: [id => nome]. */
    public static function politici(): array
    {
        return array_column(Database::righe(
            "SELECT p.id, CONCAT(" . self::NOME_POLITICO . ", IF(p.partito IS NULL OR p.partito = '', '', CONCAT(' · ', p.partito))) AS nome
             FROM politici p JOIN generalita g ON g.id = p.generalita_id WHERE g.stato = 'attivo' ORDER BY g.cognome, g.nome"
        ), 'nome', 'id');
    }

    /** Giornalisti attivi di tutte le testate: [id => "Nome · Testata"]. */
    public static function giornalisti(): array
    {
        return array_column(Database::righe(
            "SELECT gi.id, CONCAT(" . self::NOME_POLITICO . ", ' · ', j.nome) AS nome
             FROM giornalisti gi JOIN generalita g ON g.id = gi.generalita_id JOIN giornali j ON j.id = gi.giornale_id
             WHERE g.stato = 'attivo' ORDER BY g.cognome, g.nome"
        ), 'nome', 'id');
    }

    // ------------------------------------------------------------
    // Sito pubblico
    // ------------------------------------------------------------

    /** Prossimi eventi pubblici con i nomi degli organizzatori confermati. */
    public static function pubblici(): array
    {
        return self::leggi(
            "SELECT e.*, (SELECT GROUP_CONCAT(COALESCE(j.nome, " . self::NOME_POLITICO . ") ORDER BY o.id SEPARATOR ', ')
                          FROM eventi_organizzatori o
                          LEFT JOIN giornali j ON j.id = o.giornale_id
                          LEFT JOIN politici p ON p.id = o.politico_id LEFT JOIN generalita g ON g.id = p.generalita_id
                          WHERE o.evento_id = e.id AND o.confermato = 1) AS organizzatori,
                    (SELECT COUNT(*) FROM eventi_partecipanti x WHERE x.evento_id = e.id) AS quanti
             FROM eventi e
             WHERE e.pubblico = 1 AND e.stato = 'programmato' AND e.inizio > NOW() - INTERVAL 1 DAY
             ORDER BY e.inizio, e.id LIMIT 100"
        );
    }

    /**
     * Agenda pubblica dei politici: eventi pubblici di cui sono organizzatori confermati.
     * Restituisce [id del politico => [appuntamenti]] nella forma usata dalle schede.
     */
    public static function agendePolitici(): array
    {
        $agende = [];
        foreach (self::leggi(
            "SELECT e.*, o.politico_id FROM eventi e JOIN eventi_organizzatori o ON o.evento_id = e.id
             WHERE o.politico_id IS NOT NULL AND o.confermato = 1 AND e.pubblico = 1 AND e.stato = 'programmato'
               AND e.inizio > NOW() - INTERVAL 30 DAY
             ORDER BY e.inizio, e.id"
        ) as $e) {
            $agende[(int) $e['politico_id']][] = [
                'id' => $e['id'],
                'data' => $e['data'],
                'titolo' => $e['titolo'],
                'dettaglio' => trim($e['luogo'] . ($e['ora'] !== '' ? ' · ore ' . $e['ora'] : ''), ' ·'),
                'creato_da' => $e['creato_da'],
            ];
        }
        return $agende;
    }

    // ------------------------------------------------------------
    // Agenda personale del giornalista
    // ------------------------------------------------------------

    /** Eventi a cui il giornalista partecipa o è invitato (senza quelli rifiutati). */
    public static function perGiornalista(string $giornalista): array
    {
        return array_map(static fn (array $e): array => ['giornalista' => $giornalista] + $e, self::leggi(
            "SELECT e.* FROM eventi e
             WHERE e.stato = 'programmato' AND (
                   EXISTS (SELECT 1 FROM eventi_partecipanti p WHERE p.evento_id = e.id AND p.giornalista_id = ?)
                OR EXISTS (SELECT 1 FROM eventi_invitati i WHERE i.evento_id = e.id AND i.giornalista_id = ? AND i.stato <> 'rifiutato'))
             ORDER BY e.inizio, e.id",
            [(int) $giornalista, (int) $giornalista]
        ));
    }

    /** L'evento, solo se è nell'agenda del giornalista collegato. */
    public static function trova(string $id): ?array
    {
        foreach (self::perGiornalista(Auth::profilo()) as $evento) {
            if ($evento['id'] === $id) {
                return $evento;
            }
        }
        return null;
    }

    /** Nuovo appuntamento riservato nell'agenda del giornalista collegato, organizzato dalla sua testata. */
    public static function crea(array $dati): void
    {
        Database::transazione(static function () use ($dati): void {
            $id = Database::inserisci('eventi', [
                'titolo' => $dati['titolo'],
                'luogo' => $dati['luogo'],
                'inizio' => ora_utc($dati['data'] . ' ' . ($dati['ora'] !== '' ? $dati['ora'] : '00:00')),
                'pubblico' => 0,
                'creato_da' => Auth::id(),
            ]);
            Database::inserisci('eventi_organizzatori', ['evento_id' => $id, 'giornale_id' => Testata::dbId(Auth::testata()), 'confermato' => 1]);
            Database::inserisci('eventi_partecipanti', ['evento_id' => $id, 'giornalista_id' => Auth::profiloId()]);
        });
    }

    /** Toglie l'evento dall'agenda: se l'ha creato il giornalista lo cancella, altrimenti rinuncia a partecipare. */
    public static function elimina(string $id): void
    {
        $evento = self::trova($id);
        if ($evento === null) {
            return;
        }
        if ($evento['creato_da'] === Auth::id()) {
            Database::esegui('DELETE FROM eventi WHERE id = ?', [(int) $id]);
            return;
        }
        Database::esegui('DELETE FROM eventi_partecipanti WHERE evento_id = ? AND giornalista_id = ?', [(int) $id, Auth::profiloId()]);
        Database::esegui("UPDATE eventi_invitati SET stato = 'rifiutato', risposto_il = NOW() WHERE evento_id = ? AND giornalista_id = ?", [(int) $id, Auth::profiloId()]);
    }
}
