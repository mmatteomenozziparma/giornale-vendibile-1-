<?php
/**
 * Area cliente: la dashboard della redazione (scrivania, letture, social, assistenza).
 */
declare(strict_types=1);

final class ClienteController extends PortaleController
{
    protected string $area = 'cliente';
    protected string $voce = 'panoramica';

    public function index(): void
    {
        $testata = Testata::trova(Auth::testata()) ?? [];
        $letture = Letture::settimana(Testata::dbId(Auth::testata()));

        $this->vista('cliente', [
            'titolo' => 'Area cliente · WolfPress',
            'conteggio' => Articolo::contaPerStato(Auth::testata()),
            'articoli' => Articolo::perTestata(Auth::testata()),
            'letture' => $letture,
            'lettureTotali' => array_sum($letture),
            'social' => Testata::socialAttivi($testata),
            'testata' => $testata,
        ]);
    }
}
