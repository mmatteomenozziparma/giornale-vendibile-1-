<?php
/** Elenco degli eventi in agenda. Variabili: $eventi, $rimuovi (true = mostra il pulsante per toglierli). */
?>
<?php if (!$eventi): ?>
    <p class="wp-sottotitolo mb-0">Nessun evento in agenda.</p>
<?php else: ?>
    <ul class="wp-righe">
        <?php foreach ($eventi as $evento): ?>
            <?php [$giorno, $mese] = giorno_mese($evento['data']); ?>
            <li class="d-flex align-items-center gap-3">
                <span class="wp-data"><b><?= e($giorno) ?></b><small><?= e($mese) ?></small></span>
                <span class="flex-grow-1">
                    <strong class="d-block"><?= e($evento['titolo']) ?></strong>
                    <span class="wp-sottotitolo">
                        <?= e(data_it($evento['data'])) ?><?= $evento['ora'] !== '' ? ' · ' . e($evento['ora']) : '' ?><?= $evento['luogo'] !== '' ? ' · ' . e($evento['luogo']) : '' ?>
                    </span>
                </span>
                <?php if ($rimuovi): ?>
                    <form method="post" action="<?= e(url('giornalista/rimuovi-evento')) ?>">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="id" value="<?= e($evento['id']) ?>">
                        <button class="btn btn-wp-bordo btn-sm" type="submit" aria-label="Rimuovi <?= e($evento['titolo']) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
