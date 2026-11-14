<?php
/** Chat di assistenza dell'editore: una conversazione per ogni ticket. */
View::parziale('intestazione', [
    'occhiello' => $impostazioni['testata'] . ' · demo',
    'titoloPagina' => 'Chat di assistenza',
    'descrizione' => 'Segui la conversazione legata a un ticket e apri il link video condiviso dal tecnico.',
]);
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2 class="mb-3">Conversazioni per ticket</h2>
            <?php View::parziale('elenco_ticket', [
                'ticket' => $ticket,
                'scelto' => $scelto['id'] ?? '',
                'rotta' => 'assistenza/chat',
                'segnaposto' => 'Cerca conversazione',
            ]); ?>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <?php if ($scelto === null): ?>
                <h2>Nessuna conversazione</h2>
                <p class="wp-sottotitolo">Apri un ticket per iniziare a scrivere al team tecnico.</p>
                <a class="btn btn-wp" href="<?= e(url('assistenza', ['nuovo' => '1'])) ?>">Nuovo ticket</a>
            <?php else: ?>
                <h2><?= e($scelto['oggetto']) ?></h2>
                <p class="wp-sottotitolo"><?= e($scelto['id']) ?> · <?= e(Testata::nome($scelto['testata'])) ?></p>
                <a class="btn btn-wp-bordo mb-3" href="<?= e(url('assistenza', ['id' => $scelto['id']])) ?>">Apri scheda ticket</a>
                <?php View::parziale('conversazione', ['ticket' => $scelto, 'rotta' => 'assistenza/messaggio', 'ruolo' => 'editore']); ?>
            <?php endif; ?>
        </section>
    </div>
</div>
