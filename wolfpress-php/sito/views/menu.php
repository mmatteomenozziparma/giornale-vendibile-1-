<?php
/**
 * Testata del sito con il menu principale e la ricerca.
 * Per aggiungere una voce al menu basta inserire una riga in $voci.
 *
 * Ogni voce: [chiave, etichetta, indirizzo]
 */
$voci = [
    ['home', 'Home', url()],
    ['notizie', 'Notizie', url('notizie')],
    ['testate', 'Testate', url('testate')],
    ['politica', 'Politica', url('politica')],
    ['chi-siamo', 'Chi siamo', url('pagine/chi-siamo')],
];
?>
<header class="wp-testata">
    <nav class="navbar navbar-expand-lg container" aria-label="Navigazione principale">
        <a class="wp-logo" href="<?= e(url()) ?>" aria-label="<?= e(config('nome')) ?>, pagina iniziale">
            <span class="wp-logo-segno" aria-hidden="true">W</span>
            <span>
                <strong><?= e(config('nome')) ?></strong>
                <small><?= e(config('motto')) ?></small>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-principale"
                aria-controls="menu-principale" aria-expanded="false" aria-label="Apri il menu">
            <i class="bi bi-list" aria-hidden="true"></i> Menu
        </button>

        <div class="collapse navbar-collapse" id="menu-principale">
            <ul class="navbar-nav wp-voci mx-lg-auto">
                <?php foreach ($voci as [$chiave, $etichetta, $indirizzo]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $chiave === $voce ? ' attiva' : '' ?>" href="<?= e($indirizzo) ?>"<?= $chiave === $voce ? ' aria-current="page"' : '' ?>><?= e($etichetta) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form class="wp-cerca" role="search" method="get" action="<?= e(url()) ?>">
                <input type="hidden" name="r" value="ricerca">
                <label class="visually-hidden" for="cerca-menu">Cerca notizie, politici o partiti</label>
                <input class="form-control" type="search" id="cerca-menu" name="q" maxlength="80" placeholder="Cerca notizie, politici, partiti" value="<?= e($ricercaCorrente) ?>">
                <button class="btn btn-wp" type="submit" aria-label="Cerca"><i class="bi bi-search" aria-hidden="true"></i></button>
            </form>

            <span class="wp-demo">Demo</span>
        </div>
    </nav>

    <?php if ($sezioniMenu): ?>
        <div class="wp-sezioni">
            <div class="container">
                <span>Sezioni</span>
                <?php foreach ($sezioniMenu as $nomeSezione): ?>
                    <a href="<?= e(url('notizie', ['sezione' => $nomeSezione])) ?>"><?= e($nomeSezione) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</header>
