<?php
/**
 * AVVIO del sito pubblico: incluso in cima a ogni pagina con require_once.
 * Carica la parte comune (app/), il controller di base e la sessione.
 *
 * Le pagine di lettura non creano cookie: la sessione parte solo nelle pagine
 * dell'account e in "sostieni" (oppure se il visitatore è già collegato).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/core/SitoController.php';

$wpPagina = basename($_SERVER['SCRIPT_FILENAME'], '.php');
if (isset($_COOKIE['wolfpress']) || in_array($wpPagina, ['login', 'registrazione', 'password', 'account', 'sostieni'], true)) {
    Auth::avviaSessione();
}
unset($wpPagina);
