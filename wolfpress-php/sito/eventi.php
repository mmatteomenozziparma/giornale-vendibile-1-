<?php
/**
 * Prossimi eventi pubblici.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / Eventi
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello">Agenda pubblica</span>
    <h1>Eventi</h1>
    <p>Incontri organizzati da politici e testate della piattaforma, anche insieme.<?= e(demo(' Gli eventi dei dati dimostrativi sono fittizi.')) ?></p>
</header>

<?php if (!$eventi): ?>
    <section class="wp-scheda"><p class="mb-0">Non ci sono eventi pubblici in programma.</p></section>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($eventi as $evento): ?>
            <?php [$giorno, $mese] = giorno_mese($evento['data']); ?>
            <div class="col-md-6">
                <section class="wp-scheda h-100" id="evento-<?= e($evento['id']) ?>">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="wp-data"><b><?= e($giorno) ?></b><small><?= e($mese) ?></small></span>
                        <div>
                            <h2 class="mb-0"><?= e($evento['titolo']) ?></h2>
                            <span class="wp-sottotitolo"><?= e(data_it($evento['data'])) ?><?= $evento['ora'] !== '' ? ' · ore ' . e($evento['ora']) : '' ?><?= $evento['luogo'] !== '' ? ' · ' . e($evento['luogo']) : '' ?></span>
                        </div>
                    </div>
                    <?php if ($evento['descrizione'] !== ''): ?>
                        <?= paragrafi($evento['descrizione']) ?>
                    <?php endif; ?>
                    <p class="wp-sottotitolo">Organizzato da <strong><?= e($evento['organizzatori'] ?? '') ?></strong> · <?= (int) $evento['quanti'] ?> <?= (int) $evento['quanti'] === 1 ? 'partecipante' : 'partecipanti' ?></p>

                    <?php if (in_array($evento['id'], $miei, true)): ?>
                        <span class="wp-stato wp-stato-ok">Partecipi</span>
                    <?php elseif ($puoPartecipare): ?>
                        <form method="post" action="<?= e(url('eventi/partecipa')) ?>">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="id" value="<?= e($evento['id']) ?>">
                            <button class="btn btn-wp" type="submit">Partecipa</button>
                        </form>
                    <?php elseif (!$utente): ?>
                        <a class="btn btn-wp-bordo" href="<?= e(url('account')) ?>">Accedi per partecipare</a>
                    <?php endif; ?>
                </section>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
