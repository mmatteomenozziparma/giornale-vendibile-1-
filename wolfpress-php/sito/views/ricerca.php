<?php
/** Risultati della ricerca: notizie, politici e partiti. */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / Cerca
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Ricerca</span>
    <h1><?= $ricerca !== '' ? 'Risultati per «' . e($ricerca) . '»' : 'Cerca nel sito' ?></h1>
</header>

<section class="wp-cerca-grande">
    <form method="get" action="<?= e(url()) ?>" role="search">
        <input type="hidden" name="r" value="ricerca">
        <label class="visually-hidden" for="q">Parole da cercare</label>
        <input class="form-control" type="search" id="q" name="q" maxlength="80" required minlength="2" placeholder="Un argomento, un politico, un partito, un Comune..." value="<?= e($ricerca) ?>">
        <label class="visually-hidden" for="dove">Dove cercare</label>
        <select class="form-select" id="dove" name="dove">
            <?php foreach ($ambiti as $chiave => $etichetta): ?>
                <option value="<?= e($chiave) ?>"<?= selezionato($chiave, $dove) ?>><?= e($chiave === 'tutto' ? 'In tutto il sito' : 'Solo ' . mb_strtolower($etichetta)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-wp" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Cerca</button>
    </form>
</section>

<?php if ($ricerca === ''): ?>
    <p class="wp-testo-breve">Scrivi almeno due lettere: la ricerca guarda nei titoli e nei testi delle notizie, nelle schede dei politici e nelle sezioni dei partiti.</p>
<?php elseif (mb_strlen($ricerca) < 2): ?>
    <p class="wp-testo-breve">Scrivi almeno due lettere per avviare la ricerca.</p>
<?php elseif ($totale === 0): ?>
    <div class="wp-scheda">
        <h2>Nessun risultato</h2>
        <p class="mb-0">Prova con una parola diversa oppure sfoglia <a href="<?= e(url('notizie')) ?>">tutte le notizie</a> e la sezione <a href="<?= e(url('politica')) ?>">Politica</a>.</p>
    </div>
<?php else: ?>
    <p class="wp-conteggio" role="status"><?= (int) $totale ?> <?= $totale === 1 ? 'risultato' : 'risultati' ?></p>

    <?php if ($politici): ?>
        <section aria-labelledby="titolo-politici">
            <div class="wp-titolo-sezione"><h2 id="titolo-politici">Politici (<?= count($politici) ?>)</h2></div>
            <div class="row g-3">
                <?php foreach ($politici as $politico): ?>
                    <div class="col-md-6">
                        <div class="wp-scheda h-100 d-flex gap-3">
                            <span class="wp-avatar wp-avatar-grande" aria-hidden="true"><?= e(iniziali($politico['nome'])) ?></span>
                            <div>
                                <span class="wp-occhiello"><?= e(Politico::LIVELLI[$politico['id']]) ?> · <?= e($politico['partito_nome']) ?></span>
                                <h3><?= e($politico['nome']) ?></h3>
                                <p><?= e($politico['bio']) ?></p>
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'candidatura'])) ?>">Candidatura <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                    <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'mandato'])) ?>">Mandato <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($partiti): ?>
        <section aria-labelledby="titolo-partiti">
            <div class="wp-titolo-sezione"><h2 id="titolo-partiti">Partiti (<?= count($partiti) ?>)</h2></div>
            <div class="row g-3">
                <?php foreach ($partiti as $partito): ?>
                    <div class="col-md-6">
                        <a class="wp-partito h-100" href="<?= e(url('politica/partito', ['id' => $partito['id']])) ?>">
                            <span class="wp-occhiello">Partito dimostrativo · <?= e($partito['sezione']) ?></span>
                            <strong><?= e($partito['nome']) ?></strong>
                            <span>Notizie, circoli e appuntamenti <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($notizie): ?>
        <section aria-labelledby="titolo-notizie">
            <div class="wp-titolo-sezione"><h2 id="titolo-notizie">Notizie (<?= count($notizie) ?>)</h2></div>
            <div class="row g-3">
                <?php foreach ($notizie as $articolo): ?>
                    <div class="col-md-6 col-lg-4">
                        <?php View::parziale('scheda_articolo', ['articolo' => $articolo]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
