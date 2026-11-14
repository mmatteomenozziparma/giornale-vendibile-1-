<?php
/** Elenco delle testate ospitate dalla piattaforma. */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / Testate
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">I giornali della piattaforma</span>
    <h1>Le testate</h1>
    <p>Ogni testata è gestita in autonomia dal proprio editore. Scegli un giornale per leggere le sue notizie.</p>
</header>

<div class="row g-3">
    <?php foreach ($testate as $testata): ?>
        <div class="col-md-6">
            <section class="wp-scheda h-100">
                <span class="wp-occhiello"><?= e($testata['dominio']) ?></span>
                <h2><?= e($testata['nome']) ?></h2>
                <p><?= e($testata['descrizione']) ?></p>

                <?php if ($testata['ultimo']): ?>
                    <p class="wp-ultimo">
                        <small>Ultimo articolo</small>
                        <a href="<?= e(url('notizie/articolo', ['slug' => $testata['ultimo']['slug']])) ?>"><?= e($testata['ultimo']['titolo']) ?></a>
                    </p>
                <?php endif; ?>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a class="btn btn-wp" href="<?= e(url('notizie', ['testata' => $testata['id']])) ?>">
                        <?= (int) $testata['quanti'] ?> <?= $testata['quanti'] === 1 ? 'articolo' : 'articoli' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    <?php foreach ($testata['social_attivi'] as $nome => $link): ?>
                        <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e($nome) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>
