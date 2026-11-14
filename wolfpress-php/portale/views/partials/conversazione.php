<?php
/**
 * Conversazione di un ticket con il modulo per rispondere.
 * Variabili: $ticket (quello scelto), $rotta (es. 'tecnico/messaggio'), $ruolo ('editore' o 'tecnico').
 */
?>
<div class="wp-chat" data-chat tabindex="0" aria-label="Messaggi del ticket <?= e($ticket['id']) ?>">
    <?php foreach ($ticket['messaggi'] as $messaggio): ?>
        <div class="wp-bolla<?= $messaggio['ruolo'] === $ruolo ? ' wp-bolla-mia' : '' ?>">
            <strong><?= e($messaggio['autore']) ?></strong>
            <p><?= nl2br(e($messaggio['testo'])) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($ticket['video'] !== ''): ?>
    <p class="wp-sottotitolo mt-2 mb-0">
        <i class="bi bi-camera-video" aria-hidden="true"></i>
        <a href="<?= e($ticket['video']) ?>" target="_blank" rel="noopener noreferrer">Apri la videochiamata del ticket</a>
    </p>
<?php endif; ?>

<form method="post" action="<?= e(url($rotta)) ?>" class="mt-3">
    <?= Csrf::campo() ?>
    <input type="hidden" name="id" value="<?= e($ticket['id']) ?>">
    <label class="form-label" for="testo-ticket">Messaggio</label>
    <textarea class="form-control" id="testo-ticket" name="testo" rows="2" maxlength="600" required></textarea>
    <button class="btn btn-wp mt-2" type="submit">Invia messaggio</button>
</form>
