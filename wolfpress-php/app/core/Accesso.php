<?php
/**
 * Operazioni sugli account condivise da portale e sito: ogni metodo passa
 * i dati del form al backend (classe Account) e restituisce il messaggio
 * di errore da mostrare, oppure '' se è andato tutto bene.
 */
declare(strict_types=1);

final class Accesso
{
    /** Esegue l'operazione e traduce gli errori previsti in un messaggio per l'utente. */
    private static function prova(array $dati, callable $operazione): string
    {
        Http::imposta($dati);
        try {
            $operazione();
            return '';
        } catch (ErroreApi $e) {
            return $e->getMessage();
        }
    }

    private static function testo(string $campo): string
    {
        $valore = $_POST[$campo] ?? '';
        return is_string($valore) ? $valore : '';
    }

    public static function entra(): string
    {
        $errore = self::prova(['email' => self::testo('email'), 'password' => self::testo('password')], static fn () => Account::accedi());
        Auth::aggiorna();
        return $errore;
    }

    /** Registrazione: $tipo è già stato scelto dal controller tra quelli ammessi dal front end. */
    public static function registra(string $tipo): string
    {
        $dati = ['tipo' => $tipo, 'privacy' => self::testo('privacy') === '1'];
        foreach (['nome', 'cognome', 'email', 'password', 'ragione_sociale', 'partita_iva', 'partito', 'carica', 'territorio', 'livello', 'giornale_id', 'sezione', 'citta'] as $campo) {
            if (self::testo($campo) !== '') {
                $dati[$campo] = self::testo($campo);
            }
        }
        if (self::testo('password') !== self::testo('password2')) {
            return 'Le due password non coincidono.';
        }
        return self::prova($dati, static fn () => Account::registra());
    }

    /** Restituisce [errore, stato dell'account dopo la verifica]. */
    public static function verifica(string $token): array
    {
        $stato = '';
        $errore = self::prova([], static function () use ($token, &$stato) {
            $stato = Account::verificaEmail($token);
        });
        return [$errore, $stato];
    }

    public static function richiediReset(): string
    {
        return self::prova(['email' => self::testo('email')], static fn () => Account::richiediReset());
    }

    public static function nuovaPassword(): string
    {
        if (self::testo('password') !== self::testo('password2')) {
            return 'Le due password non coincidono.';
        }
        $errore = self::prova(['token' => self::testo('token'), 'password' => self::testo('password')], static fn () => Account::nuovaPassword());
        Auth::aggiorna();
        return $errore;
    }
}
