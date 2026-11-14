<?php
/** Politica: indice di partiti, persone e modelli di pagina. */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / Politica
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Atlante civico · modelli per la comunicazione politica</span>
    <h1>Politica</h1>
    <p>Trova un partito, un candidato o un eletto. Ogni persona ha due pagine: la candidatura, con le proposte, e il mandato, con gli atti e le iniziative.</p>
    <span class="wp-demo">Demo: partito, persone, luoghi, date e atti sono fittizi</span>
</header>

<section aria-labelledby="titolo-partiti">
    <div class="wp-titolo-sezione"><h2 id="titolo-partiti">Partiti</h2></div>
    <div class="row g-3">
        <?php foreach ($partiti as $partito): ?>
            <div class="col-lg-6">
                <a class="wp-partito h-100" href="<?= e(url('politica/partito', ['id' => $partito['id']])) ?>">
                    <span class="wp-occhiello">Partito dimostrativo · <?= e($partito['sezione']) ?></span>
                    <strong><?= e($partito['nome']) ?></strong>
                    <span><?= count($partito['rete']) ?> circoli · <?= count($partito['appuntamenti']) ?> appuntamenti in agenda <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if ($notizie): ?>
            <div class="col-lg-6">
                <div class="wp-scheda h-100">
                    <span class="wp-occhiello">Dalle testate</span>
                    <h3>Notizie di politica</h3>
                    <ul class="wp-righe">
                        <?php foreach ($notizie as $articolo): ?>
                            <li>
                                <a class="fw-bold" href="<?= e(url('notizie/articolo', ['slug' => $articolo['slug']])) ?>"><?= e($articolo['titolo']) ?></a>
                                <small class="d-block wp-sottotitolo"><?= e(Testata::nome($articolo['testata'])) ?> · <?= e(data_it($articolo['data'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section aria-labelledby="titolo-persone">
    <div class="wp-titolo-sezione"><h2 id="titolo-persone">Candidati ed eletti</h2></div>
    <div class="row g-3">
        <?php foreach ($politici as $politico): ?>
            <div class="col-md-6 col-lg-4">
                <div class="wp-scheda wp-persona h-100">
                    <div class="d-flex align-items-center gap-3">
                        <span class="wp-avatar wp-avatar-grande" aria-hidden="true"><?= e(iniziali($politico['nome'])) ?></span>
                        <div>
                            <span class="wp-occhiello mb-0"><?= e(Politico::LIVELLI[$politico['id']]) ?></span>
                            <h3 class="mb-0"><?= e($politico['nome']) ?></h3>
                            <small class="wp-sottotitolo"><?= e($politico['partito_nome']) ?></small>
                        </div>
                    </div>
                    <p><?= e($politico['bio']) ?></p>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a class="btn btn-wp-terra btn-sm" href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'candidatura'])) ?>">Candidatura</a>
                        <a class="btn btn-wp btn-sm" href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'mandato'])) ?>">Mandato</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-4">
    <?php View::parziale('modelli', ['politici' => $politici]); ?>
</section>
