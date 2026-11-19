<?php
/**
 * Intestazione di pagina con occhiello, titolo, descrizione e nota della demo.
 * Variabili: $occhiello, $titoloPagina, $descrizione.
 */
?>
<section class="wp-intestazione">
    <span class="wp-occhiello"><?= e($occhiello) ?></span>
    <h1><?= e($titoloPagina) ?></h1>
    <p class="wp-intro"><?= e($descrizione) ?></p>
    <?php if (config('demo')): ?>
        <div class="wp-nota">Dati dimostrativi: testate, persone e contenuti caricati con l'importazione di prova sono fittizi.</div>
    <?php endif; ?>
</section>
