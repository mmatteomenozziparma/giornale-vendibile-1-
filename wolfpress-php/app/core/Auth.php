<?php
/**
 * Accesso al portale e al sito con account veri (tabella "generalita").
 *
 * Email e password sono verificate dal backend (classe Account in backend/php):
 * qui si legge soltanto chi è collegato e che cosa può aprire.
 * I permessi sono sempre controllati sul server.
 */
declare(strict_types=1);

final class Auth
{
    /** Ruoli del portale e relativa etichetta. */
    public const RUOLI = [
        'editore' => 'Editore',
        'giornalista' => 'Giornalista',
        'politico' => 'Politico',
        'tecnico' => 'Operatore tecnico',
    ];

    /** Controller che ogni ruolo può aprire. */
    private const PERMESSI = [
        'editore' => ['home', 'configuratore', 'cliente', 'articoli', 'editore', 'assistenza', 'testata', 'eventi'],
        'giornalista' => ['home', 'giornalista', 'articoli', 'eventi'],
        'politico' => ['home', 'politico', 'eventi'],
        'tecnico' => ['home', 'tecnico'],
    ];

    /** Controller aperti anche senza accesso. */
    private const PUBBLICI = ['accesso', 'errore', 'media'];

    private static ?array $utente = null;
    private static bool $letto = false;

    public static function avviaSessione(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('wolfpress');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => Config::produzione() || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();

        // Scadenza per inattività: 2 ore.
        if (isset($_SESSION['attivita']) && time() - $_SESSION['attivita'] > 7200) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['attivita'] = time();
    }

    /** Dimentica i dati letti (dopo accesso, uscita o modifica del profilo). */
    public static function aggiorna(): void
    {
        self::$utente = null;
        self::$letto = false;
    }

    public static function esci(): void
    {
        Account::esci();
        self::aggiorna();
    }

    /**
     * Utente collegato, riletto dal database a ogni richiesta:
     * un account sospeso o con password cambiata viene scollegato subito.
     */
    public static function utente(): ?array
    {
        if (self::$letto) {
            return self::$utente;
        }
        self::$letto = true;
        $account = session_status() === PHP_SESSION_ACTIVE ? Account::corrente() : null;
        if ($account === null) {
            return self::$utente = null;
        }
        $profilo = $account['profilo'];
        $testata = '';
        if ($account['tipo'] === 'editore') {
            // Un editore può avere più testate: lavora su quella scelta dal menu, altrimenti sulla prima.
            $mie = array_column(Database::righe('SELECT slug, nome FROM giornali WHERE editore_id = ? ORDER BY id', [$profilo['id']]), 'nome', 'slug');
            $scelta = (string) ($_SESSION['testata'] ?? '');
            $testata = isset($mie[$scelta]) ? $scelta : (string) (array_key_first($mie) ?? '');
        } elseif ($account['tipo'] === 'giornalista') {
            $testata = (string) Database::valore('SELECT slug FROM giornali WHERE id = ?', [$profilo['giornale_id']]);
        }
        return self::$utente = [
            'id' => (int) $account['id'],
            'ruolo' => $account['tipo'],
            'nome' => trim($account['nome'] . ' ' . $account['cognome']),
            // I politici si riconoscono dallo slug della scheda, gli altri dal numero del profilo.
            'profilo' => $account['tipo'] === 'politico' ? (string) $profilo['slug'] : (string) $profilo['id'],
            'profilo_id' => (int) $profilo['id'],
            'testata' => $testata,
            'testate' => $mie ?? [],
            'email' => Account::email($account),
        ];
    }

    /** Id dell'account nella tabella generalita (0 se nessuno è collegato). */
    public static function id(): int
    {
        return self::utente()['id'] ?? 0;
    }

    public static function ruolo(): string
    {
        return self::utente()['ruolo'] ?? '';
    }

    public static function nome(): string
    {
        return self::utente()['nome'] ?? '';
    }

    /** Identificativo del profilo collegato (giornalista, politico o operatore). */
    public static function profilo(): string
    {
        return self::utente()['profilo'] ?? '';
    }

    /** Numero del profilo nella sua tabella (user.id, giornalisti.id, ...). */
    public static function profiloId(): int
    {
        return self::utente()['profilo_id'] ?? 0;
    }

    /** Slug della testata dell'editore o del giornalista collegato ('' se non ne ha). */
    public static function testata(): string
    {
        return self::utente()['testata'] ?? '';
    }

    /** Testate dell'editore collegato: [slug => nome]. */
    public static function testate(): array
    {
        return self::utente()['testate'] ?? [];
    }

    /** L'editore sceglie su quale delle sue testate lavorare. */
    public static function scegliTestata(string $slug): bool
    {
        if (!isset(self::testate()[$slug])) {
            return false;
        }
        $_SESSION['testata'] = $slug;
        self::aggiorna();
        return true;
    }

    public static function etichettaRuolo(): string
    {
        return self::RUOLI[self::ruolo()] ?? (self::ruolo() === 'user' ? 'Lettore' : '');
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
