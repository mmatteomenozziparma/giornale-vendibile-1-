<?php
/** Dati principali di un ticket (sola lettura). Variabile: $ticket. */
?>
<dl class="wp-dati">
    <dt>Priorità e stato</dt>
    <dd><?= bollino($ticket['priorita']) ?> <?= bollino($ticket['stato']) ?></dd>

    <dt>Aperto da</dt>
    <dd><?= e($ticket['mittente']) ?> · <?= e($ticket['ruolo_mittente']) ?></dd>

    <dt>Assegnazione</dt>
    <dd><?= e($ticket['assegnatario'] !== '' ? $ticket['assegnatario'] : 'Da assegnare') ?></dd>

    <dt>Descrizione</dt>
    <dd><?= nl2br(e($ticket['descrizione'])) ?></dd>

    <dt>Ultima risposta tecnica</dt>
    <dd><?= e($ticket['ultima_risposta'] !== '' ? $ticket['ultima_risposta'] : 'In attesa di risposta') ?></dd>
</dl>
