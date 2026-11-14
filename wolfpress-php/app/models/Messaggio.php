<?php
/**
 * Messaggi della chat di redazione, divisi per canale.
 */
declare(strict_types=1);

final class Messaggio extends Model
{
    protected const ARCHIVIO = 'messaggi';

    public static function perCanale(string $canale): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $m): bool => $m['canale'] === $canale));
    }

    public static function invia(string $canale, string $autore, string $testo): void
    {
        self::salva([
            'id' => self::nuovoId('m'),
            'canale' => $canale,
            'autore' => $autore,
            'testo' => $testo,
            'quando' => date('Y-m-d H:i'),
        ]);
    }
}
