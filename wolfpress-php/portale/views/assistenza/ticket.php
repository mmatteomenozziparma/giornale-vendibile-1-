<?php
/** Assistenza editore: elenco dei ticket della testata, dettaglio e apertura di un nuovo ticket. */
View::parziale('intestazione', [
    'occhiello' => $impostazioni['testata'] . ' · demo',
    'titoloPagina' => 'Assistenza editore',
    'descrizione' => 'Apri una segnalazione e segui le risposte del fornitore.',
]);
View::parziale('numeri_ticket', ['conteggio' => $conteggio]);
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2>Ticket di <?= e($impostazioni['testata']) ?></h2>
            <a class="btn btn-wp my-2" href="<?= e(url('assistenza', ['nuovo' => '1'])) ?>">Nuovo ticket</a>
            <?php View::parziale('elenco_ticket', [
                'ticket' => $ticket,
                'scelto' => $scelto['id'] ?? '',
                'rotta' => 'assistenza',
                'segnaposto' => 'Cerca oggetto, codice o testata',
            ]); ?>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <?php if ($nuovo || $scelto === null): ?>
                <h2>Nuovo ticket</h2>
                <p class="wp-sottotitolo">Testata: <?= e($impostazioni['testata']) ?></p>

                <form method="post" action="<?= e(url('assistenza/crea')) ?>">
                    <?= Csrf::campo() ?>
                    <div class="mb-3">
                        <label class="form-label" for="mittente">Nome e cognome del mittente</label>
                        <input class="form-control" id="mittente" name="mittente" maxlength="80" value="<?= e($utente['nome']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="oggetto">Oggetto</label>
                        <input class="form-control" id="oggetto" name="oggetto" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="priorita">Priorità</label>
                        <select class="form-select" id="priorita" name="priorita">
                            <?php foreach (Ticket::PRIORITA as $priorita): ?>
                                <option<?= selezionato($priorita, 'Normale') ?>><?= e($priorita) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="descrizione">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="4" maxlength="1200" required></textarea>
                    </div>
                    <button class="btn btn-wp" type="submit">Apri ticket</button>
                </form>
            <?php else: ?>
                <h2><?= e($scelto['oggetto']) ?></h2>
                <p class="wp-sottotitolo"><?= e($scelto['id']) ?> · <?= e(Testata::nome($scelto['testata'])) ?></p>
                <?php View::parziale('scheda_ticket', ['ticket' => $scelto]); ?>
                <a class="btn btn-wp-bordo" href="<?= e(url('assistenza/chat', ['id' => $scelto['id']])) ?>">Apri chat del ticket</a>
            <?php endif; ?>
        </section>
    </div>
</div>
