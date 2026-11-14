<?php
declare(strict_types=1);

final class ErroreController extends SitoController
{
    public function nonTrovata(): void
    {
        $this->pagina404();
    }

    public function interno(string $dettaglio = ''): void
    {
        $this->vista('errore', [
            'titolo' => 'Si è verificato un errore',
            'messaggio' => $dettaglio !== '' ? $dettaglio : 'Riprova tra poco.',
        ], 500);
    }
}
