<?php
/**
 * Controller di base: ogni controller dei due front end lo estende.
 * Il controller legge la richiesta, usa i modelli e sceglie la vista.
 */
declare(strict_types=1);

abstract class Controller
{
    /** Cartella "views" del front end corrente. */
    protected function cartellaViste(): string
    {
        return dirname($_SERVER['SCRIPT_FILENAME']) . '/views';
    }

    /** Mostra una vista dentro il layout. */
    protected function vista(string $vista, array $dati = [], int $stato = 200): void
    {
        http_response_code($stato);
        View::mostra($this->cartellaViste(), $vista, $dati + $this->datiComuni());
    }

    /** Dati disponibili in tutte le viste (ridefinibile). */
    protected function datiComuni(): array
    {
        return [];
    }

    /** Rimanda a un'altra rotta dello stesso front end. */
    protected function vai(string $rotta = '', array $parametri = [], string $ancora = ''): void
    {
        header('Location: ' . url($rotta, $parametri, $ancora), true, 303);
        exit;
    }

    /** Legge un campo inviato con un form, ripulito e accorciato. */
    protected function campo(string $nome, int $massimo = 200): string
    {
        $valore = $_POST[$nome] ?? '';
        if (!is_string($valore)) {
            return '';
        }
        $valore = trim(str_replace("\0", '', $valore));
        return mb_substr($valore, 0, $massimo);
    }

    /** Legge un parametro dell'indirizzo (?id=...). */
    protected function parametro(string $nome, int $massimo = 120): string
    {
        $valore = $_GET[$nome] ?? '';
        if (!is_string($valore)) {
            return '';
        }
        return mb_substr(trim(str_replace("\0", '', $valore)), 0, $massimo);
    }

    /** Restituisce $valore solo se è tra quelli ammessi, altrimenti il predefinito. */
    protected function tra(string $valore, array $ammessi, string $predefinito): string
    {
        return in_array($valore, $ammessi, true) ? $valore : $predefinito;
    }

    protected function pagina404(string $messaggio = 'La pagina richiesta non esiste.'): void
    {
        $this->vista('errore', ['titolo' => 'Pagina non trovata', 'messaggio' => $messaggio], 404);
    }
}
