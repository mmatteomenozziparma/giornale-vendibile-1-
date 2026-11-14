<?php
/**
 * Home del sito: notizia in primo piano, ultime notizie, più letti, politica e testate.
 */
declare(strict_types=1);

final class HomeController extends SitoController
{
    protected string $voce = 'home';

    public function index(): void
    {
        $pubblicati = Articolo::pubblicati();

        $this->vista('home', [
            'titolo' => 'WolfPress · Notizie, persone, territori',
            'primoPiano' => $pubblicati[0] ?? null,
            'ultime' => array_slice($pubblicati, 1, 6),
            'piuLetti' => Articolo::piuLetti(3),
            'politici' => Politico::tutti(),
            'partiti' => Partito::tutti(),
            'testate' => Testata::tutti(),
        ]);
    }
}
