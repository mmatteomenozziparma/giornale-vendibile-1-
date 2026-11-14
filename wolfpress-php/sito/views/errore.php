<?php
/** Pagina di errore del sito (404 e 500). */
?>
<header class="wp-intestazione-pagina">
    <span class="wp-occhiello"><?= e(config('nome')) ?></span>
    <h1><?= e($titolo) ?></h1>
    <p><?= e($messaggio ?? '') ?></p>
</header>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-wp" href="<?= e(url()) ?>">Torna alla home</a>
    <a class="btn btn-wp-bordo" href="<?= e(url('notizie')) ?>">Tutte le notizie</a>
    <a class="btn btn-wp-bordo" href="<?= e(url('politica')) ?>">Politica</a>
</div>
