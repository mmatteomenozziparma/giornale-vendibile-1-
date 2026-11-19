<?php
/**
 * Richiesta e risposta HTTP: JSON, intestazioni di sicurezza, CORS,
 * sessione, protezione CSRF, validazione dei dati in ingresso.
 */
declare(strict_types=1);

final class Http
{
    private static ?array $corpo = null;

    public static function intestazioni(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header_remove('X-Powered-By');
        if (Config::produzione()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // CORS: solo le origini elencate in CORS_ORIGINI, mai "*".
        $origine = $_SERVER['HTTP_ORIGIN'] ?? '';
        $ammesse = array_filter(array_map('trim', explode(',', Config::get('CORS_ORIGINI'))));
        if ($origine !== '' && in_array($origine, $ammesse, true)) {
            header("Access-Control-Allow-Origin: $origine");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Vary: Origin');
        }
    }

    public static function avviaSessione(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('wolfpress_api');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,       // JavaScript non può leggere il cookie
            'samesite' => 'Lax',
            'secure' => Config::produzione() || (($_SERVER['HTTPS'] ?? 'off') !== 'off'),
        ]);
        session_start();

        // Scadenza per inattività: 2 ore.
        $adesso = time();
        if (isset($_SESSION['attivita']) && $adesso - $_SESSION['attivita'] > 7200) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['attivita'] = $adesso;
    }

    public static function tokenCsrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    /** Ogni richiesta che modifica dati deve avere l'intestazione X-CSRF-Token. */
    public static function controllaCsrf(): void
    {
        $ricevuto = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($ricevuto) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $ricevuto)) {
            throw new ErroreApi('Token CSRF mancante o non valido: richiedilo a GET /api/csrf', 403);
        }
    }

    public static function corpoGrezzo(): string
    {
        $grezzo = file_get_contents('php://input', false, null, 0, 1048577);
        if ($grezzo === false || strlen($grezzo) > 1048576) {
            throw new ErroreApi('Richiesta troppo grande', 413);
        }
        return $grezzo;
    }

    /** Dati già letti altrove (es. un form HTML di wolfpress-php): valgono come corpo della richiesta. */
    public static function imposta(array $dati): void
    {
        self::$corpo = $dati;
    }

    /** Corpo JSON della richiesta come array. */
    public static function corpo(): array
    {
        if (self::$corpo === null) {
            $grezzo = self::corpoGrezzo();
            $dati = $grezzo === '' ? [] : json_decode($grezzo, true, 16);
            if (!is_array($dati)) {
                throw new ErroreApi('Il corpo della richiesta deve essere JSON valido');
            }
            self::$corpo = $dati;
        }
        return self::$corpo;
    }

    /** Testo obbligatorio o facoltativo, con lunghezza massima. */
    public static function testo(string $campo, int $max, bool $obbligatorio = true): ?string
    {
        $valore = self::corpo()[$campo] ?? null;
        if ($valore === null || $valore === '') {
            if ($obbligatorio) {
                throw new ErroreApi("Campo obbligatorio: $campo", 422);
            }
            return null;
        }
        if (!is_string($valore) || !mb_check_encoding($valore, 'UTF-8')) {
            throw new ErroreApi("Campo non valido: $campo", 422);
        }
        $valore = trim($valore);
        if ($valore === '' && $obbligatorio) {
            throw new ErroreApi("Campo obbligatorio: $campo", 422);
        }
        if (mb_strlen($valore) > $max) {
            throw new ErroreApi("Campo troppo lungo: $campo (massimo $max caratteri)", 422);
        }
        return $valore === '' ? null : $valore;
    }

    /** Intero positivo (id) obbligatorio o facoltativo. */
    public static function intero(string $campo, bool $obbligatorio = true): ?int
    {
        $valore = self::corpo()[$campo] ?? null;
        if ($valore === null || $valore === '') {
            if ($obbligatorio) {
                throw new ErroreApi("Campo obbligatorio: $campo", 422);
            }
            return null;
        }
        if ((!is_int($valore) && !(is_string($valore) && ctype_digit($valore))) || (int) $valore < 1) {
            throw new ErroreApi("Campo non valido: $campo", 422);
        }
        return (int) $valore;
    }

    /** Uno dei valori ammessi. */
    public static function scelta(string $campo, array $ammessi, ?string $predefinito = null): string
    {
        $valore = self::corpo()[$campo] ?? $predefinito;
        if (!is_string($valore) || !in_array($valore, $ammessi, true)) {
            throw new ErroreApi("Campo non valido: $campo (ammessi: " . implode(', ', $ammessi) . ')', 422);
        }
        return $valore;
    }

    /** Elenco di id interi positivi (massimo 100). */
    public static function elencoId(string $campo): array
    {
        $valore = self::corpo()[$campo] ?? [];
        if (!is_array($valore) || count($valore) > 100) {
            throw new ErroreApi("Campo non valido: $campo", 422);
        }
        $ids = [];
        foreach ($valore as $id) {
            if (!is_int($id) || $id < 1) {
                throw new ErroreApi("Campo non valido: $campo", 422);
            }
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    /** Data e ora ISO (es. 2026-12-01T18:30) → formato del database. */
    public static function dataOra(string $campo, bool $obbligatorio = true): ?string
    {
        $valore = self::testo($campo, 40, $obbligatorio);
        if ($valore === null) {
            return null;
        }
        try {
            return (new DateTimeImmutable($valore))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            throw new ErroreApi("Data non valida: $campo", 422);
        }
    }

    public static function json(array $dati, int $stato = 200): never
    {
        http_response_code($stato);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** "Titolo dell'articolo" → "titolo-dell-articolo". */
    public static function slug(string $testo): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $testo) ?: $testo;
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)) ?? '', '-');
        return substr($slug !== '' ? $slug : 'senza-titolo', 0, 150);
    }
}
