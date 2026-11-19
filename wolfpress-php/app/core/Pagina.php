<?php
/**
 * Pagina: collega ogni file di pagina (es. sito/notizie.php) al suo controller.
 *
 * Ogni pagina del sito e del portale è un file .php apribile dal browser, fatto così:
 *
 *     require_once __DIR__ . '/avvio.php';      // avvio comune del front end
 *     extract(pagina(__FILE__));                // prepara i dati (controller)
 *     require __DIR__ . '/header.php';          // <head> e apertura della pagina
 *     require __DIR__ . '/menu.php';            // menu
 *     ... HTML + PHP della pagina ...
 *     require __DIR__ . '/footer.php';          // piè di pagina e chiusura
 *
 * L'elenco delle pagine e del controller di ciascuna è in "pagine.php" del front end.
 * Le azioni dei form si chiamano con ?azione=nome (es. login.php?azione=entra).
 */
declare(strict_types=1);

final class Pagina
{
    /** Controllo dei permessi eseguito prima di ogni azione: function (string $controller): bool. */
    public static $controllo = null;

    private static array $tabelle = [];
    private static ?string $ingresso = null;   // pagina aperta dal browser (nome del file senza .php)
    private static ?array $dati = null;        // dati preparati per la pagina aperta
    private static ?array $pronti = null;      // dati già pronti per la pagina che si sta includendo

    /** Elenco delle pagine di un front end: [nome del file => [controller, azione]]. */
    public static function tabella(string $fronte): array
    {
        return self::$tabelle[$fronte] ??= require WP_ROOT . '/' . $fronte . '/pagine.php';
    }

    /** Nome del front end in uso: "sito" oppure "portale". */
    public static function fronte(): string
    {
        return basename(dirname($_SERVER['SCRIPT_FILENAME']));
    }

    /** "salva-stato" diventa "salvaStato". */
    public static function metodo(string $azione): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $azione))));
    }

    /**
     * File e azione di una rotta "controller/azione":
     * ['articolo.php', null] se la rotta ha una pagina sua, altrimenti
     * ['login.php', 'entra'] cioè la pagina principale del controller più ?azione=.
     */
    public static function indirizzo(string $fronte, string $rotta): array
    {
        if ($rotta === '') {
            return ['index.php', null];
        }
        $parti = explode('/', $rotta);
        $azione = $parti[1] ?? 'index';
        foreach (self::tabella($fronte) as $file => [$controller, $metodo]) {
            if ($controller === $parti[0] && $metodo === self::metodo($azione)) {
                return [$file . '.php', null];
            }
        }
        foreach (self::tabella($fronte) as $file => [$controller]) {
            if ($controller === $parti[0]) {
                return [$file . '.php', $azione];
            }
        }
        return ['index.php', null];
    }

    /**
     * Chiamata in cima a ogni file di pagina: esegue il controller e
     * restituisce i dati da usare nell'HTML (da passare a extract()).
     */
    public static function avvia(string $file): array
    {
        // La pagina viene inclusa da un'altra azione, che ha già preparato i dati.
        if (self::$pronti !== null) {
            $dati = self::$pronti;
            self::$pronti = null;
            return $dati;
        }

        self::$ingresso = basename($file, '.php');
        try {
            [$controller, $metodo] = self::tabella(self::fronte())[self::$ingresso] ?? ['errore', 'nonTrovata'];
            $azione = $_GET['azione'] ?? null;
            if ($azione !== null) {
                // Solo lettere minuscole e trattini: non si possono chiamare metodi arbitrari.
                if (!is_string($azione) || !preg_match('/^[a-z]+(-[a-z]+)*$/', $azione)) {
                    self::errore('nonTrovata');
                }
                $metodo = self::metodo($azione);
            }
            self::esegui($controller, $metodo);
        } catch (Throwable $errore) {
            error_log(ucfirst(self::fronte()) . ': ' . $errore->getMessage() . ' in ' . $errore->getFile() . ':' . $errore->getLine());
            self::$dati = null;
            self::errore('interno', self::spiega($errore));
        }
        if (self::$dati === null) {
            exit;   // l'azione ha già risposto da sola (reindirizzamento, immagine, JSON)
        }
        return self::$dati;
    }

    private static function esegui(string $controller, string $metodo): void
    {
        $classe = ucfirst($controller) . 'Controller';
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/controllers/' . $classe . '.php';
        if (!preg_match('/^[a-z]+$/', $controller) || !is_file($file)) {
            self::errore('nonTrovata');
        }
        require_once $file;
        if (!method_exists($classe, $metodo)) {
            self::errore('nonTrovata');
        }
        // Solo i metodi pubblici scritti nel controller sono azioni raggiungibili.
        $riflessione = new ReflectionMethod($classe, $metodo);
        if (!$riflessione->isPublic() || $riflessione->isStatic() || $riflessione->getDeclaringClass()->getName() !== $classe) {
            self::errore('nonTrovata');
        }
        if (self::$controllo !== null && (self::$controllo)($controller) === false) {
            exit;
        }
        (new $classe())->$metodo();
    }

    /** Mostra la pagina di errore del front end e termina. */
    public static function errore(string $tipo, string $dettaglio = ''): void
    {
        require_once dirname($_SERVER['SCRIPT_FILENAME']) . '/controllers/ErroreController.php';
        $tipo === 'interno' ? (new ErroreController())->interno($dettaglio) : (new ErroreController())->$tipo();
        if (self::$dati === null) {
            exit;
        }
    }

    /** Messaggio da mostrare per un errore interno (i dettagli tecnici restano nel log). */
    private static function spiega(Throwable $errore): string
    {
        if (config('debug')) {
            return $errore->getMessage();
        }
        if ($errore instanceof PDOException || str_starts_with($errore->getMessage(), 'Configurazione mancante') || str_contains($errore->getMessage(), 'non valida: generarla')) {
            return 'Il database non è configurato o non è raggiungibile. Controlla che MySQL sia avviato e che il file backend/php/.env sia compilato (vedi README).';
        }
        return '';
    }

    /**
     * Chiamata dai controller (Controller::vista): indica quale pagina mostrare e con quali dati.
     * Se è la pagina aperta dal browser i dati tornano al file, che prosegue con il suo HTML;
     * se è un'altra (per esempio la pagina di errore) viene inclusa subito al suo posto.
     */
    public static function mostra(string $pagina, array $dati, int $stato): void
    {
        if (!preg_match('/^[a-z]+(-[a-z]+)*$/', $pagina)) {
            throw new InvalidArgumentException('Nome della pagina non valido');
        }
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $pagina . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Pagina mancante: ' . $pagina);
        }
        http_response_code($stato);
        $dati['titolo'] = $dati['titolo'] ?? config('nome');

        if ($pagina === self::$ingresso && self::$dati === null) {
            self::$dati = $dati;
            return;
        }
        self::$pronti = $dati;
        self::includi($file);
        exit;
    }

    private static function includi(string $__file): void
    {
        require $__file;
    }
}
