<?php
/**
 * HEADER del portale: <head> con titolo, stili e apertura di <body>.
 * Incluso all'inizio di ogni pagina, prima di menu.php.
 *
 * Il portale è una single page application: quando la pagina è chiesta dal
 * JavaScript (assets/js/portale.js) si manda solo il titolo, perché il
 * browser sostituisce soltanto il contenuto di #wp-shell.
 */
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
if (View::richiestaSpa()) {
    header('Vary: X-Requested-With');
    echo '<title>' . e($titolo) . '</title>';
    return;
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#152a20">
    <title><?= e($titolo) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset_comune('favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/portale.css')) ?>">
</head>
<body class="wp-portale">
    <a class="wp-salta" href="#wp-main">Vai al contenuto</a>
    <div class="wp-avanzamento" aria-hidden="true"></div>

    <div id="wp-shell">
