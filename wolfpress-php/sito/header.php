<?php
/**
 * HEADER del sito pubblico: <head> con titolo, stili e apertura di <body>.
 * Incluso all'inizio di ogni pagina, prima di menu.php.
 */
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (config('noindex')): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <meta name="description" content="<?= e($descrizione) ?>">
    <meta name="theme-color" content="#173d33">
    <title><?= e($titolo) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset_comune('favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_comune('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/sito.css')) ?>">
</head>
<body class="wp-sito">
    <a class="wp-salta" href="#contenuto">Vai al contenuto</a>
