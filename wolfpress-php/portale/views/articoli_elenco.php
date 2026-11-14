<?php
/** Elenco degli articoli scritti dal giornalista collegato. */
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · redazione</span>
    <h1>I miei articoli</h1>
    <p class="wp-intro">Scrivi una bozza e mandala in revisione: sarà l'editore a pubblicarla sul sito.</p>
</section>

<section class="wp-scheda">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="row g-2 flex-grow-1" role="search">
            <div class="col-sm-6">
                <input class="form-control" type="search" placeholder="Cerca per titolo o sezione" aria-label="Cerca per titolo o sezione" data-filtro="#elenco-articoli">
            </div>
            <div class="col-sm-4">
                <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#elenco-articoli">
                    <option value="">Tutti gli stati</option>
                    <?php foreach (Articolo::STATI as $stato): ?>
                        <option><?= e($stato) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <a class="btn btn-wp" href="<?= e(url('articoli/nuovo')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuovo articolo</a>
    </div>

    <p class="wp-conteggio" data-conteggio="#elenco-articoli" data-singolare="articolo" data-plurale="articoli"><?= count($articoli) ?> articoli</p>

    <ul class="wp-righe" id="elenco-articoli">
        <?php foreach ($articoli as $articolo): ?>
            <li data-voce data-stato="<?= e($articolo['stato']) ?>" data-testo="<?= e(mb_strtolower($articolo['titolo'] . ' ' . $articolo['sezione'])) ?>">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <strong><?= e($articolo['titolo']) ?></strong>
                        <span class="wp-sottotitolo d-block"><?= e($articolo['sezione']) ?> · <?= e(data_it($articolo['data'])) ?> · <?= e(numero((int) $articolo['letture'])) ?> letture</span>
                    </div>
                    <?= bollino($articolo['stato']) ?>
                </div>
                <a class="btn btn-wp-bordo btn-sm mt-2" href="<?= e(url('articoli/modifica', ['id' => $articolo['id']])) ?>">Apri articolo</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$articoli): ?>
        <p class="wp-sottotitolo mb-0">Non hai ancora scritto articoli.</p>
    <?php endif; ?>
</section>
