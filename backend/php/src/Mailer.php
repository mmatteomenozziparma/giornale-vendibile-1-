<?php
/**
 * Invio delle email automatiche (registrazione, verifica, reset, inviti, ricevute).
 * Tre modalità (MAIL_DRIVER): smtp, mail, log. Nessuna libreria esterna.
 */
declare(strict_types=1);

final class Mailer
{
    /**
     * Spedisce un'email di solo testo. Non lancia eccezioni: se l'invio
     * fallisce lo scrive nel log e restituisce false, così un problema
     * del server di posta non blocca la registrazione.
     */
    public static function invia(string $a, string $oggetto, string $testo): bool
    {
        // Niente a-capo in indirizzi e oggetto: impedisce di iniettare intestazioni.
        $a = self::pulisci($a);
        $oggetto = self::pulisci($oggetto);
        if (filter_var($a, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        $mittente = self::pulisci(Config::get('MAIL_MITTENTE', 'no-reply@localhost'));
        $nome = self::pulisci(Config::get('MAIL_NOME_MITTENTE', 'WolfPress'));
        $intestazioni = [
            'From: ' . self::codifica($nome) . " <$mittente>",
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . (substr(strrchr($mittente, '@') ?: '@localhost', 1)) . '>',
            'Auto-Submitted: auto-generated',
        ];
        $corpo = rtrim(chunk_split(base64_encode($testo), 76, "\r\n"));

        try {
            switch (Config::get('MAIL_DRIVER', 'log')) {
                case 'smtp':
                    self::smtp($mittente, $a, $oggetto, $intestazioni, $corpo);
                    return true;
                case 'mail':
                    return mail($a, self::codifica($oggetto), $corpo, implode("\r\n", $intestazioni));
                default:
                    if (Config::produzione()) {
                        throw new RuntimeException('MAIL_DRIVER=log non è ammesso in produzione');
                    }
                    $riga = sprintf("--- %s\nA: %s\nOggetto: %s\n\n%s\n\n", date('c'), $a, $oggetto, $testo);
                    file_put_contents(RADICE . '/storage/email.log', $riga, FILE_APPEND | LOCK_EX);
                    return true;
            }
        } catch (Throwable $e) {
            error_log('Invio email fallito: ' . $e->getMessage());
            return false;
        }
    }

    private static function pulisci(string $valore): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $valore));
    }

    private static function codifica(string $testo): string
    {
        return '=?UTF-8?B?' . base64_encode($testo) . '?=';
    }

    /** Client SMTP minimo con TLS obbligatorio e AUTH LOGIN. */
    private static function smtp(string $da, string $a, string $oggetto, array $intestazioni, string $corpo): void
    {
        $host = Config::obbligatorio('SMTP_HOST');
        $porta = (int) Config::get('SMTP_PORTA', '587');
        $sicurezza = Config::get('SMTP_SICUREZZA', 'tls');
        $tls = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]];

        $socket = stream_socket_client(
            ($sicurezza === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$porta",
            $errno,
            $errore,
            15,
            STREAM_CLIENT_CONNECT,
            stream_context_create($tls)
        );
        if ($socket === false) {
            throw new RuntimeException("Connessione SMTP fallita: $errore");
        }
        stream_set_timeout($socket, 15);

        try {
            $dominio = substr(strrchr($da, '@') ?: '@localhost', 1);
            self::attendi($socket, 220);
            self::comando($socket, "EHLO $dominio", 250);
            if ($sicurezza === 'tls') {
                self::comando($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS non riuscito');
                }
                self::comando($socket, "EHLO $dominio", 250);
            }
            if (Config::get('SMTP_UTENTE') !== '') {
                self::comando($socket, 'AUTH LOGIN', 334);
                self::comando($socket, base64_encode(Config::get('SMTP_UTENTE')), 334);
                self::comando($socket, base64_encode(Config::get('SMTP_PASSWORD')), 235);
            }
            self::comando($socket, "MAIL FROM:<$da>", 250);
            self::comando($socket, "RCPT TO:<$a>", 250);
            self::comando($socket, 'DATA', 354);
            $messaggio = implode("\r\n", array_merge(
                ["To: <$a>", 'Subject: ' . self::codifica($oggetto)],
                $intestazioni
            )) . "\r\n\r\n" . $corpo;
            self::comando($socket, $messaggio . "\r\n.", 250);
            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    private static function comando($socket, string $comando, int $atteso): void
    {
        fwrite($socket, $comando . "\r\n");
        self::attendi($socket, $atteso);
    }

    private static function attendi($socket, int $atteso): void
    {
        do {
            $riga = fgets($socket, 1024);
            if ($riga === false) {
                throw new RuntimeException('Il server SMTP non risponde');
            }
        } while (isset($riga[3]) && $riga[3] === '-');   // risposte su più righe
        if ((int) substr($riga, 0, 3) !== $atteso) {
            throw new RuntimeException('Risposta SMTP inattesa: ' . trim($riga));
        }
    }

    // ---------------------------------------------------------------
    // Modelli delle email automatiche
    // ---------------------------------------------------------------

    private static function firma(): string
    {
        return "\n\n— " . Config::get('MAIL_NOME_MITTENTE', 'WolfPress') . "\nMessaggio automatico: non rispondere a questa email.";
    }

    /**
     * Indirizzo da mettere nelle email. Se sono configurati PORTALE_URL e SITO_URL
     * (front end wolfpress-php) i link portano lì; altrimenti alle API / FRONTEND_URL.
     */
    private static function link(string $azione, string $token, string $tipo): string
    {
        $base = Config::get($tipo === 'user' ? 'SITO_URL' : 'PORTALE_URL');
        if ($base !== '') {
            return "$base/index.php?r=" . ($tipo === 'user' ? 'account' : 'accesso') . "/$azione&token=$token";
        }
        return $azione === 'verifica'
            ? Config::get('APP_URL') . '/api/verifica-email?token=' . $token
            : Config::get('FRONTEND_URL') . '/nuova-password?token=' . $token;
    }

    public static function verifica(string $a, string $nome, string $token, string $tipo = 'user'): bool
    {
        $link = self::link('verifica', $token, $tipo);
        return self::invia($a, 'Conferma la tua registrazione', "Ciao $nome,\n\ngrazie per esserti registrato.\n"
            . "Per attivare l'account conferma il tuo indirizzo email entro 24 ore:\n\n$link\n\n"
            . 'Se non hai chiesto tu la registrazione, ignora questo messaggio.' . self::firma());
    }

    public static function benvenuto(string $a, string $nome, bool $inApprovazione): bool
    {
        $stato = $inApprovazione
            ? "Il tuo indirizzo è confermato. Il profilo è ora in attesa di approvazione da parte del nostro staff:\nriceverai un'email appena sarà attivo."
            : 'Il tuo indirizzo è confermato e il tuo account è attivo: ora puoi accedere.';
        return self::invia($a, 'Benvenuto', "Ciao $nome,\n\n$stato" . self::firma());
    }

    public static function approvato(string $a, string $nome): bool
    {
        return self::invia($a, 'Il tuo profilo è stato approvato', "Ciao $nome,\n\nil tuo profilo è stato approvato: ora puoi accedere al portale." . self::firma());
    }

    public static function giaRegistrato(string $a): bool
    {
        return self::invia($a, 'Hai già un account', "Ciao,\n\nqualcuno ha provato a registrarsi con questo indirizzo, che ha già un account.\n"
            . "Se eri tu, accedi oppure usa «Password dimenticata».\nSe non eri tu, non devi fare nulla." . self::firma());
    }

    /** Account creato da un editore o da un tecnico: l'interessato sceglie da sé la password. */
    public static function invitoAccount(string $a, string $nome, string $token, string $tipo): bool
    {
        $link = self::link('nuova-password', $token, $tipo);
        return self::invia($a, 'Il tuo account è pronto', "Ciao $nome,\n\nè stato creato un account a tuo nome.\n"
            . "Per attivarlo scegli la tua password entro 3 giorni:\n\n$link" . self::firma());
    }

    public static function reset(string $a, string $nome, string $token, string $tipo = 'user'): bool
    {
        $link = self::link('nuova-password', $token, $tipo);
        return self::invia($a, 'Reimposta la password', "Ciao $nome,\n\nper scegliere una nuova password apri questo link entro 1 ora:\n\n$link\n\n"
            . 'Se non hai chiesto tu il cambio, ignora questo messaggio: la password resta quella attuale.' . self::firma());
    }

    public static function passwordCambiata(string $a, string $nome): bool
    {
        return self::invia($a, 'La tua password è stata cambiata', "Ciao $nome,\n\nla password del tuo account è stata appena cambiata.\n"
            . 'Se non sei stato tu, contatta subito l\'assistenza.' . self::firma());
    }

    public static function invitoEvento(string $a, string $nome, array $evento): bool
    {
        return self::invia($a, 'Invito: ' . $evento['titolo'], "Ciao $nome,\n\nsei stato invitato all'evento «{$evento['titolo']}».\n"
            . "Quando: {$evento['inizio']} (UTC)\nDove: " . ($evento['luogo'] ?: 'da definire')
            . "\n\nAccedi al portale per accettare o rifiutare l'invito." . self::firma());
    }

    public static function ricevutaDonazione(string $a, string $nome, int $centesimi, string $destinatario): bool
    {
        $importo = number_format($centesimi / 100, 2, ',', '.');
        return self::invia($a, 'Grazie per la tua donazione', "Ciao $nome,\n\nabbiamo ricevuto la tua donazione di € $importo a favore di $destinatario.\nGrazie di cuore." . self::firma());
    }

    public static function ticketAperto(string $a, string $nome, string $codice, string $oggetto): bool
    {
        return self::invia($a, "Ticket $codice ricevuto", "Ciao $nome,\n\nabbiamo ricevuto la tua richiesta «{$oggetto}».\nCodice: $codice\nUn tecnico ti risponderà al più presto." . self::firma());
    }
}
