<?php
/**
 * Testate: i giornali ospitati dalla piattaforma, con il numero di articoli pubblicati.
 */
declare(strict_types=1);

final class TestateController extends SitoController
{
    protected string $voce = 'testate';

    public function index(): void
    {
        $testate = [];
        foreach (Testata::tutti() as $testata) {
            $articoli = Articolo::filtra('', $testata['id']);
            $testate[] = $testata + [
                'quanti' => count($articoli),
                'ultimo' => $articoli[0] ?? null,
                'social_attivi' => Testata::socialAttivi($testata),
            ];
        }

        $this->vista('testate', [
            'titolo' => 'Le testate · WolfPress',
            'testate' => $testate,
        ]);
    }
}
