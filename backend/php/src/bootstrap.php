<?php
/**
 * Avvio comune: configurazione, caricamento classi, gestione errori.
 */
declare(strict_types=1);

define('RADICE', dirname(__DIR__));

spl_autoload_register(static function (string $classe): void {
    $file = __DIR__ . '/' . $classe . '.php';
    if (preg_match('/^[A-Za-z]+$/', $classe) && is_file($file)) {
        require $file;
    }
});

Config::carica(RADICE . '/.env');

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');          // mai mostrare errori PHP a chi chiama
ini_set('log_errors', '1');

/** Errore "previsto" da mostrare al client (es. dati non validi). */
final class ErroreApi extends RuntimeException
{
    public function __construct(string $messaggio, public int $stato = 400)
    {
        parent::__construct($messaggio);
    }
}
