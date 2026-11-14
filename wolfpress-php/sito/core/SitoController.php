<?php
/**
 * Base dei controller del sito pubblico: prepara i dati del menu.
 */
declare(strict_types=1);

abstract class SitoController extends Controller
{
    /** Voce del menu principale da evidenziare (vedi views/menu.php). */
    protected string $voce = '';

    protected function datiComuni(): array
    {
        return [
            'voce' => $this->voce,
            'sezioniMenu' => Articolo::sezioni(),
            'ricercaCorrente' => '',
            'descrizione' => 'WolfPress: notizie, politici e partiti in un unico sito dimostrativo.',
        ];
    }
}
