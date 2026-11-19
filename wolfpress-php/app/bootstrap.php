<?php
/**
 * Avvio comune ai due front end (portale e sito).
 * Viene incluso da portale/index.php e da sito/index.php.
 */
declare(strict_types=1);

define('WP_ROOT', dirname(__DIR__));
define('WP_APP', __DIR__);
define('WP_STORAGE', WP_ROOT . '/storage');

$GLOBALS['wp_config'] = require WP_APP . '/config.php';

// Backend condiviso (database, cifratura, email, Stripe, account): vedi backend/php.
$backend = $GLOBALS['wp_config']['backend'];
if (!is_file($backend . '/src/bootstrap.php')) {
    http_response_code(500);
    exit('Backend non trovato: controlla la voce "backend" in app/config.php.');
}
require $backend . '/src/bootstrap.php';

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Rome');
error_reporting(E_ALL);
ini_set('display_errors', $GLOBALS['wp_config']['debug'] ? '1' : '0');

// Percorsi web: funzionano sia con "php -S" sia dentro una sottocartella di htdocs.
define('APP_BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/'));
define('ROOT_BASE', rtrim(str_replace('\\', '/', dirname(APP_BASE === '' ? '/' : APP_BASE)), '/'));

// Caricamento automatico delle classi di app/core e app/models.
spl_autoload_register(static function (string $classe): void {
    if (!preg_match('/^[A-Za-z]+$/', $classe)) {
        return;
    }
    foreach ([WP_APP . '/core', WP_APP . '/models'] as $cartella) {
        $file = $cartella . '/' . $classe . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require WP_APP . '/core/helpers.php';

// Intestazioni di sicurezza valide per ogni risposta.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
    . "style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; "
    // checkout.stripe.com: il modulo delle donazioni rimanda alla pagina di pagamento di Stripe.
    . "object-src 'none'; base-uri 'self'; form-action 'self' https://checkout.stripe.com; frame-ancestors 'none'"
);
