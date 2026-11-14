<?php
/**
 * Router per il server integrato di PHP (solo sviluppo):
 *
 *     php -S localhost:8080 router.php
 *
 * Blocca le cartelle interne (app/, storage/) come fa .htaccess su Apache
 * e lascia servire normalmente tutto il resto.
 */
$percorso = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/(app|storage)(/|$)#i', $percorso) || preg_match('#/\.#', $percorso) || $percorso === '/router.php') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Accesso negato';
    return true;
}

// false = il server integrato serve il file o la cartella richiesta.
return false;
