<?php
/**
 * Configurazione letta dalle variabili d'ambiente o dal file .env.
 * Nessun segreto è scritto nel codice.
 */
declare(strict_types=1);

final class Config
{
    private static array $valori = [];

    public static function carica(string $fileEnv): void
    {
        if (is_file($fileEnv)) {
            foreach (file($fileEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $riga) {
                $riga = trim($riga);
                if ($riga === '' || $riga[0] === '#' || !str_contains($riga, '=')) {
                    continue;
                }
                [$nome, $valore] = explode('=', $riga, 2);
                $valore = trim($valore);
                if (strlen($valore) >= 2 && ($valore[0] === '"' || $valore[0] === "'") && $valore[-1] === $valore[0]) {
                    $valore = substr($valore, 1, -1);
                }
                self::$valori[trim($nome)] = $valore;
            }
        }
    }

    /** Le variabili d'ambiente reali hanno la precedenza sul file .env. */
    public static function get(string $nome, string $predefinito = ''): string
    {
        $ambiente = getenv($nome);
        if ($ambiente !== false && $ambiente !== '') {
            return $ambiente;
        }
        $valore = self::$valori[$nome] ?? '';
        return $valore !== '' ? $valore : $predefinito;
    }

    /** Come get(), ma si ferma se il valore manca. */
    public static function obbligatorio(string $nome): string
    {
        $valore = self::get($nome);
        if ($valore === '') {
            throw new RuntimeException("Configurazione mancante: $nome");
        }
        return $valore;
    }

    public static function produzione(): bool
    {
        return self::get('APP_ENV', 'sviluppo') === 'produzione';
    }
}
