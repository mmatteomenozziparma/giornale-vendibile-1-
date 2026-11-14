<?php
/** Accesso dimostrativo: scelta del ruolo e del profilo di esempio. */
[$titoloPagina, $titoloScheda, $descrizione, $etichettaProfilo] = $testi;
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · demo</span>
    <h1><?= e($titoloPagina) ?></h1>
    <p class="wp-intro">Scegli il profilo dimostrativo per entrare nel percorso dedicato.</p>
    <div class="wp-nota">Dati fittizi. Gli accessi per ruolo sono simulati: in questa demo non serve una password.</div>
</section>

<div class="wp-scheda wp-scheda-stretta">
    <h2><?= e($titoloScheda) ?></h2>
    <p class="wp-sottotitolo"><?= e($descrizione) ?></p>

    <form method="post" action="<?= e(url('accesso/entra')) ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="ruolo" value="<?= e($ruolo) ?>">

        <label class="form-label" for="profilo"><?= e($etichettaProfilo) ?></label>
        <select class="form-select mb-3" id="profilo" name="profilo" required>
            <?php foreach ($profili as $id => $etichetta): ?>
                <option value="<?= e($id) ?>"<?= selezionato($id, $utente['profilo'] ?? '') ?>><?= e($etichetta) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-wp" type="submit">Entra nella demo</button>
    </form>

    <p class="wp-altri-ruoli">
        <?php foreach (Auth::RUOLI as $chiave => $etichetta): ?>
            <?php if ($chiave !== $ruolo): ?>
                <a href="<?= e(url('accesso', ['ruolo' => $chiave])) ?>"><?= e($etichetta) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </p>
</div>
