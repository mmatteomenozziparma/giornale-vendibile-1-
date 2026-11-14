<?php
/**
 * Area cliente: dashboard della redazione.
 * Le sezioni compaiono solo se il relativo modulo è attivo nel configuratore.
 */
$moduli = $impostazioni['moduli'];
$massimo = max($letture ?: [1]);
?>
<section class="wp-copertina" id="panoramica">
    <span class="wp-occhiello">Area riservata · <?= e($impostazioni['testata']) ?></span>
    <h1>Benvenuto, <?= e($impostazioni['cliente']) ?></h1>
    <p><?= e($impostazioni['benvenuto']) ?></p>
    <?php if (!empty($moduli['articoli'])): ?>
        <a class="btn btn-wp-chiaro" href="<?= e(url('articoli/nuovo')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuovo articolo</a>
    <?php endif; ?>
</section>

<div class="row g-3 mb-3">
    <?php foreach ($conteggio as $stato => $quanti): ?>
        <div class="col-6 col-lg-3">
            <div class="wp-numero"><span><?= e($stato) ?></span><strong><?= (int) $quanti ?></strong></div>
        </div>
    <?php endforeach; ?>
    <div class="col-6 col-lg-3">
        <div class="wp-numero"><span>Letture demo · 7 giorni</span><strong><?= e(numero((int) $lettureTotali)) ?></strong></div>
    </div>
</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <?php if (!empty($moduli['articoli'])): ?>
            <section class="wp-scheda mb-3" id="articoli">
                <h2>Scrivania editoriale</h2>
                <p class="wp-sottotitolo">Cerca, filtra e aggiorna gli articoli della testata.</p>

                <div class="row g-2" role="search">
                    <div class="col-sm-6">
                        <input class="form-control" type="search" placeholder="Cerca per titolo o sezione" aria-label="Cerca per titolo o sezione" data-filtro="#elenco-articoli">
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#elenco-articoli">
                            <option value="">Tutti gli stati</option>
                            <?php foreach (Articolo::STATI as $stato): ?>
                                <option><?= e($stato) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="wp-conteggio" data-conteggio="#elenco-articoli" data-singolare="articolo" data-plurale="articoli"><?= count($articoli) ?> articoli</p>

                <ul class="wp-righe" id="elenco-articoli">
                    <?php foreach ($articoli as $articolo): ?>
                        <li data-voce data-stato="<?= e($articolo['stato']) ?>" data-testo="<?= e(mb_strtolower($articolo['titolo'] . ' ' . $articolo['sezione'] . ' ' . $articolo['autore'])) ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <strong><?= e($articolo['titolo']) ?></strong>
                                    <span class="wp-sottotitolo d-block"><?= e($articolo['sezione']) ?> · <?= e($articolo['autore']) ?></span>
                                </div>
                                <?= bollino($articolo['stato']) ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('articoli/modifica', ['id' => $articolo['id']])) ?>">Apri articolo</a>
                                <?php if ($articolo['stato'] === 'Pubblicato'): ?>
                                    <a class="btn btn-wp-bordo btn-sm" href="<?= e(url_sito('notizie/articolo', ['slug' => $articolo['slug']])) ?>" target="_blank" rel="noopener">Vedi sul sito <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($moduli['redazione'])): ?>
            <section class="wp-scheda" id="redazione">
                <div class="wp-scheda-testa">
                    <h2>Redazione</h2>
                    <i class="bi bi-people" aria-hidden="true"></i>
                </div>
                <p class="wp-sottotitolo">Ruoli di esempio per organizzare il flusso editoriale.</p>
                <ul class="mb-0">
                    <?php foreach ($impostazioni['ruoli_redazione'] ?? [] as [$ruolo, $compito]): ?>
                        <li><strong><?= e($ruolo) ?></strong> · <?= e($compito) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>

    <div class="col-xl-5">
        <?php if (!empty($moduli['lettori'])): ?>
            <section class="wp-scheda mb-3" id="lettori">
                <div class="wp-scheda-testa">
                    <h2>Andamento letture</h2>
                    <i class="bi bi-bar-chart" aria-hidden="true"></i>
                </div>
                <p class="wp-sottotitolo">Settimana di esempio · dati dimostrativi</p>

                <div class="wp-grafico" role="img" aria-label="Letture giornaliere della settimana di esempio">
                    <?php foreach ($letture as $giorno => $valore): ?>
                        <div class="wp-grafico-colonna">
                            <span class="wp-grafico-barra" style="height: <?= (int) round($valore / $massimo * 100) ?>%" title="<?= e($giorno . ': ' . numero((int) $valore)) ?>"></span>
                            <small><?= e($giorno) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <details class="wp-dettagli">
                    <summary>Leggi i valori giornalieri</summary>
                    <table class="table table-sm wp-tabella mt-2">
                        <thead><tr><th scope="col">Giorno</th><th scope="col" class="text-end">Letture</th></tr></thead>
                        <tbody>
                            <?php foreach ($letture as $giorno => $valore): ?>
                                <tr><td><?= e($giorno) ?></td><td class="text-end"><?= e(numero((int) $valore)) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            </section>
        <?php endif; ?>

        <section class="wp-scheda mb-3" id="social">
            <h2>Canali social</h2>
            <p class="wp-sottotitolo">Collegamenti configurati per la testata.</p>
            <?php if ($social): ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($social as $nome => $link): ?>
                        <li><a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e($nome) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="wp-sottotitolo mb-0">Nessun canale social configurato.</p>
            <?php endif; ?>
        </section>

        <?php if (!empty($moduli['assistenza'])): ?>
            <section class="wp-scheda" id="assistenza">
                <div class="wp-scheda-testa">
                    <h2>Assistenza</h2>
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                </div>
                <p class="wp-sottotitolo">Contatto per problemi di accesso e richieste tecniche.</p>
                <div class="wp-nota mb-3">
                    <?php if ($impostazioni['email_assistenza'] !== ''): ?>
                        Scrivi a <a href="mailto:<?= e($impostazioni['email_assistenza']) ?>"><?= e($impostazioni['email_assistenza']) ?></a>
                    <?php else: ?>
                        Il contatto di assistenza sarà disponibile quando verrà configurato.
                    <?php endif; ?>
                </div>
                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('assistenza')) ?>">Apri assistenza e ticket</a>
            </section>
        <?php endif; ?>
    </div>
</div>
