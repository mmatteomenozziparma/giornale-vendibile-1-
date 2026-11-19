<?php
/**
 * Password dimenticata e scelta della nuova password.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Area lettori</span>
    <h1><?= $token === '' ? 'Password dimenticata' : 'Scegli la password' ?></h1>
    <p><?= $token === '' ? 'Scrivi la tua email: ti mandiamo un link valido un\'ora per scegliere una nuova password.' : 'La password deve avere almeno 10 caratteri.' ?></p>
</header>

<div class="row">
    <div class="col-lg-6">
        <?php if ($token === ''): ?>
            <form class="wp-scheda" method="post" action="<?= e(url('account/richiesta')) ?>">
                <?= Csrf::campo() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="email" required>
                </div>
                <button class="btn btn-wp" type="submit">Invia il link</button>
            </form>
        <?php else: ?>
            <form class="wp-scheda" method="post" action="<?= e(url('account/salva-password')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label" for="password">Nuova password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="10" maxlength="128" autocomplete="new-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password2">Ripeti la password</label>
                    <input class="form-control" type="password" id="password2" name="password2" minlength="10" maxlength="128" autocomplete="new-password" required>
                </div>
                <button class="btn btn-wp" type="submit">Salva la password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
