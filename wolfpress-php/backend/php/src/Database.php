<?php
/**
 * Accesso al database con PDO.
 * Tutte le query passano da prepared statement veri (niente emulazione):
 * i dati dell'utente non vengono mai concatenati nell'SQL.
 */
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('DB_HOST', '127.0.0.1'),
                Config::get('DB_PORTA', '3306'),
                Config::get('DB_NOME', 'wolfpress')
            );
            self::$pdo = new PDO($dsn, Config::obbligatorio('DB_UTENTE'), Config::get('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
        }
        return self::$pdo;
    }

    public static function esegui(string $sql, array $parametri = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($parametri);
        return $stmt;
    }

    public static function riga(string $sql, array $parametri = []): ?array
    {
        $riga = self::esegui($sql, $parametri)->fetch();
        return $riga === false ? null : $riga;
    }

    public static function righe(string $sql, array $parametri = []): array
    {
        return self::esegui($sql, $parametri)->fetchAll();
    }

    public static function valore(string $sql, array $parametri = []): mixed
    {
        $valore = self::esegui($sql, $parametri)->fetchColumn();
        return $valore === false ? null : $valore;
    }

    /** INSERT e restituisce l'id creato. */
    public static function inserisci(string $tabella, array $dati): int
    {
        $colonne = array_keys($dati);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $tabella,
            implode(', ', array_map(static fn ($c) => "`$c`", $colonne)),
            implode(', ', array_fill(0, count($colonne), '?'))
        );
        self::esegui($sql, array_values($dati));
        return (int) self::pdo()->lastInsertId();
    }

    /** Esegue la funzione dentro una transazione: o tutto o niente. */
    public static function transazione(callable $operazione): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $esito = $operazione();
            $pdo->commit();
            return $esito;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** true se l'eccezione è una violazione di chiave unica (doppione). */
    public static function doppione(Throwable $e): bool
    {
        return $e instanceof PDOException && ($e->errorInfo[1] ?? 0) === 1062;
    }
}
