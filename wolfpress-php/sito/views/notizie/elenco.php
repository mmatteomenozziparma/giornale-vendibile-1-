<?php
/** Elenco delle notizie con i filtri per sezione e testata. */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('notizie')) ?>">Notizie</a><?= $intestazione !== 'Tutte le notizie' ? ' / ' . e($intestazione) : '' ?>
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Notizie</span>
    <h1><?= e($intestazione) ?></h1>
    <p><?= count($articoli) ?> <?= count($articoli) === 1 ? 'articolo pubblicato' : 'articoli pubblicati' ?> dalle testate della piattaforma.</p>
</header>

<div class="wp-filtri">
    <div class="wp-etichette" role="group" aria-label="Filtra per sezione">
        <a class="<?= $sezione === '' ? 'attiva' : '' ?>" href="<?= e(url('notizie', $testata !== '' ? ['testata' => $testata] : [])) ?>">Tutte le sezioni</a>
        <?php foreach ($sezioni as $nome): ?>
            <a class="<?= $nome === $sezione ? 'attiva' : '' ?>" href="<?= e(url('notizie', ['sezione' => $nome] + ($testata !== '' ? ['testata' => $testata] : []))) ?>"<?= $nome === $sezione ? ' aria-current="true"' : '' ?>><?= e($nome) ?></a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="<?= e(url()) ?>" class="wp-filtro-testata">
        <input type="hidden" name="r" value="notizie">
        <?php if ($sezione !== ''): ?>
            <input type="hidden" name="sezione" value="<?= e($sezione) ?>">
        <?php endif; ?>
        <label class="form-label mb-0" for="testata">Testata</label>
        <select class="form-select" id="testata" name="testata">
            <option value="">Tutte le testate</option>
            <?php foreach ($testate as $voceTestata): ?>
                <option value="<?= e($voceTestata['id']) ?>"<?= selezionato($voceTestata['id'], $testata) ?>><?= e($voceTestata['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-wp-bordo" type="submit">Filtra</button>
    </form>
</div>

<?php if (!$articoli): ?>
    <div class="wp-scheda">
        <h2>Nessun articolo</h2>
        <p class="mb-0">Non ci sono articoli pubblicati con questi filtri. <a href="<?= e(url('notizie')) ?>">Vedi tutte le notizie</a>.</p>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($articoli as $articolo): ?>
            <div class="col-md-6 col-lg-4">
                <?php View::parziale('scheda_articolo', ['articolo' => $articolo]); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
