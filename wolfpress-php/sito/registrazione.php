<?php
/**
 * Iscrizione dei lettori.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Area lettori</span>
    <h1>Registrati</h1>
    <p>Ti invieremo un'email per confermare l'indirizzo: l'account si attiva dal link che contiene.</p>
</header>

<div class="row">
    <div class="col-lg-8">
        <form class="wp-scheda" method="post" action="<?= e(url('account/registra')) ?>">
            <?= Csrf::campo() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nome">Nome</label>
                    <input class="form-control" id="nome" name="nome" maxlength="120" autocomplete="given-name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cognome">Cognome</label>
                    <input class="form-control" id="cognome" name="cognome" maxlength="120" autocomplete="family-name" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="citta">Città (facoltativa)</label>
                    <input class="form-control" id="citta" name="citta" maxlength="120">
                </div>
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
                        <label class="form-check-label" for="privacy">Ho letto e accetto l'<a href="<?= e(url('pagine/privacy')) ?>" target="_blank" rel="noopener">informativa sulla privacy</a>.</label>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
                <button class="btn btn-wp" type="submit">Registrati</button>
                <a href="<?= e(url('account')) ?>">Ho già un account</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
