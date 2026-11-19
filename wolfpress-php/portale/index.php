<?php
/**
 * PORTALE WOLFPRESS · single page application
 * per editori, giornalisti, politici e tecnici dell'azienda.
 *
 * Questo file è l'unico ingresso (front controller): tutte le pagine passano da qui.
 *   index.php?r=controller/azione
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/core/PortaleController.php';

Auth::avviaSessione();

try {
    Router::avvia(__DIR__ . '/controllers', 'home/index', static function (string $controller): bool {
        // Pagine aperte a tutti (accesso, registrazione, errori).
        if (Auth::pubblico($controller)) {
            return true;
        }
        // Senza accesso si torna alla pagina di login.
        if (Auth::utente() === null) {
            header('Location: ' . url('accesso'), true, 303);
            return false;
        }
        // I lettori hanno la loro area nel sito pubblico, non nel portale.
        if (Auth::ruolo() === 'user') {
            header('Location: ' . url_sito('account'), true, 303);
            return false;
        }
        // Un editore senza testata deve prima crearla.
        if (Auth::ruolo() === 'editore' && Auth::testata() === '' && $controller !== 'testata') {
            header('Location: ' . url('testata'), true, 303);
            return false;
        }
        // Ogni ruolo apre solo le proprie aree.
        if (!Auth::puo($controller)) {
            require_once __DIR__ . '/controllers/ErroreController.php';
            (new ErroreController())->vietato();
            return false;
        }
        return true;
    });
} catch (Throwable $errore) {
    http_response_code(500);
    require_once __DIR__ . '/controllers/ErroreController.php';
    error_log('Portale: ' . $errore->getMessage() . ' in ' . $errore->getFile() . ':' . $errore->getLine());
    (new ErroreController())->interno(config('debug') ? $errore->getMessage() : '');
}
