<?php
/**
 * Ricerca unica: notizie, politici e partiti.
 */
declare(strict_types=1);

final class RicercaController extends SitoController
{
    protected string $voce = 'ricerca';

    private const AMBITI = ['tutto' => 'Tutto', 'notizie' => 'Notizie', 'politici' => 'Politici', 'partiti' => 'Partiti'];

    public function index(): void
    {
        $ricerca = $this->parametro('q', 80);
        $dove = $this->tra($this->parametro('dove'), array_keys(self::AMBITI), 'tutto');

        $notizie = $politici = $partiti = [];
        if (mb_strlen($ricerca) >= 2) {
            if ($dove === 'tutto' || $dove === 'notizie') {
                $notizie = Articolo::filtra('', '', $ricerca);
            }
            if ($dove === 'tutto' || $dove === 'politici') {
                $politici = Politico::cerca($ricerca);
            }
            if ($dove === 'tutto' || $dove === 'partiti') {
                $partiti = Partito::cerca($ricerca);
            }
        }

        $this->vista('ricerca', [
            'titolo' => ($ricerca !== '' ? 'Risultati per «' . $ricerca . '»' : 'Cerca') . ' · WolfPress',
            'ricerca' => $ricerca,
            'ricercaCorrente' => $ricerca,
            'dove' => $dove,
            'ambiti' => self::AMBITI,
            'notizie' => $notizie,
            'politici' => $politici,
            'partiti' => $partiti,
            'totale' => count($notizie) + count($politici) + count($partiti),
        ]);
    }
}
