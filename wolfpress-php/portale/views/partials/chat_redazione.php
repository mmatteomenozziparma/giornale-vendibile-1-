<?php
/**
 * Chat di redazione (usata nell'area editore e nel profilo giornalista).
 *
 * Variabili: $canali, $canale, $messaggi, $video, $rotta (es. 'editore'),
 *            $io (nome con cui si scrive), $configuraVideo (true per l'editore).
 */
?>
<section class="wp-scheda" id="chat">
    <div class="wp-scheda-testa">
        <h2>Chat di redazione</h2>
        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
    </div>
    <p class="wp-sottotitolo">I messaggi sono condivisi tra editore e giornalisti della demo.</p>

    <form method="get" action="<?= e(url()) ?>" data-ancora="chat">
        <input type="hidden" name="r" value="<?= e($rotta) ?>">
        <label class="form-label" for="canale">Canale</label>
        <div class="d-flex gap-2">
            <select class="form-select" id="canale" name="canale" data-invio-automatico>
                <?php foreach ($canali as $nome): ?>
                    <option<?= selezionato($nome, $canale) ?>><?= e($nome) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn btn-wp-bordo" type="submit">Apri</button></noscript>
        </div>
    </form>

    <p class="wp-sottotitolo mt-2 mb-2">
        <?php if ($video !== ''): ?>
            <i class="bi bi-camera-video" aria-hidden="true"></i>
            <a href="<?= e($video) ?>" target="_blank" rel="noopener noreferrer">Apri la videochiamata del canale</a>
        <?php else: ?>
            Nessuna videochiamata configurata per questo canale.
        <?php endif; ?>
    </p>

    <div class="wp-chat" data-chat tabindex="0" aria-label="Messaggi del canale <?= e($canale) ?>">
        <?php foreach ($messaggi as $messaggio): ?>
            <div class="wp-bolla<?= $messaggio['autore'] === $io ? ' wp-bolla-mia' : '' ?>">
                <strong><?= e($messaggio['autore']) ?></strong>
                <p><?= nl2br(e($messaggio['testo'])) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$messaggi): ?>
            <p class="wp-sottotitolo m-0">Ancora nessun messaggio in questo canale.</p>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(url($rotta . '/messaggio')) ?>" class="mt-3">
        <?= Csrf::campo() ?>
        <input type="hidden" name="canale" value="<?= e($canale) ?>">
        <label class="form-label" for="testo-chat">Messaggio</label>
        <textarea class="form-control" id="testo-chat" name="testo" rows="2" maxlength="600" required placeholder="Scrivi alla redazione..."></textarea>
        <button class="btn btn-wp mt-2" type="submit">Invia messaggio</button>
    </form>

    <?php if (!empty($configuraVideo)): ?>
        <details class="wp-dettagli mt-3"<?= $video !== '' ? ' open' : '' ?>>
            <summary>Configura videochiamata</summary>
            <form method="post" action="<?= e(url($rotta . '/video')) ?>" class="mt-2">
                <?= Csrf::campo() ?>
                <input type="hidden" name="canale" value="<?= e($canale) ?>">
                <label class="form-label" for="link-video">Link HTTPS della videochiamata</label>
                <input class="form-control" type="url" id="link-video" name="link" maxlength="300" placeholder="https://" value="<?= e($video) ?>">
                <p class="form-text">Usa il link di una riunione creata su un servizio esterno. Lascia vuoto per rimuoverlo.</p>
                <button class="btn btn-wp-bordo btn-sm" type="submit">Salva link video</button>
            </form>
        </details>
    <?php endif; ?>
</section>
