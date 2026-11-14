<?php
/** Modulo per scrivere o modificare un articolo. */
$nuovo = $articolo['id'] === '';
$editore = ($utente['ruolo'] ?? '') === 'editore';
$indietro = $editore ? url('cliente', [], 'articoli') : url('articoli');
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Scrivania editoriale</span>
    <h1><?= $nuovo ? 'Nuovo articolo' : e($articolo['titolo']) ?></h1>
    <p class="wp-intro">
        <?php if ($nuovo): ?>
            Scrivi il testo e scegli lo stato: bozza, in revisione<?= $editore ? ' o pubblicato' : '' ?>.
        <?php else: ?>
            Autore: <?= e($articolo['autore']) ?> · stato attuale <?= bollino($articolo['stato']) ?>
        <?php endif; ?>
    </p>
</section>

<?php if (!$modificabile): ?>
    <div class="wp-nota mb-3">Questo articolo è già pubblicato: per modificarlo chiedi all'editore.</div>
<?php endif; ?>

<div class="row g-3 align-items-start">
    <div class="col-xl-8">
        <form class="wp-scheda" method="post" action="<?= e(url('articoli/salva')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= e($articolo['id']) ?>">

            <fieldset<?= $modificabile ? '' : ' disabled' ?>>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="titolo">Titolo</label>
                        <input class="form-control" id="titolo" name="titolo" maxlength="120" required value="<?= e($articolo['titolo']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sezione">Sezione</label>
                        <input class="form-control" id="sezione" name="sezione" maxlength="40" required list="sezioni" value="<?= e($articolo['sezione']) ?>">
                        <datalist id="sezioni">
                            <?php foreach (['Attualità', 'Territorio', 'Lavoro', 'Cultura', 'Politica', 'Sport', 'Tecnologia'] as $sezione): ?>
                                <option value="<?= e($sezione) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sommario">Sommario</label>
                        <input class="form-control" id="sommario" name="sommario" maxlength="240" value="<?= e($articolo['sommario']) ?>">
                        <p class="form-text mb-0">Una frase che riassume l'articolo: compare negli elenchi del sito.</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="corpo">Testo</label>
                        <textarea class="form-control" id="corpo" name="corpo" rows="12" maxlength="12000"><?= e($articolo['corpo']) ?></textarea>
                        <p class="form-text mb-0">Lascia una riga vuota per iniziare un nuovo paragrafo.</p>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="stato">Stato</label>
                        <select class="form-select" id="stato" name="stato">
                            <?php foreach ($stati as $stato): ?>
                                <option<?= selezionato($stato, $articolo['stato']) ?>><?= e($stato) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-wp" type="submit">Salva articolo</button>
                    <a class="btn btn-wp-bordo" href="<?= e($indietro) ?>">Annulla</a>
                </div>
            </fieldset>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="wp-scheda mb-3">
            <h2>Flusso editoriale</h2>
            <ol class="wp-flusso">
                <li><strong>Bozza</strong> · il giornalista scrive.</li>
                <li><strong>In revisione</strong> · l'editore controlla.</li>
                <li><strong>Pubblicato</strong> · l'articolo è sul sito.</li>
            </ol>
            <?php if (!$nuovo && $articolo['stato'] === 'Pubblicato'): ?>
                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url_sito('notizie/articolo', ['slug' => $articolo['slug']])) ?>" target="_blank" rel="noopener">Vedi sul sito <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
            <?php endif; ?>
        </div>

        <?php if (!$nuovo && $modificabile): ?>
            <form class="wp-scheda" method="post" action="<?= e(url('articoli/elimina')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= e($articolo['id']) ?>">
                <h2>Elimina</h2>
                <p class="wp-sottotitolo">L'articolo viene tolto dalla scrivania e dal sito.</p>
                <button class="btn btn-wp-bordo btn-sm" type="submit">Elimina articolo</button>
            </form>
        <?php endif; ?>
    </div>
</div>
