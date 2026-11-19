<?php
/**
 * Pagina di errore del portale (404, 403, 500).
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress</span>
    <h1><?= e($titolo) ?></h1>
    <p class="wp-intro"><?= e($messaggio ?? '') ?></p>
</section>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-wp" href="<?= e($utente ? url('home') : url('accesso')) ?>">Torna al portale</a>
    <a class="btn btn-wp-bordo" href="<?= e(url('accesso')) ?>">Accesso</a>
</div>
<?php require __DIR__ . '/footer.php'; ?>
