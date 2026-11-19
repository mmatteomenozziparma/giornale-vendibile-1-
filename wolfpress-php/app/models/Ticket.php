<?php
/**
 * Ticket di assistenza (tabelle "ticket" + "ticket_messaggi") aperti dagli
 * editori e gestiti dai tecnici. L'id usato da viste e indirizzi è il codice (T-...).
 */
declare(strict_types=1);

final class Ticket
{
    public const STATI = ['Aperto', 'In lavorazione', 'Risolto'];
    public const PRIORITA = ['Bassa', 'Normale', 'Alta', 'Urgente'];

    private const STATI_DB = ['aperto' => 'Aperto', 'in_lavorazione' => 'In lavorazione', 'in_attesa_cliente' => 'In lavorazione', 'risolto' => 'Risolto', 'chiuso' => 'Risolto'];

    private static function leggi(string $dove, array $parametri): array
    {
        $righe = Database::righe(
            "SELECT t.*, j.slug AS testata, a.tipo AS tipo_mittente,
                    TRIM(CONCAT(a.nome, ' ', COALESCE(a.cognome, ''))) AS nome_mittente,
                    TRIM(CONCAT(gt.nome, ' ', COALESCE(gt.cognome, ''))) AS assegnatario
             FROM ticket t
             JOIN generalita a ON a.id = t.aperto_da
             LEFT JOIN giornali j ON j.id = t.giornale_id
             LEFT JOIN tecnici te ON te.id = t.tecnico_id
             LEFT JOIN generalita gt ON gt.id = te.generalita_id
             $dove ORDER BY t.creato_il DESC, t.id DESC",
            $parametri
        );
        if ($righe === []) {
            return [];
        }
        $ids = array_column($righe, 'id');
        $messaggi = [];
        foreach (Database::righe(
            "SELECT m.ticket_id, m.testo, m.creato_il, g.tipo, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS autore
             FROM ticket_messaggi m JOIN generalita g ON g.id = m.autore_id
             WHERE m.ticket_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY m.creato_il, m.id',
            $ids
        ) as $m) {
            $messaggi[$m['ticket_id']][] = ['autore' => $m['autore'], 'ruolo' => $m['tipo'], 'testo' => $m['testo'], 'quando' => ora_locale($m['creato_il'])];
        }

        return array_map(static function (array $r) use ($messaggi): array {
            $mittente = (string) ($r['mittente'] ?: $r['nome_mittente']);
            // Il primo messaggio della conversazione è la descrizione del ticket.
            $conversazione = array_merge(
                [['autore' => $mittente, 'ruolo' => $r['tipo_mittente'], 'testo' => $r['descrizione'], 'quando' => ora_locale($r['creato_il'])]],
                $messaggi[$r['id']] ?? []
            );
            $risposte = array_filter($conversazione, static fn (array $m): bool => $m['ruolo'] === 'tecnico');
            return [
                'id' => $r['codice'],
                'db_id' => (int) $r['id'],
                'testata' => (string) $r['testata'],
                'oggetto' => $r['oggetto'],
                'priorita' => ucfirst($r['priorita']),
                'stato' => self::STATI_DB[$r['stato']],
                'mittente' => $mittente,
                'ruolo_mittente' => Auth::RUOLI[$r['tipo_mittente']] ?? 'Lettore',
                'assegnatario' => (string) $r['assegnatario'],
                'descrizione' => $r['descrizione'],
                'ultima_risposta' => $risposte ? end($risposte)['testo'] : '',
                'video' => (string) $r['video'],
                'aperto_il' => ora_locale($r['creato_il'], 'Y-m-d'),
                'messaggi' => $conversazione,
            ];
        }, $righe);
    }

    public static function tutti(): array
    {
        return self::leggi('', []);
    }

    public static function trova(string $id): ?array
    {
        return self::leggi('WHERE t.codice = ?', [$id])[0] ?? null;
    }

    public static function perTestata(string $testata): array
    {
        return $testata === '' ? [] : self::leggi('WHERE j.slug = ?', [$testata]);
    }

    /** Conteggio per stato: ['Aperto' => 1, 'In lavorazione' => 2, 'Risolto' => 0]. */
    public static function conta(array $ticket): array
    {
        $conteggio = array_fill_keys(self::STATI, 0);
        foreach ($ticket as $t) {
            $conteggio[$t['stato']]++;
        }
        return $conteggio;
    }

    public static function nonRisolti(?string $testata = null): int
    {
        $righe = $testata === null ? self::tutti() : self::perTestata($testata);
        return count(array_filter($righe, static fn (array $t): bool => $t['stato'] !== 'Risolto'));
    }

    /** Apre un ticket a nome di chi è collegato e gli manda l'email di conferma. */
    public static function apri(string $testata, string $mittente, string $oggetto, string $priorita, string $descrizione): array
    {
        $codice = 'T-' . strtoupper(bin2hex(random_bytes(5)));
        Database::inserisci('ticket', [
            'codice' => $codice,
            'aperto_da' => Auth::id(),
            'giornale_id' => Testata::dbId($testata) ?: null,
            'oggetto' => $oggetto,
            'descrizione' => $descrizione,
            'mittente' => $mittente,
            'priorita' => mb_strtolower($priorita),
        ]);
        Mailer::ticketAperto(Auth::utente()['email'], Auth::nome(), $codice, $oggetto);
        return self::trova($codice);
    }

    /** Salva stato, assegnatario (nome di un operatore attivo, altrimenti nessuno) e link video. */
    public static function salva(array $ticket): void
    {
        $tecnico = null;
        foreach (Operatore::attivi() as $operatore) {
            if ($operatore['nome'] === $ticket['assegnatario']) {
                $tecnico = (int) $operatore['id'];
            }
        }
        $stato = (string) array_search($ticket['stato'], ['aperto' => 'Aperto', 'in_lavorazione' => 'In lavorazione', 'risolto' => 'Risolto'], true) ?: 'aperto';
        Database::esegui(
            "UPDATE ticket SET stato = ?, tecnico_id = ?, video = ?, chiuso_il = IF(? = 'risolto', COALESCE(chiuso_il, NOW()), NULL) WHERE id = ?",
            [$stato, $tecnico, $ticket['video'] !== '' ? $ticket['video'] : null, $stato, $ticket['db_id']]
        );
    }

    /** Aggiunge alla conversazione un messaggio di chi è collegato. */
    public static function aggiungiMessaggio(string $id, string $testo): void
    {
        $ticket = self::trova($id);
        if ($ticket === null) {
            return;
        }
        Database::inserisci('ticket_messaggi', ['ticket_id' => $ticket['db_id'], 'autore_id' => Auth::id(), 'testo' => $testo]);
        Database::esegui('UPDATE ticket SET aggiornato_il = NOW() WHERE id = ?', [$ticket['db_id']]);
    }
}
