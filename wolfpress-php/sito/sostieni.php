<?php
/**
 * Sostieni una testata o un politico: segui, dona, abbonati.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<?php
/** Sostieni una testata o un politico: segui, dona, abbonati. */
$campo = '<input type="hidden" name="' . ($a['tipo'] === 'testata' ? 'testata' : 'politico') . '" value="' . e($a['slug']) . '">';
$euro = static fn ($centesimi): string => '€ ' . number_format((int) $centesimi / 100, 2, ',', '.');
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / Sostieni
</nav>

<header class="wp-intestazione-pagina">
    <span class="wp-occhiello"><?= $a['tipo'] === 'testata' ? 'Testata' : 'Politico' ?></span>
    <h1>Sostieni <?= e($a['nome']) ?></h1>
    <p>Il pagamento avviene sulla pagina sicura di Stripe: questo sito non riceve né conserva i dati della tua carta.</p>
</header>

<div class="row g-3">
    <div class="col-lg-6">
        <form class="wp-scheda h-100" method="post" action="<?= e(url('sostieni/dona')) ?>">
            <?= Csrf::campo() ?>
            <?= $campo ?>
            <h2>Fai una donazione</h2>
            <div class="mb-3">
                <label class="form-label" for="importo">Importo in euro</label>
                <input class="form-control" id="importo" name="importo" inputmode="decimal" maxlength="12" pattern="\d{1,6}([.,]\d{1,2})?" placeholder="10" required>
                <div class="form-text">Minimo <?= e(number_format($minimo, 2, ',', '.')) ?> €.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="messaggio">Messaggio (facoltativo)</label>
                <input class="form-control" id="messaggio" name="messaggio" maxlength="500">
            </div>
            <button class="btn btn-wp" type="submit">Dona con Stripe <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></button>
        </form>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda mb-3">
            <h2>Segui gratis</h2>
            <?php if (!$lettore): ?>
                <p class="mb-0"><a href="<?= e(url('account/accedi')) ?>">Accedi come lettore</a> per seguire <?= e($a['nome']) ?>.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('sostieni/segui')) ?>">
                    <?= Csrf::campo() ?>
                    <?= $campo ?>
                    <?php if ($iscritto): ?>
                        <input type="hidden" name="smetti" value="1">
                        <p>Stai seguendo <?= e($a['nome']) ?>.</p>
                        <button class="btn btn-wp-bordo" type="submit">Smetti di seguire</button>
                    <?php else: ?>
                        <button class="btn btn-wp" type="submit">Segui <?= e($a['nome']) ?></button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($a['mensile'] !== null || $a['annuale'] !== null): ?>
            <section class="wp-scheda">
                <h2>Abbonati</h2>
                <?php if ($abbonato): ?>
                    <p class="mb-0">Hai già un abbonamento attivo.</p>
                <?php elseif (!$lettore): ?>
                    <p class="mb-0"><a href="<?= e(url('account/accedi')) ?>">Accedi come lettore</a> per abbonarti.</p>
                <?php else: ?>
                    <form class="d-flex flex-wrap gap-2" method="post" action="<?= e(url('sostieni/abbonati')) ?>">
                        <?= Csrf::campo() ?>
                        <?= $campo ?>
                        <?php if ($a['mensile'] !== null): ?>
                            <button class="btn btn-wp" type="submit" name="piano" value="mensile"><?= e($euro($a['mensile'])) ?> al mese</button>
                        <?php endif; ?>
                        <?php if ($a['annuale'] !== null): ?>
                            <button class="btn btn-wp" type="submit" name="piano" value="annuale"><?= e($euro($a['annuale'])) ?> all'anno</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
