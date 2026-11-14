<?php
/** Gestione tecnica · Chat: conversazione legata al ticket e link della videochiamata. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Chat di assistenza',
    'descrizione' => 'Segui la conversazione legata a un ticket e condividi un link video.',
]);
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2 class="mb-3">Conversazioni per ticket</h2>
            <?php View::parziale('elenco_ticket', [
                'ticket' => $ticket,
                'scelto' => $scelto['id'] ?? '',
                'rotta' => 'tecnico/chat',
                'segnaposto' => 'Cerca conversazione',
            ]); ?>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <?php if ($scelto === null): ?>
                <h2>Nessuna conversazione</h2>
                <p class="wp-sottotitolo mb-0">Non ci sono ticket aperti.</p>
            <?php else: ?>
                <h2><?= e($scelto['oggetto']) ?></h2>
                <p class="wp-sottotitolo"><?= e($scelto['id']) ?> · <?= e(Testata::nome($scelto['testata'])) ?></p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/ticket', ['id' => $scelto['id']])) ?>">Apri scheda ticket</a>
                    <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/referente', ['id' => $scelto['testata']])) ?>">Storico cliente</a>
                </div>

                <?php View::parziale('conversazione', ['ticket' => $scelto, 'rotta' => 'tecnico/messaggio', 'ruolo' => 'tecnico']); ?>

                <form method="post" action="<?= e(url('tecnico/video')) ?>" class="mt-3">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= e($scelto['id']) ?>">
                    <label class="form-label" for="link">Link HTTPS della videochiamata</label>
                    <input class="form-control" type="url" id="link" name="link" maxlength="300" placeholder="https://" value="<?= e($scelto['video']) ?>">
                    <p class="form-text">Usa il link di una riunione creata su un servizio esterno. Lascia vuoto per rimuoverlo.</p>
                    <button class="btn btn-wp-bordo" type="submit">Salva link video</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
