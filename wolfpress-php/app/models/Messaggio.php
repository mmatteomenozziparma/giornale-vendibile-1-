<?php
/**
 * Messaggi della chat di redazione (tabella "messaggi_redazione"),
 * divisi per canale e visibili solo alla testata di chi è collegato.
 */
declare(strict_types=1);

final class Messaggio
{
    public static function perCanale(string $canale): array
    {
        return array_map(static fn (array $r): array => [
            'id' => (string) $r['id'],
            'canale' => $r['canale'],
            'autore' => $r['autore'],
            'testo' => $r['testo'],
            'quando' => ora_locale($r['creato_il']),
        ], Database::righe(
            'SELECT m.id, m.canale, m.autore, m.testo, m.creato_il FROM messaggi_redazione m JOIN giornali j ON j.id = m.giornale_id
             WHERE j.slug = ? AND m.canale = ? ORDER BY m.creato_il, m.id',
            [Auth::testata(), $canale]
        ));
    }

    public static function invia(string $canale, string $autore, string $testo): void
    {
        $giornale = Testata::dbId(Auth::testata());
        if ($giornale === 0) {
            return;
        }
        Database::inserisci('messaggi_redazione', [
            'giornale_id' => $giornale,
            'canale' => $canale,
            'autore_id' => Auth::id(),
            'autore' => $autore,
            'testo' => $testo,
        ]);
    }
}
