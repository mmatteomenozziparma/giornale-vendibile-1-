<?php
/**
 * SITO PUBBLICO WOLFPRESS
 * per i lettori che cercano notizie, politici e partiti.
 *
 * Questo file è l'unico ingresso (front controller): tutte le pagine passano da qui.
 *   index.php?r=controller/azione
 *
 * Le pagine di lettura non creano cookie. La sessione parte solo nell'area
 * account e nelle pagine "sostieni" (oppure se il visitatore è già collegato).
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/core/SitoController.php';

$rotta = is_string($_GET['r'] ?? null) ? $_GET['r'] : '';
// Il webhook di Stripe non ha cookie: lo autentica la firma, non la sessione.
if ($rotta !== 'sostieni/webhook'
    && (isset($_COOKIE['wolfpress']) || str_starts_with($rotta, 'account') || str_starts_with($rotta, 'sostieni'))) {
    Auth::avviaSessione();
}

try {
    Router::avvia(__DIR__ . '/controllers', 'home/index');
} catch (Throwable $errore) {
    error_log('Sito: ' . $errore->getMessage() . ' in ' . $errore->getFile() . ':' . $errore->getLine());
    http_response_code(500);
    require_once __DIR__ . '/controllers/ErroreController.php';
    (new ErroreController())->interno(config('debug') ? $errore->getMessage() : '');
}
