<?php
/** Profilo giornalista: portfolio, eventi in agenda e chat di redazione. */
?>
<section class="wp-copertina wp-copertina-profilo">
    <span class="wp-occhiello"><?= e($impostazioni['testata']) ?> · profilo giornalista</span>
    <div class="d-flex align-items-center gap-3">
        <span class="wp-avatar wp-avatar-grande" aria-hidden="true"><?= e(iniziali($giornalista['nome'])) ?></span>
        <div>
            <h1><?= e($giornalista['nome']) ?></h1>
            <p class="mb-0"><?= e($giornalista['ruolo']) ?> · <?= e($giornalista['sezione']) ?> · Account <?= e(mb_strtolower($giornalista['stato'])) ?></p>
        </div>
    </div>
</section>

<div class="row g-3 align-items-start mb-3">
    <div class="col-xl-7">
        <section class="wp-scheda" id="portfolio">
            <div class="wp-scheda-testa">
                <h2>Portfolio</h2>
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            </div>
            <p class="wp-sottotitolo">I tre articoli più letti, ordinati per visualizzazioni demo.</p>
            <?php View::parziale('portfolio', ['portfolio' => $portfolio]); ?>
            <a class="btn btn-wp-bordo btn-sm mt-3" href="<?= e(url('articoli')) ?>">Tutti i miei articoli</a>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda" id="eventi">
            <div class="wp-scheda-testa">
                <h2>Eventi in agenda</h2>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
            </div>
            <p class="wp-sottotitolo">Appuntamenti assegnati a questo profilo. Calendario demo.</p>
            <?php View::parziale('eventi', ['eventi' => $eventi, 'rimuovi' => true]); ?>

            <details class="wp-dettagli mt-3">
                <summary>Aggiungi un evento</summary>
                <form method="post" action="<?= e(url('giornalista/evento')) ?>" class="row g-2 mt-1">
                    <?= Csrf::campo() ?>
                    <div class="col-12">
                        <label class="form-label" for="evento-titolo">Titolo</label>
                        <input class="form-control" id="evento-titolo" name="titolo" maxlength="80" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="evento-data">Data</label>
                        <input class="form-control" type="date" id="evento-data" name="data" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="evento-ora">Ora</label>
                        <input class="form-control" type="time" id="evento-ora" name="ora">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="evento-luogo">Luogo</label>
                        <input class="form-control" id="evento-luogo" name="luogo" maxlength="60" placeholder="Online, città, sala...">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-wp-bordo btn-sm" type="submit">Aggiungi all'agenda</button>
                    </div>
                </form>
            </details>
        </section>
    </div>
</div>

<?php View::parziale('chat_redazione', [
    'canali' => $canali,
    'canale' => $canale,
    'messaggi' => $messaggi,
    'video' => $video,
    'rotta' => 'giornalista',
    'io' => $giornalista['nome'],
    'configuraVideo' => false,
]); ?>
