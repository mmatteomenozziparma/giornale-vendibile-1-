<?php
/**
 * Area politico: gestione della pagina pubblica di candidatura o di mandato.
 * $scenario vale 'candidatura' oppure 'mandato'; $dati contiene i dati di quello scenario.
 */
$mandato = $scenario === 'mandato';
$indirizzoPubblico = url_sito('politica/profilo', ['livello' => $politico['id'], 'tipo' => $scenario]);
?>
<section class="wp-copertina wp-copertina-profilo<?= $mandato ? '' : ' wp-copertina-terra' ?>">
    <span class="wp-occhiello"><?= e(Politico::SCENARI[$scenario]) ?> · <?= e($livello) ?> · scenario demo</span>
    <div class="d-flex align-items-center gap-3">
        <span class="wp-avatar wp-avatar-grande" aria-hidden="true"><?= e(iniziali($politico['nome'])) ?></span>
        <div>
            <h1><?= e($politico['nome']) ?></h1>
            <p class="mb-0"><?= e($dati['ruolo']) ?> · <?= e($dati['luogo']) ?></p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-wp-chiaro" href="<?= e($indirizzoPubblico) ?>" target="_blank" rel="noopener">Vedi la pagina pubblica <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        <a class="btn btn-wp-vetro" href="<?= e(url('politico', ['scenario' => $mandato ? 'candidatura' : 'mandato'])) ?>"><?= $mandato ? 'Passa alla candidatura' : 'Passa al mandato' ?></a>
    </div>
</section>

