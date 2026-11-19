<?php
/**
 * Cifratura dei dati personali e protezione delle password.
 *
 *  - EMAIL (e telefono): cifratura autenticata XSalsa20-Poly1305 (libsodium).
 *    Serve poterla rileggere per spedire le email, quindi è reversibile ma
 *    solo con CHIAVE_CIFRATURA, che sta fuori dal database.
 *  - INDICE CIECO: HMAC-SHA256 dell'email normalizzata. Permette di cercare
 *    "esiste questa email?" e di imporre l'unicità senza salvarla in chiaro.
 *  - PASSWORD: non si cifra, si fa l'hash con Argon2id (non reversibile).
 *    Prima dell'hash viene mescolata con un "pepe" segreto: chi ruba solo
 *    il database non può nemmeno tentare le password a forza bruta.
 */
declare(strict_types=1);

final class Cifratura
{
    private static function chiave(string $nome): string
    {
        $chiave = base64_decode(Config::obbligatorio($nome), true);
        if ($chiave === false || strlen($chiave) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException("$nome non valida: generarla con bin/genera-chiavi.php");
        }
        return $chiave;
    }

    public static function cifra(string $testo): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($testo, $nonce, self::chiave('CHIAVE_CIFRATURA')));
    }

    public static function decifra(string $cifrato): string
    {
        $dati = base64_decode($cifrato, true);
        if ($dati === false || strlen($dati) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Dato cifrato non valido');
        }
        $testo = sodium_crypto_secretbox_open(
            substr($dati, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($dati, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            self::chiave('CHIAVE_CIFRATURA')
        );
        if ($testo === false) {
            throw new RuntimeException('Dato cifrato alterato o chiave errata');
        }
        return $testo;
    }

    public static function normalizzaEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Indice cieco dell'email: stesso indirizzo → stesso valore. */
    public static function indiceEmail(string $email): string
    {
        return hash_hmac('sha256', self::normalizzaEmail($email), self::chiave('CHIAVE_INDICE'));
    }

    /** HMAC generico con la chiave dell'indice (es. per non salvare gli IP in chiaro). */
    public static function impronta(string $valore): string
    {
        return hash_hmac('sha256', $valore, self::chiave('CHIAVE_INDICE'));
    }

    private static function conPepe(string $password): string
    {
        return base64_encode(hash_hmac('sha256', $password, self::chiave('PEPE_PASSWORD'), true));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash(self::conPepe($password), PASSWORD_ARGON2ID);
    }

    public static function verificaPassword(string $password, string $hash): bool
    {
        return password_verify(self::conPepe($password), $hash);
    }

    public static function daAggiornare(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }

    /** Token casuale da spedire per email: [token in chiaro, hash da salvare]. */
    public static function nuovoToken(): array
    {
        $token = bin2hex(random_bytes(32));
        return [$token, hash('sha256', $token)];
    }
}
