<?php
/** Pagina di un articolo. */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> /
    <a href="<?= e(url('notizie')) ?>">Notizie</a> /
    <a href="<?= e(url('notizie', ['sezione' => $articolo['sezione']])) ?>"><?= e($articolo['sezione']) ?></a>
</nav>

<article class="wp-articolo">
    <header>
        <span class="wp-occhiello"><?= e($articolo['sezione']) ?> · <?= e($testata['nome'] ?? '') ?></span>
        <h1><?= e($articolo['titolo']) ?></h1>
        <p class="wp-sommario"><?= e($articolo['sommario']) ?></p>
        <p class="wp-firma">
            <span class="wp-avatar" aria-hidden="true"><?= e(iniziali($articolo['autore'])) ?></span>
            <span>
                <strong><?= e($articolo['autore']) ?></strong>
                <small><time datetime="<?= e($articolo['data']) ?>"><?= e(data_it($articolo['data'])) ?></time> · <?= e(numero((int) $articolo['letture'])) ?> letture</small>
            </span>
        </p>
    </header>

    <div class="wp-corpo-articolo">
        <?= paragrafi($articolo['corpo']) ?>
    </div>

    <footer>
        <p class="wp-nota mb-0">Contenuto dimostrativo: non è una notizia verificata.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-wp-bordo" href="<?= e(url('notizie', ['sezione' => $articolo['sezione']])) ?>">Altri articoli di <?= e($articolo['sezione']) ?></a>
            <a class="btn btn-wp-bordo" href="<?= e(url('notizie', ['testata' => $articolo['testata']])) ?>">Tutto da <?= e($testata['nome'] ?? 'questa testata') ?></a>
        </div>
    </footer>
</article>

<?php if ($altri): ?>
    <section aria-labelledby="titolo-altri">
        <div class="wp-titolo-sezione">
            <h2 id="titolo-altri">Dalla stessa sezione</h2>
        </div>
        <div class="row g-3">
            <?php foreach ($altri as $altro): ?>
                <div class="col-md-6 col-lg-4">
                    <?php View::parziale('scheda_articolo', ['articolo' => $altro]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
