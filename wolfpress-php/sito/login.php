<?php
/**
 * Accesso dei lettori (login).
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Area lettori</span>
    <h1>Accedi</h1>
    <p>Con un account puoi seguire testate e politici, abbonarti e ritrovare le tue donazioni.</p>
</header>

<div class="row">
    <div class="col-lg-6">
        <form class="wp-scheda" method="post" action="<?= e(url('account/entra')) ?>">
            <?= Csrf::campo() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" maxlength="128" autocomplete="current-password" required>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <button class="btn btn-wp" type="submit">Entra</button>
                <a href="<?= e(url('account/password')) ?>">Password dimenticata</a>
            </div>
        </form>
    </div>
    <div class="col-lg-6 mt-3 mt-lg-0">
        <section class="wp-scheda h-100">
            <h2>Non hai un account?</h2>
            <p>La registrazione è gratuita e richiede solo nome, email e password.</p>
            <a class="btn btn-wp" href="<?= e(url('account/registrati')) ?>">Registrati</a>
            <p class="mt-3 mb-0">Sei un editore, un giornalista o un politico? <a href="<?= e(url_portale('accesso')) ?>">Vai al portale</a>.</p>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
