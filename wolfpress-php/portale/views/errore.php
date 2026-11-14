<?php
/** Pagina di errore del portale (404, 403, 500). */
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · demo</span>
    <h1><?= e($titolo) ?></h1>
    <p class="wp-intro"><?= e($messaggio ?? '') ?></p>
</section>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-wp" href="<?= e($utente ? url('home') : url('accesso')) ?>">Torna al portale</a>
    <a class="btn btn-wp-bordo" href="<?= e(url('accesso')) ?>">Accessi demo</a>
</div>
