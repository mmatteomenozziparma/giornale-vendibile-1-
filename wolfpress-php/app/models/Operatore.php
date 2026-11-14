<?php
/**
 * Personale tecnico dell'azienda fornitrice (ingegneri, sistemisti, supporto).
 */
declare(strict_types=1);

final class Operatore extends Model
{
    protected const ARCHIVIO = 'operatori';

    public const RUOLI = ['Sistemista', 'Supporto tecnico', 'Responsabile tecnico', 'Sviluppatore'];
    public const STATI = ['Attivo', 'Sospeso'];

    public static function attivi(): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $o): bool => $o['stato'] === 'Attivo'));
    }

    public static function crea(array $dati): void
    {
        self::salva(['id' => self::nuovoId('o')] + $dati);
    }
}
