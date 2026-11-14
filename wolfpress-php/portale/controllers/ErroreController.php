<?php
declare(strict_types=1);

final class ErroreController extends PortaleController
{
    public function nonTrovata(): void
    {
        $this->vista('errore', [
            'titolo' => 'Pagina non trovata',
            'messaggio' => 'La pagina richiesta non esiste oppure è stata spostata.',
        ], 404);
    }

    public function vietato(): void
    {
        $this->vista('errore', [
            'titolo' => 'Area non disponibile',
            'messaggio' => 'Il profilo «' . Auth::etichettaRuolo() . '» non può aprire questa area. Cambia profilo demo per entrare.',
        ], 403);
    }

    public function interno(string $dettaglio = ''): void
    {
        $this->vista('errore', [
            'titolo' => 'Si è verificato un errore',
            'messaggio' => $dettaglio !== '' ? $dettaglio : 'Riprova tra poco. Se il problema continua, apri un ticket di assistenza.',
        ], 500);
    }
}
