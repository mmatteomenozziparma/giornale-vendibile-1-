<?php
/**
 * FOOTER del portale: piè di pagina, chiusura di <main> e della griglia, script.
 * Incluso alla fine di ogni pagina.
 */
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
?>
            <footer class="wp-piede">
                <span><?= e(config('nome')) ?> · portale</span>
                <span>Area riservata: accesso con account personale.</span>
            </footer>
        </main>
    </div>

</div>
<?php if (View::richiestaSpa()) { return; }   // il JavaScript sostituisce solo l'interno di #wp-shell ?>
    </div>

    <div id="wp-annuncio" class="visually-hidden" aria-live="polite"></div>

    <script src="<?= e(asset_comune('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>" defer></script>
    <script src="<?= e(asset('js/portale.js')) ?>" defer></script>
</body>
</html>
