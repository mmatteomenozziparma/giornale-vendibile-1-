<?php
/**
 * Account dei giornalisti della redazione.
 */
declare(strict_types=1);

final class Giornalista extends Model
{
    protected const ARCHIVIO = 'giornalisti';

    public const STATI = ['Attivo', 'Sospeso'];

    public static function attivi(): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $g): bool => $g['stato'] === 'Attivo'));
    }

    public static function crea(array $dati): array
    {
        $giornalista = ['id' => self::nuovoId('g')] + $dati;
        self::salva($giornalista);
        return $giornalista;
    }
}
