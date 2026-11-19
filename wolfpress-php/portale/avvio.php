<?php
/**
 * AVVIO del portale: incluso in cima a ogni pagina con require_once.
 * Carica la parte comune (app/), il controller di base, la sessione
 * e il controllo dei permessi eseguito prima di ogni azione.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/core/PortaleController.php';

Auth::avviaSessione();

Pagina::$controllo = static function (string $controller): bool {
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
        Pagina::errore('vietato');
    }
    return true;
};
