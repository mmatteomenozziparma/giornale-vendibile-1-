<?php
/**
 * Store: archivio dei dati su file JSON.
 *
 * - I dati di partenza (demo) sono in app/data/*.json e non vengono mai modificati.
 * - Le modifiche fatte dal portale vengono salvate in storage/*.json.
 * - "Ripristina dati demo" cancella storage/*.json e si torna ai dati di partenza.
 *
 * Per passare a MySQL basta riscrivere i modelli (app/models): controller e viste restano uguali.
 */
declare(strict_types=1);

final class Store
{
    /** @var array<string, array> */
    private static array $memoria = [];

    public static function leggi(string $nome): array
    {
        self::controllaNome($nome);
        if (isset(self::$memoria[$nome])) {
            return self::$memoria[$nome];
        }
        $file = WP_STORAGE . '/' . $nome . '.json';
        if (!is_file($file)) {
            $file = WP_APP . '/data/' . $nome . '.json';
        }
        $dati = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        return self::$memoria[$nome] = is_array($dati) ? $dati : [];
    }

    public static function scrivi(string $nome, array $dati): void
    {
        self::controllaNome($nome);
        if (!is_dir(WP_STORAGE) && !@mkdir(WP_STORAGE, 0775, true)) {
            throw new RuntimeException('Impossibile creare la cartella storage/.');
        }
        $json = json_encode($dati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $file = WP_STORAGE . '/' . $nome . '.json';
        $provvisorio = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        // Scrittura su file provvisorio e rinomina: il file non resta mai scritto a metà.
        if ($json === false || @file_put_contents($provvisorio, $json, LOCK_EX) === false || !@rename($provvisorio, $file)) {
            @unlink($provvisorio);
            throw new RuntimeException('La cartella storage/ non è scrivibile: controlla i permessi.');
        }
        self::$memoria[$nome] = $dati;
    }

    /** Cancella le modifiche e torna ai dati demo di partenza. */
    public static function ripristina(): void
    {
        foreach (glob(WP_STORAGE . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
        self::$memoria = [];
    }

    private static function controllaNome(string $nome): void
    {
        if (!preg_match('/^[a-z]+$/', $nome)) {
            throw new InvalidArgumentException('Nome di archivio non valido');
        }
    }
}
