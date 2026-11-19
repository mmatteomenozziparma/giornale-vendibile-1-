<?php
/**
 * Accesso al portale (login).
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · portale</span>
    <h1>Accedi</h1>
    <p class="wp-intro">Area riservata a editori, giornalisti, politici e tecnici.</p>
</section>

<div class="wp-scheda wp-scheda-stretta">
    <form method="post" action="<?= e(url('accesso/entra')) ?>">
        <?= Csrf::campo() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" maxlength="254" autocomplete="username" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" maxlength="128" autocomplete="current-password" required>
        </div>
        <button class="btn btn-wp" type="submit">Entra</button>
    </form>

    <p class="wp-altri-ruoli">
        <a href="<?= e(url('accesso/password')) ?>">Password dimenticata</a>
        <a href="<?= e(url('accesso/registrazione')) ?>">Crea un account</a>
        <a href="<?= e(url_sito('account')) ?>" data-spa="off">Sei un lettore? Vai al sito</a>
    </p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
