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
        $testata = Testata::trova(Testata::PRINCIPALE) ?? [];
        $letture = Impostazioni::leggi()['letture'] ?? [];

        $this->vista('cliente', [
            'titolo' => 'Area cliente · WolfPress',
            'conteggio' => Articolo::contaPerStato(Testata::PRINCIPALE),
            'articoli' => Articolo::perTestata(Testata::PRINCIPALE),
            'letture' => $letture,
            'lettureTotali' => array_sum($letture),
            'social' => Testata::socialAttivi($testata),
            'testata' => $testata,
        ]);
    }
}
