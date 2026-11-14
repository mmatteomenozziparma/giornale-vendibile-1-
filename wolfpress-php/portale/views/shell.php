<?php
/**
 * Struttura interna del portale: menu laterale, barra in alto e contenuto.
 * È la parte che la single page application sostituisce a ogni cambio di pagina.
 */
?>
<div class="wp-griglia" style="--wp-primary: <?= e($impostazioni['colore'] ?? '#286347') ?>">

    <aside class="wp-laterale offcanvas-lg offcanvas-start" id="wp-laterale" tabindex="-1" aria-label="Menu del portale">
        <?php require __DIR__ . '/menu.php'; ?>
    </aside>

    <div class="wp-corpo">
        <header class="wp-barra">
            <?php require __DIR__ . '/topbar.php'; ?>
        </header>

        <main id="wp-main" class="wp-main" tabindex="-1">
            <?php if (!empty($avviso)): ?>
                <div class="wp-avviso wp-avviso-<?= e($avviso['tipo']) ?>" role="status">
                    <i class="bi <?= $avviso['tipo'] === 'errore' ? 'bi-exclamation-triangle' : 'bi-check2-circle' ?>" aria-hidden="true"></i>
                    <span><?= e($avviso['testo']) ?></span>
                </div>
            <?php endif; ?>

            <?= $contenuto ?>

            <?php require __DIR__ . '/footer.php'; ?>
        </main>
    </div>

</div>
