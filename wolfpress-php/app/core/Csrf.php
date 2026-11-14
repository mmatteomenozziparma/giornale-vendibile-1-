<?php
/**
 * Protezione dei form (CSRF): ogni form del portale porta un codice segreto
 * legato alla sessione; senza il codice giusto l'invio viene rifiutato.
 */
declare(strict_types=1);

final class Csrf
{
    public static function codice(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Campo nascosto da mettere dentro ogni <form method="post">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::codice()) . '">';
    }

    public static function valido(): bool
    {
        $inviato = $_POST['_csrf'] ?? '';
        return is_string($inviato) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $inviato);
    }
}