<div class="wp-nota mb-3">Partito, persone, luoghi, date e atti sono fittizi. Le modifiche compaiono subito nella sezione Politica del sito.</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-6">
        <section class="wp-scheda mb-3" id="profilo">
            <span class="wp-occhiello"><?= $mandato ? 'Attività del mandato' : 'Presentazione' ?></span>
            <h2 class="mb-3"><?= $mandato ? 'Profilo e incarichi' : 'Profilo' ?></h2>

            <form method="post" action="<?= e(url('politico/salva')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="scenario" value="<?= e($scenario) ?>">
                <div class="mb-3">
                    <label class="form-label" for="ruolo"><?= $mandato ? 'Carica' : 'Candidatura' ?></label>
                    <input class="form-control" id="ruolo" name="ruolo" maxlength="80" required value="<?= e($dati['ruolo']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="luogo">Territorio o collegio</label>
                    <input class="form-control" id="luogo" name="luogo" maxlength="80" value="<?= e($dati['luogo']) ?>">
                </div>
                <?php if ($mandato): ?>
                    <div class="mb-3">
                        <label class="form-label" for="incarico">Incarico</label>
                        <input class="form-control" id="incarico" name="incarico" maxlength="160" value="<?= e($dati['incarico'] ?? '') ?>">
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="bio">Presentazione</label>
                    <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="400"><?= e($politico['bio']) ?></textarea>
                    <p class="form-text mb-0">La presentazione è la stessa per candidatura e mandato.</p>
                </div>

                <h3 class="wp-titolo-campi">Contatti</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="contatto">Ufficio o segreteria</label>
                        <input class="form-control" id="contatto" name="contatto" maxlength="80" value="<?= e($dati['contatto']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="120" value="<?= e($dati['email']) ?>">
                    </div>
                </div>
                <button class="btn btn-wp mt-3" type="submit">Salva profilo e contatti</button>
            </form>
        </section>

        <section class="wp-scheda" id="agenda">
            <span class="wp-occhiello"><?= $mandato ? 'Calendario del mandato' : 'Calendario della candidatura' ?></span>
            <h2>Agenda</h2>

            <?php if (!$dati['agenda']): ?>
                <p class="wp-sottotitolo">Nessun appuntamento in agenda.</p>
            <?php else: ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($dati['agenda'] as $indice => $evento): ?>
                        <?php [$giorno, $mese] = giorno_mese($evento['data']); ?>
                        <li class="d-flex align-items-center gap-3">
                            <span class="wp-data"><b><?= e($giorno) ?></b><small><?= e($mese) ?></small></span>
                            <span class="flex-grow-1">
                                <strong class="d-block"><?= e($evento['titolo']) ?></strong>
                                <span class="wp-sottotitolo"><?= e($evento['dettaglio']) ?></span>
                            </span>
                            <form method="post" action="<?= e(url('politico/rimuovi-evento')) ?>">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="scenario" value="<?= e($scenario) ?>">
                                <input type="hidden" name="indice" value="<?= (int) $indice ?>">
                                <button class="btn btn-wp-bordo btn-sm" type="submit" aria-label="Rimuovi <?= e($evento['titolo']) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post" action="<?= e(url('politico/aggiungi-evento')) ?>" class="row g-2">
                <?= Csrf::campo() ?>
                <input type="hidden" name="scenario" value="<?= e($scenario) ?>">
                <div class="col-md-4">
                    <label class="form-label" for="agenda-data">Data</label>
                    <input class="form-control" type="date" id="agenda-data" name="data" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="agenda-titolo">Appuntamento</label>
                    <input class="form-control" id="agenda-titolo" name="titolo" maxlength="80" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="agenda-dettaglio">Luogo e orario</label>
                    <input class="form-control" id="agenda-dettaglio" name="dettaglio" maxlength="80" placeholder="Sala civica · ore 18:00">
                </div>
                <div class="col-12">
                    <button class="btn btn-wp-bordo" type="submit">Aggiungi all'agenda</button>
                </div>
            </form>
        </section>
    </div>

    <div class="col-xl-6">
        <section class="wp-scheda" id="voci">
            <span class="wp-occhiello"><?= $mandato ? 'Rendiconto dimostrativo' : 'Programma dimostrativo' ?></span>
            <h2><?= $mandato ? 'Atti e iniziative' : 'Proposte' ?></h2>

            <?php if (!$dati['voci']): ?>
                <p class="wp-sottotitolo"><?= $mandato ? 'Nessun atto inserito.' : 'Nessuna proposta inserita.' ?></p>
            <?php else: ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($dati['voci'] as $indice => $voce): ?>
                        <li class="d-flex align-items-start gap-3">
                            <span class="flex-grow-1">
                                <strong class="d-block"><?= e($voce['titolo']) ?></strong>
                                <span class="wp-sottotitolo d-block fw-bold">
                                    <?= $mandato ? e(($voce['tipo'] ?? 'Atto') . ' · ' . ($voce['stato'] ?? '')) : 'Proposta ' . sprintf('%02d', $indice + 1) ?>
                                </span>
                                <span class="wp-sottotitolo"><?= e($voce['testo']) ?></span>
                            </span>
                            <form method="post" action="<?= e(url('politico/rimuovi-voce')) ?>">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="scenario" value="<?= e($scenario) ?>">
                                <input type="hidden" name="indice" value="<?= (int) $indice ?>">
                                <button class="btn btn-wp-bordo btn-sm" type="submit" aria-label="Rimuovi <?= e($voce['titolo']) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3 class="wp-titolo-campi"><?= $mandato ? 'Aggiungi un atto' : 'Aggiungi una proposta' ?></h3>
            <form method="post" action="<?= e(url('politico/aggiungi-voce')) ?>" class="row g-2">
                <?= Csrf::campo() ?>
                <input type="hidden" name="scenario" value="<?= e($scenario) ?>">
                <div class="col-12">
                    <label class="form-label" for="voce-titolo">Titolo</label>
                    <input class="form-control" id="voce-titolo" name="titolo" maxlength="120" required>
                </div>
                <?php if ($mandato): ?>
                    <div class="col-md-6">
                        <label class="form-label" for="voce-tipo">Tipo di atto</label>
                        <input class="form-control" id="voce-tipo" name="tipo" maxlength="60" list="tipi-atto" placeholder="Mozione, interrogazione...">
                        <datalist id="tipi-atto">
                            <?php foreach (['Mozione', 'Interrogazione', 'Risoluzione', 'Proposta di legge', 'Disegno di legge', 'Emendamento', 'Ordine del giorno'] as $tipo): ?>
                                <option value="<?= e($tipo) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="voce-stato">Stato dell'iter</label>
                        <input class="form-control" id="voce-stato" name="stato" maxlength="60" list="stati-atto" placeholder="Depositata, in discussione...">
                        <datalist id="stati-atto">
                            <?php foreach (['Depositata', 'Presentata', 'Assegnata', 'In commissione', 'In discussione', 'In attesa di risposta', 'Risposta ricevuta', 'Approvata'] as $stato): ?>
                                <option value="<?= e($stato) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label" for="voce-testo">Descrizione</label>
                    <textarea class="form-control" id="voce-testo" name="testo" rows="3" maxlength="500"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-wp" type="submit"><?= $mandato ? 'Aggiungi atto' : 'Aggiungi proposta' ?></button>
                </div>
            </form>
        </section>
    </div>
</div>
