<?php
/** Gestione tecnica · Ticket: coda di assistenza, presa in carico, risposta e assegnazione. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Ticket di assistenza',
    'descrizione' => 'Prendi in carico, rispondi e aggiorna lo stato delle segnalazioni.',
]);
View::parziale('numeri_ticket', ['conteggio' => $conteggio]);
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2 class="mb-3">Coda di assistenza</h2>
            <?php View::parziale('elenco_ticket', [
                'ticket' => $ticket,
                'scelto' => $scelto['id'] ?? '',
                'rotta' => 'tecnico/ticket',
                'segnaposto' => 'Cerca oggetto, codice o testata',
            ]); ?>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <?php if ($scelto === null): ?>
                <h2>Nessun ticket</h2>
                <p class="wp-sottotitolo mb-0">La coda di assistenza è vuota.</p>
            <?php else: ?>
                <h2><?= e($scelto['oggetto']) ?></h2>
                <p class="wp-sottotitolo"><?= e($scelto['id']) ?> · <?= e(Testata::nome($scelto['testata'])) ?></p>
                <?php View::parziale('scheda_ticket', ['ticket' => $scelto]); ?>

                <form method="post" action="<?= e(url('tecnico/salva-ticket')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= e($scelto['id']) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="stato">Stato</label>
                        <select class="form-select" id="stato" name="stato">
                            <?php foreach (Ticket::STATI as $stato): ?>
                                <option<?= selezionato($stato, $scelto['stato']) ?>><?= e($stato) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="assegnatario">Assegnatario tecnico</label>
                        <select class="form-select" id="assegnatario" name="assegnatario">
                            <option value="">Da assegnare</option>
                            <option<?= selezionato('Team tecnico', $scelto['assegnatario']) ?>>Team tecnico</option>
                            <?php foreach ($operatori as $operatore): ?>
                                <option<?= selezionato($operatore['nome'], $scelto['assegnatario']) ?>><?= e($operatore['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="risposta">Risposta al cliente</label>
                        <textarea class="form-control" id="risposta" name="risposta" rows="3" maxlength="600"></textarea>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-wp" type="submit">Salva aggiornamento</button>
                        <button class="btn btn-wp-bordo" type="submit" formaction="<?= e(url('tecnico/prendi')) ?>">Prendi in carico</button>
                        <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/chat', ['id' => $scelto['id']])) ?>">Apri chat del ticket</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
