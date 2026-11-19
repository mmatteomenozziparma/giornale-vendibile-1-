<?php
/**
 * FOOTER del sito pubblico: chiude <main>, mostra il piè di pagina e carica gli script.
 * Incluso alla fine di ogni pagina.
 */
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
?>
    </main>

<footer class="wp-fondo">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <strong class="wp-fondo-nome"><?= e(config('nome')) ?></strong>
                <p>Piattaforma per giornali digitali indipendenti: notizie delle testate ospitate e schede di partiti, candidati ed eletti.</p>
            </div>
            <div class="col-6 col-md-3">
                <h2>Esplora</h2>
                <ul>
                    <li><a href="<?= e(url('notizie')) ?>">Notizie</a></li>
                    <li><a href="<?= e(url('testate')) ?>">Testate</a></li>
                    <li><a href="<?= e(url('politica')) ?>">Politica</a></li>
                    <li><a href="<?= e(url('ricerca')) ?>">Cerca</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h2>Informazioni</h2>
                <ul>
                    <li><a href="<?= e(url('pagine/chi-siamo')) ?>">Chi siamo</a></li>
                    <li><a href="<?= e(url('pagine/privacy')) ?>">Privacy</a></li>
                    <li><a href="<?= e(url('pagine/accessibilita')) ?>">Accessibilità</a></li>
                    <li><a href="<?= e(url_portale()) ?>">Area riservata a redazioni, politici e tecnici</a></li>
                </ul>
            </div>
        </div>
        <p class="wp-fondo-nota">
            <?= e(config('nome')) ?><?= e(demo(' · demo editoriale. Testate, partito, persone, luoghi, date e atti sono fittizi. Non è una testata registrata.', ' · piattaforma per giornali digitali.')) ?>
        </p>
    </div>
</footer>

    <script src="<?= e(asset_comune('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>" defer></script>
    <script src="<?= e(asset('js/sito.js')) ?>" defer></script>
</body>
</html>
