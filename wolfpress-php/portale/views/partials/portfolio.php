<?php
/** Elenco numerato degli articoli più letti. Variabile: $portfolio. */
?>
<?php if (!$portfolio): ?>
    <p class="wp-sottotitolo mb-0">Nessun articolo pubblicato per ora.</p>
<?php else: ?>
    <ol class="wp-righe wp-classifica">
        <?php foreach ($portfolio as $posizione => $articolo): ?>
            <li>
                <a href="<?= e(url_sito('notizie/articolo', ['slug' => $articolo['slug']])) ?>" target="_blank" rel="noopener">
                    <span class="wp-posizione"><?= $posizione + 1 ?></span>
                    <span class="flex-grow-1">
                        <strong><?= e($articolo['titolo']) ?></strong>
                        <small><?= e(numero((int) $articolo['letture'])) ?> visualizzazioni</small>
                    </span>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
