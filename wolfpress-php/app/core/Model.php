<?php
/**
 * Modello di base: un elenco di righe (array) con un campo "id".
 * Ogni modello indica in ARCHIVIO il nome del proprio file JSON.
 */
declare(strict_types=1);

abstract class Model
{
    /** Nome del file in app/data (senza .json). */
    protected const ARCHIVIO = '';

    public static function tutti(): array
    {
        return Store::leggi(static::ARCHIVIO);
    }

    public static function trova(string $id): ?array
    {
        foreach (static::tutti() as $riga) {
            if ((string) ($riga['id'] ?? '') === $id) {
                return $riga;
            }
        }
        return null;
    }

    /** Aggiorna la riga con lo stesso id oppure la aggiunge in fondo. */
    public static function salva(array $riga): void
    {
        $righe = static::tutti();
        foreach ($righe as $i => $esistente) {
            if ((string) ($esistente['id'] ?? '') === (string) $riga['id']) {
                $righe[$i] = $riga;
                Store::scrivi(static::ARCHIVIO, $righe);
                return;
            }
        }
        $righe[] = $riga;
        Store::scrivi(static::ARCHIVIO, $righe);
    }

    public static function elimina(string $id): void
    {
        $righe = array_values(array_filter(
            static::tutti(),
            static fn (array $riga): bool => (string) ($riga['id'] ?? '') !== $id
        ));
        Store::scrivi(static::ARCHIVIO, $righe);
    }

    /** Identificativo corto e casuale per le nuove righe. */
    protected static function nuovoId(string $prefisso): string
    {
        return $prefisso . bin2hex(random_bytes(4));
    }
}
