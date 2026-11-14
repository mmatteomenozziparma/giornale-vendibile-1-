<?php
/** Gestione tecnica · Referente cliente: chi segue il cliente e storico delle sue segnalazioni. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Referente cliente',
    'descrizione' => 'Consulta lo storico delle segnalazioni per il cliente che segui.',
]);
?>
<div class="row g-3 align-items-start">
    <div class="col-md-4 col-xl-3">
        <section class="wp-scheda">
            <span class="wp-occhiello">Referente del fornitore</span>
            <?php if ($referente): ?>
                <h2><?= e($referente['nome']) ?></h2>
                <div class="wp-ritratto" aria-hidden="true"><?= e(iniziali($referente['nome'])) ?></div>
                <strong class="d-block"><?= e($referente['ruolo']) ?></strong>
                <span class="wp-sottotitolo">Turno o reperibilità · <?= e($referente['turno']) ?></span>
            <?php else: ?>
                <h2>Da assegnare</h2>
                <p class="wp-sottotitolo mb-0">Nessun referente collegato a questo cliente.</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-md-8 col-xl-9">
        <section class="wp-scheda">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-lg-7">
                    <span class="wp-occhiello">Storico assistenza</span>
                    <h2><?= e($scelta['cliente']) ?></h2>
                    <span class="wp-sottotitolo"><?= e($scelta['nome']) ?> · <?= e($scelta['dominio']) ?></span>
                </div>
                <div class="col-lg-5">
                    <form method="get" action="<?= e(url()) ?>">
                        <input type="hidden" name="r" value="tecnico/referente">
                        <label class="form-label" for="id">Seleziona cliente</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="id" name="id" data-invio-automatico>
                                <?php foreach ($testate as $testata): ?>
                                    <option value="<?= e($testata['id']) ?>"<?= selezionato($testata['id'], $scelta['id']) ?>><?= e($testata['cliente']) ?> · <?= e($testata['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button class="btn btn-wp-bordo" type="submit">Apri</button></noscript>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-2" role="search">
                <div class="col-sm-6">
                    <input class="form-control" type="search" placeholder="Cerca ID, problema o mittente" aria-label="Cerca ID, problema o mittente" data-filtro="#storico-ticket">
                </div>
                <div class="col-sm-6">
                    <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#storico-ticket">
                        <option value="">Tutti gli stati</option>
                        <?php foreach (Ticket::STATI as $stato): ?>
                            <option><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="wp-conteggio" data-conteggio="#storico-ticket" data-singolare="risultato" data-plurale="risultati"><?= count($ticket) ?> risultati</p>

            <div class="table-responsive">
                <table class="table wp-tabella">
                    <thead>
                        <tr><th scope="col">ID ticket</th><th scope="col">Problema</th><th scope="col">Mittente</th><th scope="col">Stato</th><th scope="col">Chat</th></tr>
                    </thead>
                    <tbody id="storico-ticket">
                        <?php foreach ($ticket as $t): ?>
                            <tr data-voce data-stato="<?= e($t['stato']) ?>" data-testo="<?= e(mb_strtolower($t['id'] . ' ' . $t['oggetto'] . ' ' . $t['mittente'])) ?>">
                                <td><a class="fw-bold" href="<?= e(url('tecnico/ticket', ['id' => $t['id']])) ?>"><?= e($t['id']) ?></a></td>
                                <td><strong><?= e($t['oggetto']) ?></strong></td>
                                <td><?= e($t['mittente']) ?><small><?= e($t['ruolo_mittente']) ?></small></td>
                                <td><?= bollino($t['stato']) ?></td>
                                <td>
                                    <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('tecnico/chat', ['id' => $t['id']])) ?>" aria-label="Apri la chat del ticket <?= e($t['id']) ?>">
                                        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$ticket): ?>
                <p class="wp-sottotitolo mb-0 mt-2">Nessuna segnalazione per questo cliente.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
