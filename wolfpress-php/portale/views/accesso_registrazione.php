<?php
/** Registrazione al portale: editore, giornalista o politico. */
$tipi = ['editore' => 'Editore', 'giornalista' => 'Giornalista', 'politico' => 'Politico'];
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · portale</span>
    <h1>Crea un account</h1>
    <p class="wp-intro">Dopo la registrazione riceverai un'email per confermare l'indirizzo. Il profilo viene poi approvato dal nostro staff prima del primo accesso.</p>
</section>

<div class="wp-scheda wp-scheda-media">
    <nav class="wp-schede mb-3" aria-label="Tipo di account">
        <?php foreach ($tipi as $chiave => $etichetta): ?>
            <a class="btn btn-wp-bordo<?= $chiave === $tipo ? ' attiva' : '' ?>" href="<?= e(url('accesso/registrazione', ['tipo' => $chiave])) ?>"<?= $chiave === $tipo ? ' aria-current="true"' : '' ?>><?= e($etichetta) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tipo === 'giornalista' && !$testate): ?>
        <p class="wp-nota">Non ci sono ancora testate a cui collegarsi: chiedi al tuo editore di registrarsi per primo.</p>
    <?php else: ?>
    <form method="post" action="<?= e(url('accesso/registra')) ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="nome">Nome</label>
                <input class="form-control" id="nome" name="nome" maxlength="120" autocomplete="given-name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cognome">Cognome</label>
                <input class="form-control" id="cognome" name="cognome" maxlength="120" autocomplete="family-name" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" required>
            </div>

            <?php if ($tipo === 'editore'): ?>
                <div class="col-md-8">
                    <label class="form-label" for="ragione_sociale">Ragione sociale</label>
                    <input class="form-control" id="ragione_sociale" name="ragione_sociale" maxlength="200" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="partita_iva">Partita IVA (facoltativa)</label>
                    <input class="form-control" id="partita_iva" name="partita_iva" maxlength="20">
                </div>
            <?php elseif ($tipo === 'giornalista'): ?>
                <div class="col-md-7">
                    <label class="form-label" for="giornale_id">Testata</label>
                    <select class="form-select" id="giornale_id" name="giornale_id" required>
                        <?php foreach ($testate as $testata): ?>
                            <option value="<?= e($testata['db_id']) ?>"><?= e($testata['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="sezione">Sezione (facoltativa)</label>
                    <input class="form-control" id="sezione" name="sezione" maxlength="120">
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label" for="livello">Livello istituzionale</label>
                    <select class="form-select" id="livello" name="livello">
                        <?php foreach (Politico::LIVELLI as $chiave => $etichetta): ?>
                            <option value="<?= e($chiave) ?>"><?= e($etichetta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="partito">Partito o lista (facoltativo)</label>
                    <input class="form-control" id="partito" name="partito" maxlength="160">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="carica">Ruolo o candidatura</label>
                    <input class="form-control" id="carica" name="carica" maxlength="160" placeholder="Candidata al Consiglio comunale">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="territorio">Territorio</label>
                    <input class="form-control" id="territorio" name="territorio" maxlength="160">
                </div>
            <?php endif; ?>

            <div class="col-md-6">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" minlength="10" maxlength="128" autocomplete="new-password" required>
                <div class="form-text">Almeno 10 caratteri.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password2">Ripeti la password</label>
                <input class="form-control" type="password" id="password2" name="password2" minlength="10" maxlength="128" autocomplete="new-password" required>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="privacy" name="privacy" value="1" required>
                    <label class="form-check-label" for="privacy">Ho letto e accetto l'<a href="<?= e(url_sito('pagine/privacy')) ?>" target="_blank" rel="noopener">informativa sulla privacy</a>.</label>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-wp" type="submit">Registrati</button>
            <a class="btn btn-wp-bordo" href="<?= e(url('accesso')) ?>">Ho già un account</a>
        </div>
    </form>
    <?php endif; ?>
</div>
