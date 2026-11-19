<?php
/**
 * Articolo firmato da un politico.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('politica')) ?>">Politica</a> /
    <a href="<?= e(url('politica/profilo', ['livello' => $articolo['politico']])) ?>"><?= e($articolo['autore']) ?></a>
</nav>

<article class="wp-scheda">
    <span class="wp-occhiello"><?= e($articolo['categoria'] !== '' ? $articolo['categoria'] : 'Dal politico') ?></span>
    <h1><?= e($articolo['titolo']) ?></h1>
    <p class="wp-sottotitolo">
        Di <a href="<?= e(url('politica/profilo', ['livello' => $articolo['politico']])) ?>"><?= e($articolo['autore']) ?></a> · <?= e(data_it($articolo['data'])) ?>
    </p>
    <?php if ($articolo['sommario'] !== ''): ?>
        <p class="fw-bold"><?= e($articolo['sommario']) ?></p>
    <?php endif; ?>
    <?= paragrafi($articolo['corpo']) ?>
    <p class="wp-nota mb-0">Testo scritto e pubblicato dal politico: non è un articolo della redazione.</p>
</article>
<?php require __DIR__ . '/footer.php'; ?>
