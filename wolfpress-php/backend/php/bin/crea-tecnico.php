<?php
/**
 * Crea un account tecnico (l'unico tipo che non può registrarsi dal sito).
 * Uso:  php bin/crea-tecnico.php Nome Cognome email@esempio.it [responsabile]
 * La password viene chiesta a terminale, così non resta nella cronologia dei comandi.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/src/bootstrap.php';

[, $nome, $cognome, $email] = $argv + [null, '', '', ''];
$email = Cifratura::normalizzaEmail($email);
if ($nome === '' || $cognome === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Uso: php bin/crea-tecnico.php Nome Cognome email@esempio.it [responsabile]\n");
    exit(1);
}
$password = getenv('PASSWORD_TECNICO') ?: '';
if ($password === '') {
    fwrite(STDERR, 'Password (minimo 10 caratteri): ');
    $terminale = stream_isatty(STDIN);
    if ($terminale) {
        shell_exec('stty -echo');
    }
    $password = rtrim((string) fgets(STDIN), "\r\n");
    if ($terminale) {
        shell_exec('stty echo');
        fwrite(STDERR, PHP_EOL);
    }
}
try {
    Account::controllaPassword($password, $email);
    Database::transazione(static function () use ($nome, $cognome, $email, $password, $argv) {
        $id = Database::inserisci('generalita', [
            'tipo' => 'tecnico',
            'nome' => $nome,
            'cognome' => $cognome,
            'email_cifrata' => Cifratura::cifra($email),
            'email_hash' => Cifratura::indiceEmail($email),
            'password_hash' => Cifratura::hashPassword($password),
            'stato' => 'attivo',
            'email_verificata_il' => gmdate('Y-m-d H:i:s'),
        ]);
        Database::inserisci('tecnici', [
            'generalita_id' => $id,
            'livello' => ($argv[4] ?? '') === 'responsabile' ? 'responsabile' : 'operatore',
        ]);
    });
    echo "Tecnico creato: $email\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Errore: ' . (Database::doppione($e) ? 'email già registrata' : $e->getMessage()) . "\n");
    exit(1);
}
