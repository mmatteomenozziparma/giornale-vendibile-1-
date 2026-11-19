<?php
/**
 * View: pezzi di pagina riutilizzabili (cartella "parti" del front end).
 *
 * Dice anche se la richiesta arriva dal JavaScript del portale (single page
 * application): in quel caso header.php e footer.php non scrivono <head> e
 * script, e il browser sostituisce solo l'interno della pagina.
 */
declare(strict_types=1);

final class View
{
    /** Include un pezzo di pagina riutilizzabile (cartella "parti" del front end). */
    public static function parziale(string $nome, array $dati = []): void
    {
        if (!preg_match('#^[a-z_]+$#', $nome)) {
            throw new InvalidArgumentException('Nome del parziale non valido');
        }
        echo self::cattura(dirname($_SERVER['SCRIPT_FILENAME']) . '/parti/' . $nome . '.php', $dati);
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
