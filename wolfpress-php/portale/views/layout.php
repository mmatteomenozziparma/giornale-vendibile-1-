<?php
/**
 * Layout del portale: viene caricato una sola volta.
 * Da qui in poi il JavaScript (assets/js/portale.js) sostituisce solo
 * il contenuto di #wp-shell, senza ricaricare la pagina.
 */
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
        <?php require __DIR__ . '/shell.php'; ?>
    </div>

    <div id="wp-annuncio" class="visually-hidden" aria-live="polite"></div>

    <script src="<?= e(asset_comune('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>" defer></script>
    <script src="<?= e(asset('js/portale.js')) ?>" defer></script>
</body>
</html>
