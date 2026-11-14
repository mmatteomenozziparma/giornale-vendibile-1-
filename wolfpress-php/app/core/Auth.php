<?php
/**
 * Accesso dimostrativo al portale.
 *
 * ATTENZIONE: in questa demo si entra scegliendo un ruolo, senza password.
 * I permessi sono comunque controllati sul server (sessione PHP), quindi
 * per andare in produzione basta sostituire Auth::entra() con un vero login.
 */
declare(strict_types=1);

final class Auth
{
    /** Ruoli disponibili e relativa etichetta. */
    public const RUOLI = [
        'editore' => 'Editore',
        'giornalista' => 'Giornalista',
        'politico' => 'Politico',
        'tecnico' => 'Operatore tecnico',
    ];

    /** Controller che ogni ruolo può aprire. */
    private const PERMESSI = [
        'editore' => ['home', 'configuratore', 'cliente', 'articoli', 'editore', 'assistenza', 'demo'],
        'giornalista' => ['home', 'giornalista', 'articoli', 'demo'],
        'politico' => ['home', 'politico', 'demo'],
        'tecnico' => ['home', 'tecnico', 'demo'],
    ];

    /** Controller aperti anche senza accesso. */
    private const PUBBLICI = ['accesso', 'errore', 'media'];

    public static function avviaSessione(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('wolfpress_demo');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }

    public static function entra(string $ruolo, string $nome, string $profilo = ''): void
    {
        session_regenerate_id(true);
        $_SESSION['utente'] = ['ruolo' => $ruolo, 'nome' => $nome, 'profilo' => $profilo];
        unset($_SESSION['csrf']);
    }

    public static function esci(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function utente(): ?array
    {
        return $_SESSION['utente'] ?? null;
    }

    public static function ruolo(): string
    {
        return $_SESSION['utente']['ruolo'] ?? '';
    }

    public static function nome(): string
    {
        return $_SESSION['utente']['nome'] ?? '';
    }

    /** Identificativo del profilo scelto (giornalista, politico o operatore). */
    public static function profilo(): string
    {
        return $_SESSION['utente']['profilo'] ?? '';
    }

    public static function etichettaRuolo(): string
    {
        return self::RUOLI[self::ruolo()] ?? '';
    }

    public static function pubblico(string $controller): bool
    {
        return in_array($controller, self::PUBBLICI, true);
    }

    public static function puo(string $controller): bool
    {
        return self::pubblico($controller)
            || in_array($controller, self::PERMESSI[self::ruolo()] ?? [], true);
    }

    /** Messaggio da mostrare una sola volta nella pagina successiva. */
    public static function avviso(string $testo, string $tipo = 'ok'): void
    {
        $_SESSION['avviso'] = ['testo' => $testo, 'tipo' => $tipo];
    }

    public static function leggiAvviso(): ?array
    {
        $avviso = $_SESSION['avviso'] ?? null;
        unset($_SESSION['avviso']);
        return $avviso;
    }
}
