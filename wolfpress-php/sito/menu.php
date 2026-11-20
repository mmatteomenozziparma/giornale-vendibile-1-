<?php
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
/**
 * MENU del sito pubblico. Incluso in ogni pagina dopo header.php; apre anche <main>, che footer.php chiude.
 *
 * Il menu è CONTESTUALE ($contesto, impostato dal controller della pagina):
 *
 *   wolfpress  la piattaforma: home, notizie di tutte le testate, ricerca, pagine informative
 *   giornale   una testata ($testataMenu) con il suo nome e le sue sezioni, oppure l'elenco dei giornali
 *   atlante    Atlante Civico: partiti, candidati ed eletti, eventi
 *
 * In alto c'è sempre la barra dei tre mondi, per passare dall'uno all'altro.
 * Per aggiungere una voce basta una riga nell'elenco del contesto giusto: [chiave, etichetta, indirizzo]
 */
$mondi = [
    'wolfpress' => ['WolfPress', url()],
    'giornale' => ['Giornali', url('testate')],
    'atlante' => ['Atlante Civico', url('politica')],
];

if ($contesto === 'atlante') {
    $marchio = ['A', 'Atlante Civico', 'Partiti, candidati ed eletti', url('politica')];
    $voci = [
        ['politica', 'Panoramica', url('politica')],
        ['partiti', 'Partiti', url('politica', [], 'titolo-partiti')],
        ['persone', 'Candidati ed eletti', url('politica', [], 'titolo-persone')],
        ['eventi', 'Eventi', url('eventi')],
    ];
    $cerca = ['politici', 'Cerca politici'];
    $striscia = ['Partiti', array_map(static fn ($slug, $nome) => [$nome, url('politica/partito', ['id' => $slug])], array_keys($partitiMenu), $partitiMenu)];
} elseif ($contesto === 'giornale' && $testataMenu !== null) {
    $marchio = [mb_strtoupper(mb_substr($testataMenu['nome'], 0, 1)), $testataMenu['nome'], $testataMenu['dominio'] !== '' ? $testataMenu['dominio'] : 'Testata su ' . config('nome'), url('notizie', ['testata' => $testataMenu['id']])];
    $voci = [
        ['notizie', 'Ultime notizie', url('notizie', ['testata' => $testataMenu['id']])],
        ['sostieni', 'Segui o sostieni', url('sostieni', ['testata' => $testataMenu['id']])],
        ['testate', 'Tutte le testate', url('testate')],
    ];
    $cerca = ['notizie', 'Cerca nelle notizie'];
    $striscia = ['Sezioni', array_map(static fn ($nome) => [$nome, url('notizie', ['testata' => $testataMenu['id'], 'sezione' => $nome])], $sezioniMenu)];
} elseif ($contesto === 'giornale') {
    $marchio = ['G', 'Giornali', 'Le testate della piattaforma', url('testate')];
    $voci = [
        ['testate', 'Le testate', url('testate')],
        ['notizie', 'Tutte le notizie', url('notizie')],
    ];
    $cerca = ['notizie', 'Cerca nelle notizie'];
    $striscia = ['Sezioni', array_map(static fn ($nome) => [$nome, url('notizie', ['sezione' => $nome])], $sezioniMenu)];
} else {
    $marchio = ['W', config('nome'), config('motto'), url()];
    $voci = [
        ['home', 'Home', url()],
        ['notizie', 'Notizie', url('notizie')],
        ['testate', 'Giornali', url('testate')],
        ['politica', 'Atlante Civico', url('politica')],
        ['eventi', 'Eventi', url('eventi')],
        ['chi-siamo', 'Chi siamo', url('pagine/chi-siamo')],
    ];
    $cerca = ['tutto', 'Cerca notizie, politici, partiti'];
    $striscia = ['Sezioni', array_map(static fn ($nome) => [$nome, url('notizie', ['sezione' => $nome])], $sezioniMenu)];
}
?>
<div class="wp-mondi">
    <nav class="container" aria-label="Aree del sito">
        <?php foreach ($mondi as $chiave => [$etichetta, $indirizzo]): ?>
            <a class="<?= $chiave === $contesto ? 'attivo' : '' ?>" href="<?= e($indirizzo) ?>"<?= $chiave === $contesto ? ' aria-current="true"' : '' ?>><?= e($etichetta) ?></a>
        <?php endforeach; ?>
        <a class="wp-mondi-account<?= $voce === 'account' ? ' attivo' : '' ?>" href="<?= e(url($utente ? 'account' : 'account/accedi')) ?>">
            <i class="bi bi-person" aria-hidden="true"></i> <?= $utente ? e($utente['nome']) : 'Accedi' ?>
        </a>
    </nav>
</div>

<header class="wp-testata wp-contesto-<?= e($contesto) ?>">
    <nav class="navbar navbar-expand-lg container" aria-label="Menu di <?= e($marchio[1]) ?>">
        <a class="wp-logo" href="<?= e($marchio[3]) ?>" aria-label="<?= e($marchio[1]) ?>, pagina iniziale">
            <span class="wp-logo-segno" aria-hidden="true"><?= e($marchio[0]) ?></span>
            <span>
                <strong><?= e($marchio[1]) ?></strong>
                <small><?= e($marchio[2]) ?></small>
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

            <form class="wp-cerca" role="search" method="get" action="<?= e(url('ricerca')) ?>">
                <?php if ($cerca[0] !== 'tutto'): ?><input type="hidden" name="dove" value="<?= e($cerca[0]) ?>"><?php endif; ?>
                <label class="visually-hidden" for="cerca-menu"><?= e($cerca[1]) ?></label>
                <input class="form-control" type="search" id="cerca-menu" name="q" maxlength="80" placeholder="<?= e($cerca[1]) ?>" value="<?= e($ricercaCorrente) ?>">
                <button class="btn btn-wp" type="submit" aria-label="Cerca"><i class="bi bi-search" aria-hidden="true"></i></button>
            </form>
        </div>
    </nav>

    <?php if ($striscia[1]): ?>
        <div class="wp-sezioni">
            <div class="container">
                <span><?= e($striscia[0]) ?></span>
                <?php foreach ($striscia[1] as [$etichetta, $indirizzo]): ?>
                    <a href="<?= e($indirizzo) ?>"><?= e($etichetta) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</header>

    <main id="contenuto" class="container wp-contenuto" tabindex="-1">
        <?php if (!empty($avviso)): ?>
            <div class="alert <?= $avviso['tipo'] === 'errore' ? 'alert-danger' : 'alert-success' ?> mt-3" role="status"><?= e($avviso['testo']) ?></div>
        <?php endif; ?>
