<?php
/**
 * Configuratore della testata: identità, moduli visibili e anteprima in tempo reale.
 * L'anteprima si aggiorna mentre scrivi (vedi "anteprima" in assets/js/portale.js).
 */
$logo = $impostazioni['logo'] ?? '';
$haLogo = is_array($logo);
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Studio editoriale</span>
    <h1>Configura la testata</h1>
    <p class="wp-intro">Imposta l'identità del giornale e scegli cosa vedrà il cliente nella sua area riservata.</p>
</section>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <form method="post" action="<?= e(url('configuratore/salva')) ?>" enctype="multipart/form-data" id="modulo-configuratore">
            <?= Csrf::campo() ?>

            <div class="wp-scheda mb-3" id="identita">
                <div class="wp-scheda-testa">
                    <div>
                        <h2>Identità del giornale</h2>
                        <p class="wp-sottotitolo">Testata, logo e messaggio di benvenuto.</p>
                    </div>
                    <span class="wp-passo">01</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="testata">Nome della testata</label>
                        <input class="form-control" id="testata" name="testata" maxlength="60" required value="<?= e($impostazioni['testata']) ?>" data-anteprima="testata">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cliente">Nome del cliente o della redazione</label>
                        <input class="form-control" id="cliente" name="cliente" maxlength="80" value="<?= e($impostazioni['cliente']) ?>" data-anteprima="cliente">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="benvenuto">Messaggio di benvenuto</label>
                        <textarea class="form-control" id="benvenuto" name="benvenuto" rows="2" maxlength="240" data-anteprima="benvenuto"><?= e($impostazioni['benvenuto']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="colore">Colore principale</label>
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-control form-control-color" type="color" id="colore" name="colore" value="<?= e($impostazioni['colore']) ?>" data-anteprima-colore>
                            <strong class="wp-codice-colore" data-anteprima-codice><?= e($impostazioni['colore']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email_assistenza">Email di assistenza</label>
                        <input class="form-control" type="email" id="email_assistenza" name="email_assistenza" maxlength="120" placeholder="assistenza@esempio.it" value="<?= e($impostazioni['email_assistenza']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-0" for="logo">Logo della testata</label>
                        <p class="form-text mt-0 mb-1"><strong>PNG, JPEG o WebP · massimo 1 MB</strong></p>
                        <input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" data-anteprima-logo>
                    </div>
                </div>
            </div>

            <div class="wp-scheda mb-3" id="moduli">
                <div class="wp-scheda-testa">
                    <div>
                        <h2>Moduli visibili</h2>
                        <p class="wp-sottotitolo">Attiva le sezioni disponibili per il cliente.</p>
                    </div>
                    <span class="wp-passo">02</span>
                </div>

                <ul class="wp-righe">
                    <?php foreach (Impostazioni::MODULI as $chiave => [$nome, $descrizione, $icona]): ?>
                        <li class="d-flex align-items-center gap-3">
                            <i class="bi <?= e($icona) ?>" aria-hidden="true"></i>
                            <label class="flex-grow-1" for="modulo-<?= e($chiave) ?>">
                                <strong class="d-block"><?= e($nome) ?></strong>
                                <span class="wp-sottotitolo"><?= e($descrizione) ?></span>
                            </label>
                            <input class="form-check-input wp-spunta" type="checkbox" id="modulo-<?= e($chiave) ?>" name="moduli[]" value="<?= e($chiave) ?>"
                                   data-anteprima-modulo="<?= e($chiave) ?>"<?= !empty($impostazioni['moduli'][$chiave]) ? ' checked' : '' ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-wp" type="submit">Salva configurazione</button>
                <a class="btn btn-wp-bordo" href="<?= e(url('cliente')) ?>">Apri area cliente <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </form>

        <?php if ($haLogo): ?>
            <form method="post" action="<?= e(url('configuratore/rimuovi-logo')) ?>" class="mt-2">
                <?= Csrf::campo() ?>
                <button class="btn btn-wp-bordo btn-sm" type="submit">Rimuovi logo</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="col-xl-5">
        <div class="wp-scheda wp-anteprima" aria-label="Anteprima dell'area cliente">
            <div class="wp-scheda-testa align-items-center">
                <strong>Anteprima in tempo reale</strong>
                <span class="wp-pillola">Vista cliente</span>
            </div>

            <div class="wp-anteprima-sfondo">
                <div class="wp-finestra" data-anteprima-finestra style="--wp-primary: <?= e($impostazioni['colore']) ?>">
                    <div class="wp-finestra-barra">
                        <span aria-hidden="true">● ● ●</span>
                        <span>/cliente/</span>
                    </div>
                    <div class="wp-finestra-testa">
                        <span class="wp-marchio-sigla" data-anteprima-sigla<?= $haLogo ? ' hidden' : '' ?>><?= e(sigla($impostazioni['testata']) ?: 'WP') ?></span>
                        <img class="wp-marchio-logo" data-anteprima-immagine alt=""<?= $haLogo ? ' src="' . e(url('media/logo', ['v' => $logo['versione'] ?? 1])) . '"' : ' hidden' ?>>
                        <strong data-anteprima-testo="testata"><?= e($impostazioni['testata']) ?></strong>
                        <p class="wp-titolo">Benvenuto, <span data-anteprima-testo="cliente"><?= e($impostazioni['cliente']) ?></span></p>
                        <p data-anteprima-testo="benvenuto"><?= e($impostazioni['benvenuto']) ?></p>
                    </div>
                    <ul class="wp-finestra-moduli">
                        <?php foreach (Impostazioni::MODULI as $chiave => [$nome, , $icona]): ?>
                            <li data-anteprima-voce="<?= e($chiave) ?>"<?= empty($impostazioni['moduli'][$chiave]) ? ' hidden' : '' ?>>
                                <i class="bi <?= e($icona) ?>" aria-hidden="true"></i> <?= e($nome) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <p class="wp-sottotitolo mb-0 mt-3">Salva per applicare le modifiche all'area cliente.</p>
        </div>
    </div>
</div>
