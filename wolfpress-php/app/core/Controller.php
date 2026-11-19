<?php
/**
 * Controller di base: ogni controller dei due front end lo estende.
 * Il controller legge la richiesta, usa i modelli e prepara i dati per il file di pagina.
 */
declare(strict_types=1);

abstract class Controller
{
    /**
     * Indica la pagina da mostrare (nome del file senza .php, es. 'notizie')
     * e i dati che il suo HTML potrà usare come variabili.
     */
    protected function vista(string $pagina, array $dati = [], int $stato = 200): void
    {
        Pagina::mostra($pagina, $dati + $this->datiComuni(), $stato);
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
