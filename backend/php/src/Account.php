<?php
/**
 * Registrazione, verifica email, accesso, reset password e permessi.
 */
declare(strict_types=1);

final class Account
{
    /** Tabella del profilo per ogni tipo di account. */
    public const TABELLE = [
        'user' => 'user',
        'editore' => 'editori',
        'politico' => 'politici',
        'tecnico' => 'tecnici',
        'giornalista' => 'giornalisti',
    ];

    /** Chi può registrarsi da solo. I tecnici si creano solo con bin/crea-tecnico.php. */
    private const REGISTRABILI = ['user', 'editore', 'politico', 'giornalista'];

    private static ?array $corrente = null;

    // ------------------------------------------------------------
    // Limite ai tentativi per indirizzo IP
    // ------------------------------------------------------------

    private static function chiaveLimite(string $azione): string
    {
        // Si usa solo REMOTE_ADDR: X-Forwarded-For può essere falsificato dal client.
        return Cifratura::impronta($azione . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
    }

    public static function limita(string $azione, int $massimo, int $minuti): void
    {
        $fatti = (int) Database::valore(
            'SELECT COUNT(*) FROM tentativi WHERE chiave = ? AND creato_il > NOW() - INTERVAL ? MINUTE',
            [self::chiaveLimite($azione), $minuti]
        );
        if ($fatti >= $massimo) {
            throw new ErroreApi('Troppi tentativi: riprova più tardi.', 429);
        }
    }

    public static function segna(string $azione): void
    {
        Database::esegui('INSERT INTO tentativi (chiave) VALUES (?)', [self::chiaveLimite($azione)]);
        if (random_int(1, 50) === 1) {   // pulizia occasionale
            Database::esegui('DELETE FROM tentativi WHERE creato_il < NOW() - INTERVAL 1 DAY');
            Database::esegui('DELETE FROM token_email WHERE scade_il < NOW() - INTERVAL 7 DAY');
        }
    }

    // ------------------------------------------------------------
    // Registrazione e verifica dell'email
    // ------------------------------------------------------------

    public static function controllaPassword(string $password, string $email): void
    {
        $lunghezza = mb_strlen($password);
        if ($lunghezza < 10 || $lunghezza > 128) {
            throw new ErroreApi('La password deve avere da 10 a 128 caratteri.', 422);
        }
        if (mb_strtolower($password) === Cifratura::normalizzaEmail($email)) {
            throw new ErroreApi('La password non può essere uguale all\'email.', 422);
        }
    }

    public static function emailValida(string $campo = 'email'): string
    {
        $email = Cifratura::normalizzaEmail((string) Http::testo($campo, 254));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ErroreApi('Indirizzo email non valido.', 422);
        }
        return $email;
    }

    public static function registra(): array
    {
        self::limita('registrazione', 10, 60);
        self::segna('registrazione');

        $tipo = Http::scelta('tipo', self::REGISTRABILI, 'user');
        $nome = (string) Http::testo('nome', 120);
        $cognome = (string) Http::testo('cognome', 120);
        $email = self::emailValida();
        $password = Http::corpo()['password'] ?? '';
        if (!is_string($password)) {
            throw new ErroreApi('Campo non valido: password', 422);
        }
        self::controllaPassword($password, $email);
        if ((Http::corpo()['privacy'] ?? false) !== true) {
            throw new ErroreApi('Per registrarsi occorre accettare l\'informativa sulla privacy.', 422);
        }

        // Dati specifici del profilo, validati prima di scrivere qualcosa.
        $profilo = match ($tipo) {
            'user' => ['nickname' => Http::testo('nickname', 60, false), 'citta' => Http::testo('citta', 120, false)],
            'editore' => ['ragione_sociale' => Http::testo('ragione_sociale', 200), 'partita_iva' => Http::testo('partita_iva', 20, false)],
            'politico' => [
                'slug' => Http::slug("$nome $cognome") . '-' . bin2hex(random_bytes(3)),
                'partito' => Http::testo('partito', 160, false),
                'carica' => Http::testo('carica', 160, false),
                'territorio' => Http::testo('territorio', 160, false),
            ],
            'giornalista' => ['giornale_id' => Http::intero('giornale_id'), 'sezione' => Http::testo('sezione', 120, false)],
        };

        $hash = Cifratura::hashPassword($password);
        $risposta = [
            'ok' => true,
            'messaggio' => 'Controlla la tua casella di posta: ti abbiamo inviato un\'email per completare la registrazione.',
        ];

        try {
            $token = Database::transazione(static function () use ($tipo, $nome, $cognome, $email, $hash, $profilo) {
                $id = Database::inserisci('generalita', [
                    'tipo' => $tipo,
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'email_cifrata' => Cifratura::cifra($email),
                    'email_hash' => Cifratura::indiceEmail($email),
                    'password_hash' => $hash,
                    'consenso_privacy_il' => gmdate('Y-m-d H:i:s'),
                ]);
                Database::inserisci(self::TABELLE[$tipo], ['generalita_id' => $id] + $profilo);
                return self::creaToken($id, 'verifica', 24 * 60);
            });
        } catch (PDOException $e) {
            if (Database::doppione($e) && str_contains($e->getMessage(), 'uq_generalita_email')) {
                // Email già registrata: stessa risposta di una registrazione riuscita,
                // così non si può scoprire chi è iscritto. L'avviso arriva per email.
                Mailer::giaRegistrato($email);
                return $risposta;
            }
            throw $e;
        }

        Mailer::verifica($email, $nome, $token);
        return $risposta;
    }

