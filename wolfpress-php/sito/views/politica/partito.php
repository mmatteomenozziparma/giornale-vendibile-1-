<?php
/** Sezione regionale di un partito: notizie, rete territoriale, agenda, contatti e modelli. */
$parole = explode(' ', $partito['nome'], 2);
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('politica')) ?>">Politica</a> / <?= e($partito['nome']) ?>
</nav>

<section class="wp-copertina-partito">
    <div class="wp-copertina-testo">
        <span class="wp-occhiello">Partito dimostrativo · <?= e($partito['sezione']) ?></span>
        <h1><?= e($parole[0]) ?><?php if (isset($parole[1])): ?><br><?= e($parole[1]) ?><?php endif; ?></h1>
        <p><?= e($partito['descrizione']) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-wp-chiaro" href="#modelli">Esplora i modelli <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
            <a class="btn btn-wp-vetro" href="#agenda">Vedi l'agenda</a>
        </div>
    </div>
    <div class="wp-copertina-mappa">
        <?php View::parziale('mappa', ['rete' => $partito['rete']]); ?>
        <span class="wp-mappa-nota">01 / Territorio dimostrativo</span>
    </div>
</section>

<nav class="wp-ancore" aria-label="Sezioni della pagina">
    <a href="#notizie">Notizie</a>
    <a href="#rete">Rete territoriale</a>
    <a href="#agenda">Agenda</a>
    <a href="#contatti">Contatti</a>
    <a href="#modelli">Modelli</a>
</nav>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="notizie">
            <span class="wp-occhiello">Aggiornamenti</span>
            <h2>Notizie dalla sezione</h2>
            <div class="wp-fisarmonica">
                <?php foreach ($partito['notizie'] as $notizia): ?>
                    <details>
                        <summary>
                            <span>
                                <strong><?= e($notizia['titolo']) ?></strong>
                                <small><?= e($notizia['categoria']) ?> · <?= e(data_it($notizia['data'])) ?></small>
                            </span>
                        </summary>
                        <p><?= e($notizia['testo']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="rete">
            <span class="wp-occhiello">Organizzazione</span>
            <h2>Rete territoriale</h2>
            <p class="wp-testo-breve"><?= e($partito['rete_intro']) ?></p>
            <ul class="wp-righe">
                <?php foreach ($partito['rete'] as $circolo): ?>
                    <li>
                        <strong class="d-block"><?= e($circolo['nome']) ?></strong>
                        <span class="wp-sottotitolo"><?= e($circolo['descrizione']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="agenda">
            <span class="wp-occhiello">Calendario regionale</span>
            <h2>Prossimi appuntamenti</h2>
            <ul class="wp-righe">
                <?php foreach ($partito['appuntamenti'] as $appuntamento): ?>
                    <?php [$giorno, $mese] = giorno_mese($appuntamento['data']); ?>
                    <li class="d-flex align-items-center gap-3">
                        <span class="wp-data"><b><?= e($giorno) ?></b><small><?= e($mese) ?></small></span>
                        <span>
                            <strong class="d-block"><?= e($appuntamento['titolo']) ?></strong>
                            <span class="wp-sottotitolo"><?= e($appuntamento['dettaglio']) ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="contatti">
            <span class="wp-occhiello">Segreteria regionale</span>
            <h2>Contatti</h2>
            <p class="wp-testo-breve"><?= e($partito['orari']) ?></p>
            <p class="wp-recapito"><?= e($partito['email']) ?></p>
            <p class="wp-sottotitolo mb-0">Recapito fittizio.</p>
        </section>
    </div>

    <div class="col-12">
        <?php View::parziale('modelli', ['politici' => $politici]); ?>
    </div>
</div>
