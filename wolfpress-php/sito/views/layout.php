<?php
/**
 * Layout del sito pubblico: testata con menu, contenuto della pagina e piè di pagina.
 */
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

    <?php require __DIR__ . '/menu.php'; ?>

    <main id="contenuto" class="container wp-contenuto" tabindex="-1">
        <?= $contenuto ?>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>

    <script src="<?= e(asset_comune('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>" defer></script>
    <script src="<?= e(asset('js/sito.js')) ?>" defer></script>
</body>
</html>
