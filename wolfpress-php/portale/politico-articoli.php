<?php
/**
 * Articoli firmati dal politico.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Area politica</span>
    <h1>I tuoi articoli</h1>
    <p class="wp-intro">Gli articoli pubblicati compaiono nella tua scheda sul sito e tra le notizie del tuo partito.</p>
</section>

<div class="row g-3 align-items-start">
    <div class="col-xl-5">
        <section class="wp-scheda">
            <h2>Elenco</h2>
            <?php if (!$articoli): ?>
                <p class="wp-sottotitolo mb-0">Non hai ancora scritto articoli.</p>
            <?php else: ?>
                <ul class="wp-righe">
                    <?php foreach ($articoli as $a): ?>
                        <li class="d-flex align-items-center gap-3">
                            <span class="flex-grow-1">
                                <a class="fw-bold d-block" href="<?= e(url('politico/articoli', ['id' => $a['id']])) ?>"><?= e($a['titolo']) ?></a>
                                <span class="wp-sottotitolo"><?= e(data_it($a['data'])) ?><?= $a['categoria'] !== '' ? ' · ' . e($a['categoria']) : '' ?></span>
                            </span>
                            <?= bollino($a['stato']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-xl-7">
        <form class="wp-scheda" method="post" action="<?= e(url('politico/salva-articolo')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= e($articolo['id']) ?>">
            <h2 class="mb-3"><?= $articolo['id'] === '' ? 'Nuovo articolo' : 'Modifica articolo' ?></h2>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="titolo">Titolo</label>
                    <input class="form-control" id="titolo" name="titolo" maxlength="220" required value="<?= e($articolo['titolo']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="categoria">Categoria</label>
                    <input class="form-control" id="categoria" name="categoria" maxlength="120" value="<?= e($articolo['categoria']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="sommario">Sommario</label>
                    <input class="form-control" id="sommario" name="sommario" maxlength="500" value="<?= e($articolo['sommario']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="corpo">Testo</label>
                    <textarea class="form-control" id="corpo" name="corpo" rows="10" maxlength="20000" required><?= e($articolo['corpo']) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="stato">Stato</label>
                    <select class="form-select" id="stato" name="stato">
                        <?php foreach (ArticoloPolitico::STATI as $stato): ?>
                            <option<?= selezionato($stato, $articolo['stato']) ?>><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-wp" type="submit">Salva</button>
                <?php if ($articolo['id'] !== ''): ?>
                    <a class="btn btn-wp-bordo" href="<?= e(url('politico/articoli')) ?>">Nuovo articolo</a>
                    <button class="btn btn-wp-bordo" type="submit" formaction="<?= e(url('politico/elimina-articolo')) ?>" formnovalidate>Elimina</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
