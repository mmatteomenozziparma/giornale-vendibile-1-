<?php
/**
 * View: unisce una vista (es. home.php) al layout del front end.
 *
 * Se la richiesta arriva dal JavaScript del portale (single page application)
 * viene restituito solo il contenuto interno ("shell.php"), senza <head> e script:
 * il browser sostituisce le parti della pagina senza ricaricarla.
 */
declare(strict_types=1);

final class View
{
    public static function mostra(string $cartella, string $vista, array $dati): void
    {
        if (!preg_match('#^[a-z_]+(/[a-z_]+)?$#', $vista)) {
            throw new InvalidArgumentException('Nome della vista non valido');
        }
        $file = $cartella . '/' . $vista . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vista mancante: ' . $vista);
        }

        $dati['titolo'] = $dati['titolo'] ?? config('nome');
        $dati['contenuto'] = self::cattura($file, $dati);

        header('Content-Type: text/html; charset=utf-8');

        if (self::richiestaSpa() && is_file($cartella . '/shell.php')) {
            header('Vary: X-Requested-With');
            echo '<title>' . e($dati['titolo']) . '</title>';
            echo self::cattura($cartella . '/shell.php', $dati);
            return;
        }
        echo self::cattura($cartella . '/layout.php', $dati);
    }

    /** Include un pezzo di vista riutilizzabile (cartella views/partials). */
    public static function parziale(string $nome, array $dati = []): void
    {
        if (!preg_match('#^[a-z_]+$#', $nome)) {
            throw new InvalidArgumentException('Nome del parziale non valido');
        }
        echo self::cattura(dirname($_SERVER['SCRIPT_FILENAME']) . '/views/partials/' . $nome . '.php', $dati);
    }

    public static function richiestaSpa(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'wolfpress-spa';
    }

    private static function cattura(string $__file, array $__dati): string
    {
        extract($__dati, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (Throwable $errore) {
            ob_end_clean();
            throw $errore;
        }
        return (string) ob_get_clean();
    }
}
