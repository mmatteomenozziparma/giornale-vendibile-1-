<?php
/**
 * Ticket di assistenza aperti dagli editori e gestiti dai tecnici.
 * Ogni ticket contiene anche la propria chat (campo "messaggi").
 */
declare(strict_types=1);

final class Ticket extends Model
{
    protected const ARCHIVIO = 'ticket';

    public const STATI = ['Aperto', 'In lavorazione', 'Risolto'];
    public const PRIORITA = ['Bassa', 'Normale', 'Alta', 'Urgente'];

    public static function perTestata(string $testata): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $t): bool => $t['testata'] === $testata));
    }

    /** Conteggio per stato: ['Aperto' => 1, 'In lavorazione' => 2, 'Risolto' => 0]. */
    public static function conta(array $ticket): array
    {
        $conteggio = array_fill_keys(self::STATI, 0);
        foreach ($ticket as $t) {
            $conteggio[$t['stato']] = ($conteggio[$t['stato']] ?? 0) + 1;
        }
        return $conteggio;
    }

    public static function nonRisolti(?string $testata = null): int
    {
        $righe = $testata === null ? self::tutti() : self::perTestata($testata);
        return count(array_filter($righe, static fn (array $t): bool => $t['stato'] !== 'Risolto'));
    }

    /** Prossimo codice libero: T-103, T-104... */
    public static function prossimoCodice(): string
    {
        $massimo = 100;
        foreach (self::tutti() as $ticket) {
            if (preg_match('/^T-(\d+)$/', (string) $ticket['id'], $trovato)) {
                $massimo = max($massimo, (int) $trovato[1]);
            }
        }
        return 'T-' . ($massimo + 1);
    }

    public static function apri(string $testata, string $mittente, string $oggetto, string $priorita, string $descrizione): array
    {
        $ticket = [
            'id' => self::prossimoCodice(),
            'testata' => $testata,
            'oggetto' => $oggetto,
            'priorita' => $priorita,
            'stato' => 'Aperto',
            'mittente' => $mittente,
            'ruolo_mittente' => 'Editore',
            'assegnatario' => '',
            'descrizione' => $descrizione,
            'ultima_risposta' => '',
            'video' => '',
            'aperto_il' => date('Y-m-d'),
            'messaggi' => [
                ['autore' => $mittente, 'ruolo' => 'editore', 'testo' => $descrizione, 'quando' => date('Y-m-d H:i')],
            ],
        ];
        self::salva($ticket);
        return $ticket;
    }

    public static function aggiungiMessaggio(string $id, string $autore, string $ruolo, string $testo): void
    {
        $ticket = self::trova($id);
        if ($ticket === null) {
            return;
        }
        $ticket['messaggi'][] = ['autore' => $autore, 'ruolo' => $ruolo, 'testo' => $testo, 'quando' => date('Y-m-d H:i')];
        if ($ruolo === 'tecnico') {
            $ticket['ultima_risposta'] = $testo;
        }
        self::salva($ticket);
    }
}
