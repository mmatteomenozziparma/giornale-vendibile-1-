<?php
/**
 * Eventi in agenda assegnati ai giornalisti.
 */
declare(strict_types=1);

final class Evento extends Model
{
    protected const ARCHIVIO = 'eventi';

    public static function perGiornalista(string $giornalista): array
    {
        $righe = array_filter(self::tutti(), static fn (array $e): bool => $e['giornalista'] === $giornalista);
        usort($righe, static fn (array $a, array $b): int => strcmp($a['data'] . $a['ora'], $b['data'] . $b['ora']));
        return $righe;
    }

    public static function crea(array $dati): void
    {
        self::salva(['id' => self::nuovoId('e')] + $dati);
    }
}
