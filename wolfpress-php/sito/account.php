<?php
/**
 * Account del lettore: iscrizioni, abbonamenti, donazioni, eventi.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<?php
/** Riepilogo dell'account collegato: iscrizioni, abbonamenti e donazioni del lettore. */
$euro = static fn ($centesimi): string => '€ ' . number_format((int) $centesimi / 100, 2, ',', '.');
?>
<header class="wp-intestazione-pagina">
    <span class="wp-occhiello"><?= e(Auth::etichettaRuolo()) ?></span>
    <h1><?= e($utente['nome']) ?></h1>
    <p><?= e($utente['email']) ?></p>
</header>

<?php if (!$lettore): ?>
    <section class="wp-scheda">
        <p>Sei collegato con un account del portale.</p>
        <a class="btn btn-wp" href="<?= e(url_portale('home')) ?>">Vai al portale</a>
    </section>
<?php else: ?>
    <div class="row g-3">
        <div class="col-lg-4">
            <section class="wp-scheda h-100">
                <h2>Segui</h2>
                <?php if (!$iscrizioni): ?>
                    <p class="mb-0">Non segui ancora nessuno. Apri una <a href="<?= e(url('testate')) ?>">testata</a> o la scheda di un <a href="<?= e(url('politica')) ?>">politico</a>.</p>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($iscrizioni as $i): ?>
                            <li><a href="<?= e(url('sostieni', $i['testata'] !== null ? ['testata' => $i['testata']] : ['politico' => $i['politico']])) ?>"><?= e($i['nome']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="wp-scheda h-100">
                <h2>Abbonamenti</h2>
                <?php if (!$abbonamenti): ?>
                    <p class="mb-0">Nessun abbonamento.</p>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($abbonamenti as $a): ?>
                            <li><strong><?= e($a['nome']) ?></strong> · <?= e($a['piano']) ?>, <?= e($euro($a['importo_centesimi'])) ?> · <?= e($a['stato']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="wp-scheda h-100">
                <h2>Donazioni</h2>
                <?php if (!$donazioni): ?>
                    <p class="mb-0">Nessuna donazione confermata.</p>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($donazioni as $d): ?>
                            <li><?= e($euro($d['importo_centesimi'])) ?> a <strong><?= e($d['nome']) ?></strong> · <?= e(data_it(ora_locale($d['pagata_il'], 'Y-m-d'))) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <section class="wp-scheda mt-3">
        <h2>Eventi a cui partecipi</h2>
        <?php if (!$eventi): ?>
            <p class="mb-0">Nessuno. Guarda i <a href="<?= e(url('eventi')) ?>">prossimi eventi</a>.</p>
        <?php else: ?>
            <ul class="mb-0">
                <?php foreach ($eventi as $ev): ?>
                    <li><a href="<?= e(url('eventi', [], 'evento-' . $ev['id'])) ?>"><?= e($ev['titolo']) ?></a> · <?= e(data_it($ev['data'])) ?><?= $ev['luogo'] !== '' ? ' · ' . e($ev['luogo']) : '' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<form class="mt-3" method="post" action="<?= e(url('account/esci')) ?>">
    <?= Csrf::campo() ?>
    <button class="btn btn-wp-bordo" type="submit">Esci</button>
</form>
<?php require __DIR__ . '/footer.php'; ?>
