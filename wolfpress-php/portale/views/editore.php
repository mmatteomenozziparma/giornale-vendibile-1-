<?php
/** Area editore: account dei giornalisti e chat di redazione. */
?>
<section class="wp-copertina">
    <span class="wp-occhiello"><?= e($impostazioni['testata']) ?> · direzione editoriale</span>
    <h1>Area editore</h1>
    <p>Gestisci gli account di redazione e coordina il lavoro nei canali interni.</p>
    <a class="btn btn-wp-chiaro" href="<?= e(url('assistenza')) ?>">Apri assistenza e ticket</a>
</section>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <div class="wp-numero"><span>Account di redazione</span><strong><?= count($giornalisti) ?></strong></div>
    </div>
    <div class="col-sm-6">
        <div class="wp-numero"><span>Account attivi</span><strong><?= (int) $attivi ?></strong></div>
    </div>
</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda" id="account">
            <div class="wp-scheda-testa">
                <h2>Account giornalisti</h2>
                <i class="bi bi-people" aria-hidden="true"></i>
            </div>
            <p class="wp-sottotitolo">Anagrafica, ruoli e profili dimostrativi.</p>

            <div class="d-flex gap-2" role="search">
                <input class="form-control" type="search" placeholder="Cerca nome, ruolo o sezione" aria-label="Cerca nome, ruolo o sezione" data-filtro="#elenco-giornalisti">
                <a class="btn btn-wp text-nowrap" href="<?= e(url('editore/nuovo')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Aggiungi</a>
            </div>
            <p class="wp-conteggio" data-conteggio="#elenco-giornalisti" data-singolare="account trovato" data-plurale="account trovati"><?= count($giornalisti) ?> account trovati</p>

            <ul class="wp-righe" id="elenco-giornalisti">
                <?php foreach ($giornalisti as $giornalista): ?>
                    <li class="d-flex gap-3" data-voce data-testo="<?= e(mb_strtolower($giornalista['nome'] . ' ' . $giornalista['ruolo'] . ' ' . $giornalista['sezione'])) ?>">
                        <span class="wp-avatar" aria-hidden="true"><?= e(iniziali($giornalista['nome'])) ?></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?= e($giornalista['nome']) ?></strong>
                                    <span class="wp-sottotitolo d-block"><?= e($giornalista['ruolo']) ?> · <?= e($giornalista['sezione']) ?></span>
                                </div>
                                <?= bollino($giornalista['stato']) ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('editore/profilo', ['id' => $giornalista['id']])) ?>">Profilo</a>
                                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('editore/modifica', ['id' => $giornalista['id']])) ?>">Modifica</a>
                                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('editore', ['canale' => in_array($giornalista['sezione'], $canali, true) ? $giornalista['sezione'] : $canali[0]], 'chat')) ?>">Chat</a>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="col-xl-5">
        <?php View::parziale('chat_redazione', [
            'canali' => $canali,
            'canale' => $canale,
            'messaggi' => $messaggi,
            'video' => $video,
            'rotta' => 'editore',
            'io' => 'Editore',
            'configuraVideo' => true,
        ]); ?>
    </div>
</div>
