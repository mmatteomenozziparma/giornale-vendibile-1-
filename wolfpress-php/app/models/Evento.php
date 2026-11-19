<?php
/**
 * Agenda dei giornalisti, letta dalle tabelle degli eventi: un giornalista vede
 * gli eventi a cui partecipa e quelli a cui è stato invitato. Gli appuntamenti
 * che aggiunge da sé sono eventi riservati organizzati dalla sua testata.
 */
declare(strict_types=1);

final class Evento
{
    public static function perGiornalista(string $giornalista): array
    {
        $righe = Database::righe(
            "SELECT e.id, e.titolo, e.luogo, e.inizio, e.creato_da FROM eventi e
             WHERE e.stato = 'programmato' AND (
                   EXISTS (SELECT 1 FROM eventi_partecipanti p WHERE p.evento_id = e.id AND p.giornalista_id = ?)
                OR EXISTS (SELECT 1 FROM eventi_invitati i WHERE i.evento_id = e.id AND i.giornalista_id = ? AND i.stato <> 'rifiutato'))
             ORDER BY e.inizio, e.id",
            [(int) $giornalista, (int) $giornalista]
        );
        return array_map(static fn (array $r): array => [
            'id' => (string) $r['id'],
            'giornalista' => $giornalista,
            'titolo' => $r['titolo'],
            'data' => ora_locale($r['inizio'], 'Y-m-d'),
            'ora' => ora_locale($r['inizio'], 'H:i') === '00:00' ? '' : ora_locale($r['inizio'], 'H:i'),
            'luogo' => (string) $r['luogo'],
            'creato_da' => (int) $r['creato_da'],
        ], $righe);
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

    /** Nuovo appuntamento nell'agenda del giornalista collegato. */
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