    private static function creaToken(int $generalitaId, string $tipo, int $minuti): string
    {
        [$token, $hash] = Cifratura::nuovoToken();
        Database::esegui('UPDATE token_email SET usato_il = NOW() WHERE generalita_id = ? AND tipo = ? AND usato_il IS NULL', [$generalitaId, $tipo]);
        Database::esegui(
            'INSERT INTO token_email (generalita_id, tipo, token_hash, scade_il) VALUES (?, ?, ?, NOW() + INTERVAL ? MINUTE)',
            [$generalitaId, $tipo, $hash, $minuti]
        );
        return $token;
    }

    /** Consuma un token valido (monouso, non scaduto) e restituisce l'account. */
    private static function consumaToken(string $token, string $tipo): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new ErroreApi('Link non valido o scaduto.', 400);
        }
        $riga = Database::riga(
            'SELECT t.id AS token_id, g.* FROM token_email t JOIN generalita g ON g.id = t.generalita_id
             WHERE t.token_hash = ? AND t.tipo = ? AND t.usato_il IS NULL AND t.scade_il > NOW() FOR UPDATE',
            [hash('sha256', $token), $tipo]
        );
        if ($riga === null) {
            throw new ErroreApi('Link non valido o scaduto.', 400);
        }
        Database::esegui('UPDATE token_email SET usato_il = NOW() WHERE id = ?', [$riga['token_id']]);
        return $riga;
    }

    public static function verificaEmail(string $token): string
    {
        self::limita('verifica', 30, 60);
        self::segna('verifica');
        $account = Database::transazione(static function () use ($token) {
            $account = self::consumaToken($token, 'verifica');
            // Solo i lettori sono attivi subito: gli altri profili li approva un tecnico.
            $account['stato'] = $account['tipo'] === 'user' ? 'attivo' : 'in_approvazione';
            Database::esegui(
                "UPDATE generalita SET stato = ?, email_verificata_il = NOW() WHERE id = ? AND stato = 'da_verificare'",
                [$account['stato'], $account['id']]
            );
            return $account;
        });
        Mailer::benvenuto(Cifratura::decifra($account['email_cifrata']), $account['nome'], $account['stato'] !== 'attivo');
        return $account['stato'];
    }

    // ------------------------------------------------------------
    // Accesso e sessione
    // ------------------------------------------------------------

    public static function accedi(): array
    {
        self::limita('accesso', 20, 15);
        $email = Cifratura::normalizzaEmail((string) Http::testo('email', 254));
        $password = Http::corpo()['password'] ?? '';
        if (!is_string($password) || $password === '' || strlen($password) > 1024) {
            throw new ErroreApi('Email o password non corretti.', 401);
        }

        $account = Database::riga("SELECT * FROM generalita WHERE email_hash = ? AND tipo <> 'giornale'", [Cifratura::indiceEmail($email)]);
        $bloccato = $account !== null && $account['bloccato_fino'] !== null && strtotime($account['bloccato_fino'] . ' UTC') > time();

        // La verifica si esegue sempre (anche se l'account non esiste) così
        // il tempo di risposta non rivela quali email sono registrate.
        $hash = $account['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$S25BWTVDNWY2eC9URzBBMg$KE0kkmoZWoUfozNhjT/oiWwOCZN0gAflNRoxZ2PDfRI';
        $corretta = Cifratura::verificaPassword($password, $hash) && $account !== null && !$bloccato;

        if (!$corretta) {
            self::segna('accesso');
            if ($account !== null && !$bloccato) {
                // Dopo 5 errori consecutivi l'account resta bloccato 15 minuti.
                Database::esegui(
                    'UPDATE generalita SET accessi_falliti = accessi_falliti + 1,
                        bloccato_fino = IF(accessi_falliti >= 5, NOW() + INTERVAL 15 MINUTE, bloccato_fino),
                        accessi_falliti = IF(accessi_falliti >= 5, 0, accessi_falliti)
                     WHERE id = ?',
                    [$account['id']]
                );
            }
            throw new ErroreApi('Email o password non corretti.', 401);
        }

        // Da qui la password è giusta: si può dire all'utente perché non entra.
        if ($account['stato'] !== 'attivo') {
            throw new ErroreApi(match ($account['stato']) {
                'da_verificare' => 'Conferma prima il tuo indirizzo email: controlla la posta.',
                'in_approvazione' => 'Il tuo profilo è in attesa di approvazione.',
                default => 'Account sospeso: contatta l\'assistenza.',
            }, 403);
        }

        $nuovoHash = Cifratura::daAggiornare($account['password_hash']) ? Cifratura::hashPassword($password) : $account['password_hash'];
        Database::esegui(
            'UPDATE generalita SET accessi_falliti = 0, bloccato_fino = NULL, ultimo_accesso_il = NOW(), password_hash = ? WHERE id = ?',
            [$nuovoHash, $account['id']]
        );

        session_regenerate_id(true);          // contro la "session fixation"
        $_SESSION = [
            'gid' => (int) $account['id'],
            'pv' => self::versionePassword($nuovoHash),
            'attivita' => time(),
        ];
        self::$corrente = null;
        return self::pubblico(self::corrente());
    }

    public static function esci(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        self::$corrente = null;
    }

    /** Cambia al cambio password: tutte le vecchie sessioni decadono. */
    private static function versionePassword(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 16);
    }

    /** Account della sessione, riletto dal database a ogni richiesta. */
    public static function corrente(): ?array
    {
        if (self::$corrente !== null) {
            return self::$corrente;
        }
        $id = $_SESSION['gid'] ?? null;
        if (!is_int($id)) {
            return null;
        }
        $account = Database::riga("SELECT * FROM generalita WHERE id = ? AND stato = 'attivo'", [$id]);
        if ($account === null || !hash_equals(self::versionePassword((string) $account['password_hash']), (string) ($_SESSION['pv'] ?? ''))) {
            self::esci();
            return null;
        }
        $tabella = self::TABELLE[$account['tipo']];
        $account['profilo'] = Database::riga("SELECT * FROM `$tabella` WHERE generalita_id = ?", [$id]) ?? [];
        return self::$corrente = $account;
    }

    /** Richiede l'accesso e, se indicati, uno dei tipi di account ammessi. */
    public static function richiedi(string ...$tipi): array
    {
        $account = self::corrente();
        if ($account === null) {
            throw new ErroreApi('Devi effettuare l\'accesso.', 401);
        }
        if ($tipi !== [] && !in_array($account['tipo'], $tipi, true)) {
            throw new ErroreApi('Non hai i permessi per questa operazione.', 403);
        }
        return $account;
    }

    /** Id nella tabella del profilo (user.id, politici.id, ...). */
    public static function profiloId(array $account): int
    {
        return (int) $account['profilo']['id'];
    }

    /** Dati dell'account che si possono restituire al client. */
    public static function pubblico(array $account): array
    {
        $profilo = $account['profilo'];
        unset($profilo['generalita_id']);
        return [
            'id' => (int) $account['id'],
            'tipo' => $account['tipo'],
            'nome' => $account['nome'],
            'cognome' => $account['cognome'],
            'email' => Cifratura::decifra($account['email_cifrata']),
            'profilo' => $profilo,
        ];
    }

    public static function email(array $account): string
    {
        return Cifratura::decifra($account['email_cifrata']);
    }

    // ------------------------------------------------------------
    // Password dimenticata
    // ------------------------------------------------------------

    public static function richiediReset(): void
    {
        self::limita('reset', 5, 60);
        self::segna('reset');
        $email = self::emailValida();
        $account = Database::riga(
            "SELECT * FROM generalita WHERE email_hash = ? AND tipo <> 'giornale' AND stato <> 'sospeso'",
            [Cifratura::indiceEmail($email)]
        );
        if ($account !== null) {
            $token = Database::transazione(static fn () => self::creaToken((int) $account['id'], 'reset', 60));
            Mailer::reset($email, $account['nome'], $token);
        }
        // La risposta al client è identica in entrambi i casi.
    }

    public static function nuovaPassword(): void
    {
        self::limita('reset-conferma', 10, 60);
        self::segna('reset-conferma');
        $token = (string) Http::testo('token', 64);
        $password = Http::corpo()['password'] ?? '';
        if (!is_string($password)) {
            throw new ErroreApi('Campo non valido: password', 422);
        }
        $account = Database::transazione(static function () use ($token, $password) {
            $account = self::consumaToken($token, 'reset');
            self::controllaPassword($password, Cifratura::decifra($account['email_cifrata']));
            Database::esegui(
                'UPDATE generalita SET password_hash = ?, accessi_falliti = 0, bloccato_fino = NULL WHERE id = ?',
                [Cifratura::hashPassword($password), $account['id']]
            );
            return $account;
        });
        self::esci();
        Mailer::passwordCambiata(Cifratura::decifra($account['email_cifrata']), $account['nome']);
    }
}
