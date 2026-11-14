<?php
/** Scheda di un giornalista: sola lettura ($modifica = false) oppure modulo di modifica. */
$nuovo = $giornalista['id'] === '';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Area editore · account giornalisti</span>
    <h1><?= $nuovo ? 'Nuovo account' : e($giornalista['nome']) ?></h1>
    <p class="wp-intro">
        <?php if ($nuovo): ?>
            Crea un account di redazione dimostrativo.
        <?php else: ?>
            <?= e($giornalista['ruolo']) ?> · <?= e($giornalista['sezione']) ?> · <?= bollino($giornalista['stato']) ?>
        <?php endif; ?>
    </p>
</section>

<?php if ($modifica): ?>
    <form class="wp-scheda wp-scheda-media" method="post" action="<?= e(url('editore/salva')) ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="id" value="<?= e($giornalista['id']) ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="nome">Nome e cognome</label>
                <input class="form-control" id="nome" name="nome" maxlength="80" required value="<?= e($giornalista['nome']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="120" value="<?= e($giornalista['email']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ruolo">Ruolo</label>
                <input class="form-control" id="ruolo" name="ruolo" maxlength="60" required value="<?= e($giornalista['ruolo']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sezione">Sezione</label>
                <input class="form-control" id="sezione" name="sezione" maxlength="40" value="<?= e($giornalista['sezione']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="stato">Stato</label>
                <select class="form-select" id="stato" name="stato">
                    <?php foreach (Giornalista::STATI as $stato): ?>
                        <option<?= selezionato($stato, $giornalista['stato']) ?>><?= e($stato) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="bio">Presentazione</label>
                <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="400"><?= e($giornalista['bio']) ?></textarea>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-wp" type="submit">Salva account</button>
            <a class="btn btn-wp-bordo" href="<?= e(url('editore')) ?>">Annulla</a>
        </div>
    </form>
<?php else: ?>
    <div class="row g-3 align-items-start">
        <div class="col-xl-5">
            <section class="wp-scheda">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="wp-avatar wp-avatar-grande" aria-hidden="true"><?= e(iniziali($giornalista['nome'])) ?></span>
                    <div>
                        <h2 class="mb-0"><?= e($giornalista['nome']) ?></h2>
                        <span class="wp-sottotitolo"><?= e($giornalista['email']) ?></span>
                    </div>
                </div>
                <p><?= e($giornalista['bio']) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-wp" href="<?= e(url('editore/modifica', ['id' => $giornalista['id']])) ?>">Modifica</a>
                    <a class="btn btn-wp-bordo" href="<?= e(url('editore')) ?>">Torna agli account</a>
                </div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="wp-scheda mb-3">
                <h2>Portfolio</h2>
                <p class="wp-sottotitolo">I tre articoli più letti, ordinati per visualizzazioni demo.</p>
                <?php View::parziale('portfolio', ['portfolio' => $portfolio]); ?>
            </section>
            <section class="wp-scheda">
                <h2>Eventi in agenda</h2>
                <p class="wp-sottotitolo">Appuntamenti assegnati a questo profilo.</p>
                <?php View::parziale('eventi', ['eventi' => $eventi, 'rimuovi' => false]); ?>
            </section>
        </div>
    </div>
<?php endif; ?>
