<?php
/**
 * Strumenti della demo: riporta i dati allo stato iniziale.
 */
declare(strict_types=1);

final class DemoController extends PortaleController
{
    public function ripristina(): void
    {
        $this->soloPost();
        Store::ripristina();
        @unlink(WP_STORAGE . '/logo.bin');
        $this->avviso('Dati demo ripristinati: tutte le modifiche sono state annullate.');
        $this->vai('home');
    }
}
