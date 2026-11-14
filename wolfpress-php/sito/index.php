<?php
/**
 * SITO PUBBLICO WOLFPRESS
 * per i lettori che cercano notizie, politici e partiti.
 *
 * Questo file è l'unico ingresso (front controller): tutte le pagine passano da qui.
 *   index.php?r=controller/azione
 *
 * Il sito non usa sessioni né cookie: legge soltanto i dati pubblicati dal portale.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/core/SitoController.php';

try {
    Router::avvia(__DIR__ . '/controllers', 'home/index');
} catch (Throwable $errore) {
    http_response_code(500);
    require_once __DIR__ . '/controllers/ErroreController.php';
    (new ErroreController())->interno(config('debug') ? $errore->getMessage() : '');
}
