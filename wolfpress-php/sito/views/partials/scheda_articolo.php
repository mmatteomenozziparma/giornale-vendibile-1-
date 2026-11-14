<?php
/** Riquadro di un articolo negli elenchi. Variabile: $articolo. */
?>
<article class="wp-scheda wp-notizia h-100">
    <span class="wp-occhiello">
        <a href="<?= e(url('notizie', ['sezione' => $articolo['sezione']])) ?>"><?= e($articolo['sezione']) ?></a>
        · <?= e(Testata::nome($articolo['testata'])) ?>
    </span>
    <h3><a href="<?= e(url('notizie/articolo', ['slug' => $articolo['slug']])) ?>"><?= e($articolo['titolo']) ?></a></h3>
    <p><?= e($articolo['sommario']) ?></p>
    <small><?= e($articolo['autore']) ?> · <time datetime="<?= e($articolo['data']) ?>"><?= e(data_it($articolo['data'])) ?></time></small>
</article>
