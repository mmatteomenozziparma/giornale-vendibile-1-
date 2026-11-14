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
    <div class="wp-nota">Dati fittizi. Gli accessi per ruolo sono simulati e le modifiche restano in questa demo.</div>
</section>
