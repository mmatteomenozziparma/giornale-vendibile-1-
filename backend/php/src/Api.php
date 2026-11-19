<?php
/**
 * Le operazioni delle API usate dai due front end (portale e sito).
 * Ogni metodo controlla da sé chi è l'utente e che cosa può fare.
 */
declare(strict_types=1);

final class Api
{
    // ============================================================
    // Giornali e articoli dei giornali
    // ============================================================

    public static function giornali(): array
    {
        return ['giornali' => Database::righe(
            "SELECT id, nome, slug, descrizione, dominio, prezzo_mensile_cent, prezzo_annuale_cent
             FROM giornali WHERE stato = 'attivo' ORDER BY nome"
        )];
    }

    public static function creaGiornale(): array
    {
        $editore = Account::richiedi('editore');
        $nome = (string) Http::testo('nome', 160);
        $email = Account::emailValida();
        $slug = Http::slug($nome);
        $prezzi = [];
        foreach (['prezzo_mensile_cent', 'prezzo_annuale_cent'] as $campo) {
            $prezzi[$campo] = Http::intero($campo, false);
            if ($prezzi[$campo] !== null && ($prezzi[$campo] < 100 || $prezzi[$campo] > 100000)) {
                throw new ErroreApi("Campo non valido: $campo (da 100 a 100000 centesimi)", 422);
            }
        }
        $id = Database::transazione(static function () use ($editore, $nome, $email, $slug, $prezzi) {
            // Scheda di contatto della testata: nessuna password, non può fare login.
            $generalita = Database::inserisci('generalita', [
                'tipo' => 'giornale',
                'nome' => $nome,
                'email_cifrata' => Cifratura::cifra($email),
                'email_hash' => Cifratura::impronta("giornale|$slug|$email"),
                'stato' => 'attivo',
            ]);
            return Database::inserisci('giornali', [
                'generalita_id' => $generalita,
                'editore_id' => Account::profiloId($editore),
                'nome' => $nome,
                'slug' => $slug,
                'descrizione' => Http::testo('descrizione', 2000, false),
                'dominio' => Http::testo('dominio', 190, false),
            ] + $prezzi);
        });
        return ['id' => $id, 'slug' => $slug];
    }

    /** Il giornale indicato, solo se l'account può scriverci (editore proprietario o suo giornalista). */
    private static function giornaleMio(array $account, int $giornaleId, bool $soloEditore = false): array
    {
        $giornale = Database::riga('SELECT * FROM giornali WHERE id = ?', [$giornaleId]);
        $mio = $giornale !== null && (
            ($account['tipo'] === 'editore' && (int) $giornale['editore_id'] === Account::profiloId($account))
            || (!$soloEditore && $account['tipo'] === 'giornalista' && (int) $account['profilo']['giornale_id'] === $giornaleId)
        );
        if (!$mio) {
            throw new ErroreApi('Non hai i permessi su questo giornale.', 403);
        }
        return $giornale;
    }

    public static function articoliGiornale(int $giornaleId): array
    {
        return ['articoli' => Database::righe(
            "SELECT a.id, a.titolo, a.slug, a.sezione, a.sommario, a.corpo, a.pubblicato_il,
                    CONCAT(g.nome, ' ', g.cognome) AS autore
             FROM articoli_giornali a
             LEFT JOIN giornalisti gi ON gi.id = a.giornalista_id
             LEFT JOIN generalita g ON g.id = gi.generalita_id
             WHERE a.giornale_id = ? AND a.stato = 'pubblicato'
             ORDER BY a.pubblicato_il DESC LIMIT 50",
            [$giornaleId]
        )];
    }

    public static function creaArticoloGiornale(int $giornaleId): array
    {
        $account = Account::richiedi('editore', 'giornalista');
        self::giornaleMio($account, $giornaleId);
        $titolo = (string) Http::testo('titolo', 220);
        // Il giornalista manda in revisione; solo l'editore pubblica.
        $pubblica = $account['tipo'] === 'editore' && (Http::corpo()['pubblica'] ?? false) === true;
        $stato = $pubblica ? 'pubblicato' : ($account['tipo'] === 'giornalista' ? 'in_revisione' : 'bozza');
        $id = Database::inserisci('articoli_giornali', [
            'giornale_id' => $giornaleId,
            'giornalista_id' => $account['tipo'] === 'giornalista' ? Account::profiloId($account) : null,
            'titolo' => $titolo,
            'slug' => Http::slug($titolo) . '-' . bin2hex(random_bytes(3)),
            'sezione' => Http::testo('sezione', 120, false),
            'sommario' => Http::testo('sommario', 500, false),
            'corpo' => Http::testo('corpo', 100000),
            'stato' => $stato,
            'pubblicato_il' => $pubblica ? gmdate('Y-m-d H:i:s') : null,
        ]);
        return ['id' => $id, 'stato' => $stato];
    }

    public static function pubblicaArticoloGiornale(int $articoloId): array
    {
        $account = Account::richiedi('editore');
        $giornaleId = (int) Database::valore('SELECT giornale_id FROM articoli_giornali WHERE id = ?', [$articoloId]);
        self::giornaleMio($account, $giornaleId, true);
        Database::esegui(
            "UPDATE articoli_giornali SET stato = 'pubblicato', pubblicato_il = COALESCE(pubblicato_il, NOW()) WHERE id = ?",
            [$articoloId]
        );
        return ['id' => $articoloId, 'stato' => 'pubblicato'];
    }

    // ============================================================
    // Politici e articoli dei politici
    // ============================================================

    public static function politici(): array
    {
        return ['politici' => Database::righe(
            "SELECT p.id, p.slug, g.nome, g.cognome, p.partito, p.carica, p.territorio, p.biografia
             FROM politici p JOIN generalita g ON g.id = p.generalita_id
             WHERE g.stato = 'attivo' ORDER BY g.cognome, g.nome"
        )];
    }

    public static function articoliPolitico(int $politicoId): array
    {
        return ['articoli' => Database::righe(
            "SELECT id, titolo, slug, categoria, sommario, corpo, pubblicato_il FROM articoli_politici
             WHERE politico_id = ? AND stato = 'pubblicato' ORDER BY pubblicato_il DESC LIMIT 50",
            [$politicoId]
        )];
    }

    public static function creaArticoloPolitico(): array
    {
        $politico = Account::richiedi('politico');
        $titolo = (string) Http::testo('titolo', 220);
        $pubblica = (Http::corpo()['pubblica'] ?? false) === true;
        $id = Database::inserisci('articoli_politici', [
            'politico_id' => Account::profiloId($politico),
            'titolo' => $titolo,
            'slug' => Http::slug($titolo) . '-' . bin2hex(random_bytes(3)),
            'categoria' => Http::testo('categoria', 120, false),
            'sommario' => Http::testo('sommario', 500, false),
            'corpo' => Http::testo('corpo', 100000),
            'stato' => $pubblica ? 'pubblicato' : 'bozza',
            'pubblicato_il' => $pubblica ? gmdate('Y-m-d H:i:s') : null,
        ]);
        return ['id' => $id, 'stato' => $pubblica ? 'pubblicato' : 'bozza'];
    }

    // ============================================================
    // Eventi
    // ============================================================

    public static function eventi(): array
    {
        $eventi = Database::righe(
            "SELECT id, titolo, descrizione, luogo, inizio, fine FROM eventi
             WHERE pubblico = 1 AND stato = 'programmato' AND inizio > NOW() - INTERVAL 1 DAY
             ORDER BY inizio LIMIT 100"
        );
        if ($eventi === []) {
            return ['eventi' => []];
        }
        $ids = array_column($eventi, 'id');
        $organizzatori = Database::righe(
            "SELECT o.evento_id, o.politico_id, o.giornale_id,
                    COALESCE(j.nome, CONCAT(g.nome, ' ', g.cognome)) AS nome
             FROM eventi_organizzatori o
             LEFT JOIN giornali j ON j.id = o.giornale_id
             LEFT JOIN politici p ON p.id = o.politico_id
             LEFT JOIN generalita g ON g.id = p.generalita_id
             WHERE o.confermato = 1 AND o.evento_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')',
            $ids
        );
        foreach ($eventi as &$evento) {
            $evento['organizzatori'] = array_values(array_filter($organizzatori, static fn ($o) => $o['evento_id'] === $evento['id']));
        }
        return ['eventi' => $eventi];
    }

    /**
     * Crea un evento. Lo organizza un politico, un giornale o entrambi:
     * chi lo crea è organizzatore confermato; gli altri organizzatori
     * indicati restano "da confermare" finché non accettano.
     */
    public static function creaEvento(): array
    {
        $account = Account::richiedi('politico', 'editore');
        $politici = Http::elencoId('politici_organizzatori');
        $giornali = Http::elencoId('giornali_organizzatori');
        $inizio = (string) Http::dataOra('inizio');
        $fine = Http::dataOra('fine', false);
        if ($fine !== null && $fine < $inizio) {
            throw new ErroreApi('La fine dell\'evento non può precedere l\'inizio.', 422);
        }

        $mioPolitico = $account['tipo'] === 'politico' ? Account::profiloId($account) : 0;
        $mieiGiornali = $account['tipo'] === 'editore'
            ? array_map('intval', array_column(Database::righe('SELECT id FROM giornali WHERE editore_id = ?', [Account::profiloId($account)]), 'id'))
            : [];
        if ($mioPolitico !== 0 && !in_array($mioPolitico, $politici, true)) {
            $politici[] = $mioPolitico;
        }
        if ($account['tipo'] === 'editore' && array_intersect($giornali, $mieiGiornali) === []) {
            throw new ErroreApi('Indica in giornali_organizzatori almeno un tuo giornale.', 422);
        }

        $id = Database::transazione(static function () use ($account, $politici, $giornali, $inizio, $fine, $mioPolitico, $mieiGiornali) {
            $id = Database::inserisci('eventi', [
                'titolo' => Http::testo('titolo', 220),
                'descrizione' => Http::testo('descrizione', 5000, false),
                'luogo' => Http::testo('luogo', 220, false),
                'inizio' => $inizio,
                'fine' => $fine,
                'pubblico' => (Http::corpo()['pubblico'] ?? true) === false ? 0 : 1,
                'creato_da' => $account['id'],
            ]);
            foreach ($politici as $politico) {
                Database::inserisci('eventi_organizzatori', ['evento_id' => $id, 'politico_id' => $politico, 'confermato' => (int) ($politico === $mioPolitico)]);
            }
            foreach ($giornali as $giornale) {
                Database::inserisci('eventi_organizzatori', ['evento_id' => $id, 'giornale_id' => $giornale, 'confermato' => (int) in_array($giornale, $mieiGiornali, true)]);
            }
            return $id;
        });
        return ['id' => $id];
    }

    /** Righe di eventi_organizzatori che appartengono all'account (politico stesso o giornali dell'editore). */
    private static function mieOrganizzazioni(array $account, int $eventoId): array
    {
        if ($account['tipo'] === 'politico') {
            return Database::righe('SELECT * FROM eventi_organizzatori WHERE evento_id = ? AND politico_id = ?', [$eventoId, Account::profiloId($account)]);
        }
        return Database::righe(
            'SELECT o.* FROM eventi_organizzatori o JOIN giornali j ON j.id = o.giornale_id WHERE o.evento_id = ? AND j.editore_id = ?',
            [$eventoId, Account::profiloId($account)]
        );
    }

    public static function confermaOrganizzazione(int $eventoId): array
    {
        $account = Account::richiedi('politico', 'editore');
        $righe = self::mieOrganizzazioni($account, $eventoId);
        if ($righe === []) {
            throw new ErroreApi('Non sei tra gli organizzatori di questo evento.', 403);
        }
        foreach ($righe as $riga) {
            Database::esegui('UPDATE eventi_organizzatori SET confermato = 1 WHERE id = ?', [$riga['id']]);
        }
        return ['ok' => true];
    }

    /** Annulla un evento: lo può fare chi l'ha creato o un organizzatore confermato. */
    public static function annullaEvento(int $eventoId): array
    {
        $account = Account::richiedi();
        $evento = self::eventoAttivo($eventoId);
        $organizza = in_array($account['tipo'], ['politico', 'editore'], true)
            && array_filter(self::mieOrganizzazioni($account, $eventoId), static fn ($o) => (int) $o['confermato'] === 1) !== [];
        if ((int) $evento['creato_da'] !== (int) $account['id'] && !$organizza) {
            throw new ErroreApi('Solo gli organizzatori possono annullare l\'evento.', 403);
        }
        Database::esegui("UPDATE eventi SET stato = 'annullato' WHERE id = ?", [$eventoId]);
        return ['id' => $eventoId, 'stato' => 'annullato'];
    }

    private static function eventoAttivo(int $eventoId): array
    {
        $evento = Database::riga("SELECT * FROM eventi WHERE id = ? AND stato = 'programmato'", [$eventoId]);
        if ($evento === null) {
            throw new ErroreApi('Evento non trovato.', 404);
        }
        return $evento;
    }

    /** Invita politici e giornalisti: lo può fare solo un organizzatore confermato. */
    public static function invita(int $eventoId): array
    {
        $account = Account::richiedi('politico', 'editore');
        $evento = self::eventoAttivo($eventoId);
        if (array_filter(self::mieOrganizzazioni($account, $eventoId), static fn ($o) => (int) $o['confermato'] === 1) === []) {
            throw new ErroreApi('Solo un organizzatore confermato può invitare.', 403);
        }
        $invitati = 0;
        foreach (['politici' => ['politico_id', 'politici'], 'giornalisti' => ['giornalista_id', 'giornalisti']] as $campo => [$colonna, $tabella]) {
            foreach (Http::elencoId($campo) as $id) {
                $persona = Database::riga(
                    "SELECT g.nome, g.email_cifrata FROM `$tabella` t JOIN generalita g ON g.id = t.generalita_id WHERE t.id = ? AND g.stato = 'attivo'",
                    [$id]
                );
                if ($persona === null) {
                    continue;
                }
                $nuovo = Database::esegui("INSERT IGNORE INTO eventi_invitati (evento_id, `$colonna`) VALUES (?, ?)", [$eventoId, $id])->rowCount();
                if ($nuovo === 1) {
                    $invitati++;
                    Mailer::invitoEvento(Cifratura::decifra($persona['email_cifrata']), $persona['nome'], $evento);
                }
            }
        }
        return ['invitati' => $invitati];
    }

    /** Colonna di eventi_invitati / eventi_partecipanti per il tipo di account. */
    private static function colonnaPersona(array $account): string
    {
        return ['politico' => 'politico_id', 'giornalista' => 'giornalista_id', 'user' => 'user_id'][$account['tipo']];
    }

    public static function rispondiInvito(int $eventoId): array
    {
        $account = Account::richiedi('politico', 'giornalista');
        self::eventoAttivo($eventoId);
        $colonna = self::colonnaPersona($account);
        $accetta = (Http::corpo()['accetta'] ?? null) === true;
        $aggiornati = Database::esegui(
            "UPDATE eventi_invitati SET stato = ?, risposto_il = NOW() WHERE evento_id = ? AND `$colonna` = ?",
            [$accetta ? 'accettato' : 'rifiutato', $eventoId, Account::profiloId($account)]
        )->rowCount();
        if ($aggiornati === 0 && Database::valore("SELECT 1 FROM eventi_invitati WHERE evento_id = ? AND `$colonna` = ?", [$eventoId, Account::profiloId($account)]) === null) {
            throw new ErroreApi('Non risulti invitato a questo evento.', 403);
        }
        if ($accetta) {
            Database::esegui("INSERT IGNORE INTO eventi_partecipanti (evento_id, `$colonna`) VALUES (?, ?)", [$eventoId, Account::profiloId($account)]);
        } else {
            Database::esegui("DELETE FROM eventi_partecipanti WHERE evento_id = ? AND `$colonna` = ?", [$eventoId, Account::profiloId($account)]);
        }
        return ['stato' => $accetta ? 'accettato' : 'rifiutato'];
    }

    /** Partecipa a un evento: politici, giornalisti o user. Gli eventi privati solo su invito. */
    public static function partecipa(int $eventoId): array
    {
        $account = Account::richiedi('politico', 'giornalista', 'user');
        $evento = self::eventoAttivo($eventoId);
        $colonna = self::colonnaPersona($account);
        $invitato = $account['tipo'] !== 'user'
            && Database::valore("SELECT 1 FROM eventi_invitati WHERE evento_id = ? AND `$colonna` = ?", [$eventoId, Account::profiloId($account)]) !== null;
        if ((int) $evento['pubblico'] !== 1 && !$invitato) {
            throw new ErroreApi('Evento riservato agli invitati.', 403);
        }
        Database::esegui("INSERT IGNORE INTO eventi_partecipanti (evento_id, `$colonna`) VALUES (?, ?)", [$eventoId, Account::profiloId($account)]);
        if ($invitato) {
            Database::esegui("UPDATE eventi_invitati SET stato = 'accettato', risposto_il = NOW() WHERE evento_id = ? AND `$colonna` = ?", [$eventoId, Account::profiloId($account)]);
        }
        return ['ok' => true];
    }

    /** I miei inviti e le mie partecipazioni. */
    public static function mieiEventi(): array
    {
        $account = Account::richiedi('politico', 'giornalista', 'user');
        $colonna = self::colonnaPersona($account);
        $id = Account::profiloId($account);
        return [
            'inviti' => $account['tipo'] === 'user' ? [] : Database::righe(
                "SELECT e.id, e.titolo, e.luogo, e.inizio, i.stato FROM eventi_invitati i JOIN eventi e ON e.id = i.evento_id WHERE i.`$colonna` = ? ORDER BY e.inizio",
                [$id]
            ),
            'partecipazioni' => Database::righe(
                "SELECT e.id, e.titolo, e.luogo, e.inizio FROM eventi_partecipanti p JOIN eventi e ON e.id = p.evento_id WHERE p.`$colonna` = ? ORDER BY e.inizio",
                [$id]
            ),
        ];
    }

    // ============================================================
    // Iscrizioni (gratuite) a giornali e politici
    // ============================================================

    /** Legge dal corpo il destinatario: esattamente uno tra giornale_id e politico_id, esistente e attivo. */
    private static function destinatario(): array
    {
        $giornale = Http::intero('giornale_id', false);
        $politico = Http::intero('politico_id', false);
        if (($giornale === null) === ($politico === null)) {
            throw new ErroreApi('Indica giornale_id oppure politico_id (uno solo).', 422);
        }
        $nome = $giornale !== null
            ? Database::valore("SELECT nome FROM giornali WHERE id = ? AND stato = 'attivo'", [$giornale])
            : Database::valore("SELECT CONCAT(g.nome, ' ', g.cognome) FROM politici p JOIN generalita g ON g.id = p.generalita_id WHERE p.id = ? AND g.stato = 'attivo'", [$politico]);
        if ($nome === null) {
            throw new ErroreApi('Destinatario non trovato.', 404);
        }
        return ['giornale_id' => $giornale, 'politico_id' => $politico, 'nome' => (string) $nome];
    }

    public static function iscriviti(): array
    {
        $user = Account::richiedi('user');
        $a = self::destinatario();
        Database::esegui(
            'INSERT IGNORE INTO iscrizioni (user_id, giornale_id, politico_id, newsletter) VALUES (?, ?, ?, ?)',
            [Account::profiloId($user), $a['giornale_id'], $a['politico_id'], (Http::corpo()['newsletter'] ?? true) === false ? 0 : 1]
        );
        return ['ok' => true];
    }

    public static function annullaIscrizione(): array
    {
        $user = Account::richiedi('user');
        $giornale = Http::intero('giornale_id', false);
        $politico = Http::intero('politico_id', false);
        Database::esegui(
            'DELETE FROM iscrizioni WHERE user_id = ? AND (giornale_id <=> ?) AND (politico_id <=> ?)',
            [Account::profiloId($user), $giornale, $politico]
        );
        return ['ok' => true];
    }

    public static function iscrizioni(): array
    {
        $user = Account::richiedi('user');
        return ['iscrizioni' => Database::righe(
            'SELECT id, giornale_id, politico_id, newsletter, creato_il FROM iscrizioni WHERE user_id = ? ORDER BY creato_il DESC',
            [Account::profiloId($user)]
        )];
    }

    // ============================================================
    // Donazioni e abbonamenti (Stripe Checkout)
    // ============================================================

    public static function dona(): array
    {
        Account::limita('donazione', 10, 60);
        Account::segna('donazione');
        $account = Account::corrente();          // si può donare anche senza account
        $a = self::destinatario();
        $importo = Http::corpo()['importo_centesimi'] ?? null;
        $minimo = (int) Config::get('DONAZIONE_MIN_CENT', '100');
        $massimo = (int) Config::get('DONAZIONE_MAX_CENT', '500000');
        // L'importo arriva in centesimi interi: niente virgole, niente arrotondamenti.
        if (!is_int($importo) || $importo < $minimo || $importo > $massimo) {
            throw new ErroreApi("importo_centesimi deve essere un intero tra $minimo e $massimo.", 422);
        }

        $id = Database::inserisci('donazioni', [
            'user_id' => $account !== null && $account['tipo'] === 'user' ? Account::profiloId($account) : null,
            'giornale_id' => $a['giornale_id'],
            'politico_id' => $a['politico_id'],
            'importo_centesimi' => $importo,
            'messaggio' => Http::testo('messaggio', 500, false),
        ]);
        try {
            $sessione = Stripe::checkoutDonazione($id, $importo, $a['nome'], $account !== null ? Account::email($account) : null);
        } catch (Throwable $e) {
            Database::esegui("UPDATE donazioni SET stato = 'fallita' WHERE id = ?", [$id]);
            throw $e;
        }
        Database::esegui('UPDATE donazioni SET stripe_checkout_session_id = ? WHERE id = ?', [$sessione['id'], $id]);
        // Il front end deve solo reindirizzare l'utente a questo indirizzo di Stripe.
        return ['donazione_id' => $id, 'url_pagamento' => $sessione['url']];
    }

    public static function abbonati(): array
    {
        $user = Account::richiedi('user');
        $giornaleId = (int) Http::intero('giornale_id');
        $piano = Http::scelta('piano', ['mensile', 'annuale']);
        $giornale = Database::riga("SELECT * FROM giornali WHERE id = ? AND stato = 'attivo'", [$giornaleId]);
        $prezzo = $giornale["prezzo_{$piano}_cent"] ?? null;
        if ($prezzo === null) {
            throw new ErroreApi('Questo giornale non offre il piano richiesto.', 404);
        }
        if (Database::valore("SELECT 1 FROM abbonamenti WHERE user_id = ? AND giornale_id = ? AND stato IN ('attivo','insoluto')", [Account::profiloId($user), $giornaleId]) !== null) {
            throw new ErroreApi('Hai già un abbonamento attivo a questo giornale.', 409);
        }
        $id = Database::inserisci('abbonamenti', [
            'user_id' => Account::profiloId($user),
            'giornale_id' => $giornaleId,
            'piano' => $piano,
            'importo_centesimi' => $prezzo,     // il prezzo viene dal database, non dal browser
        ]);
        try {
            $sessione = Stripe::checkoutAbbonamento($id, (int) $prezzo, $piano, $giornale['nome'], Account::email($user));
        } catch (Throwable $e) {
            Database::esegui("UPDATE abbonamenti SET stato = 'annullato' WHERE id = ?", [$id]);
            throw $e;
        }
        Database::esegui('UPDATE abbonamenti SET stripe_checkout_session_id = ? WHERE id = ?', [$sessione['id'], $id]);
        return ['abbonamento_id' => $id, 'url_pagamento' => $sessione['url']];
    }

    public static function abbonamenti(): array
    {
        $user = Account::richiedi('user');
        return ['abbonamenti' => Database::righe(
            'SELECT a.id, a.giornale_id, g.nome AS giornale, a.piano, a.importo_centesimi, a.stato, a.inizio_il, a.scade_il
             FROM abbonamenti a JOIN giornali g ON g.id = a.giornale_id WHERE a.user_id = ? ORDER BY a.creato_il DESC',
            [Account::profiloId($user)]
        )];
    }

    /**
     * Webhook di Stripe: è l'UNICO punto in cui un pagamento diventa "riuscito".
     * Niente sessione né CSRF: l'autenticità è garantita dalla firma.
     */
    public static function webhookStripe(): array
    {
        $evento = Stripe::eventoWebhook(Http::corpoGrezzo(), (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''));
        $ricevuta = null;

        Database::transazione(static function () use ($evento, &$ricevuta) {
            // Stripe può rimandare lo stesso evento: lo si elabora una volta sola.
            if (Database::esegui('INSERT IGNORE INTO stripe_eventi (id, tipo) VALUES (?, ?)', [$evento['id'], $evento['type']])->rowCount() === 0) {
                return;
            }
            $o = $evento['data']['object'];
            $tipo = $o['metadata']['tipo'] ?? '';
            $id = (int) ($o['metadata']['id'] ?? 0);

            switch ($evento['type']) {
                case 'checkout.session.completed':
                case 'checkout.session.async_payment_succeeded':
                    if ($tipo === 'donazione' && ($o['payment_status'] ?? '') === 'paid') {
                        // Si controllano anche sessione e importo: devono essere quelli creati da noi.
                        $fatto = Database::esegui(
                            "UPDATE donazioni SET stato = 'pagata', pagata_il = NOW(), stripe_payment_intent_id = ?
                             WHERE id = ? AND stripe_checkout_session_id = ? AND importo_centesimi = ? AND stato = 'in_attesa'",
                            [$o['payment_intent'] ?? null, $id, $o['id'], (int) ($o['amount_total'] ?? 0)]
                        )->rowCount();
                        $ricevuta = $fatto === 1 ? $id : null;
                    } elseif ($tipo === 'abbonamento') {
                        Database::esegui(
                            "UPDATE abbonamenti SET stato = 'attivo', inizio_il = NOW(), stripe_customer_id = ?, stripe_subscription_id = ?
                             WHERE id = ? AND stripe_checkout_session_id = ? AND stato = 'in_attesa'",
                            [$o['customer'] ?? null, $o['subscription'] ?? null, $id, $o['id']]
                        );
                    }
                    break;

                case 'checkout.session.expired':
                case 'checkout.session.async_payment_failed':
                    $fallita = $evento['type'] === 'checkout.session.expired' ? 'scaduta' : 'fallita';
                    Database::esegui("UPDATE donazioni SET stato = ? WHERE stripe_checkout_session_id = ? AND stato = 'in_attesa'", [$fallita, $o['id']]);
                    Database::esegui("UPDATE abbonamenti SET stato = 'annullato' WHERE stripe_checkout_session_id = ? AND stato = 'in_attesa'", [$o['id']]);
                    break;

                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $stato = match ($o['status'] ?? '') {
                        'active', 'trialing' => 'attivo',
                        'past_due', 'unpaid' => 'insoluto',
                        'canceled' => 'annullato',
                        default => 'scaduto',
                    };
                    $fine = $o['current_period_end'] ?? ($o['items']['data'][0]['current_period_end'] ?? null);
                    Database::esegui(
                        'UPDATE abbonamenti SET stato = ?, scade_il = COALESCE(?, scade_il) WHERE stripe_subscription_id = ?',
                        [$stato, is_int($fine) ? gmdate('Y-m-d H:i:s', $fine) : null, $o['id']]
                    );
                    break;

                case 'charge.refunded':
                    if (($o['refunded'] ?? false) === true && isset($o['payment_intent'])) {
                        Database::esegui("UPDATE donazioni SET stato = 'rimborsata' WHERE stripe_payment_intent_id = ?", [$o['payment_intent']]);
                    }
                    break;
            }
        });

        if ($ricevuta !== null) {
            $d = Database::riga(
                "SELECT d.importo_centesimi, g.nome, g.email_cifrata,
                        COALESCE(j.nome, CONCAT(gp.nome, ' ', gp.cognome)) AS destinatario
                 FROM donazioni d JOIN `user` u ON u.id = d.user_id JOIN generalita g ON g.id = u.generalita_id
                 LEFT JOIN giornali j ON j.id = d.giornale_id
                 LEFT JOIN politici p ON p.id = d.politico_id LEFT JOIN generalita gp ON gp.id = p.generalita_id
                 WHERE d.id = ?",
                [$ricevuta]
            );
            if ($d !== null) {
                Mailer::ricevutaDonazione(Cifratura::decifra($d['email_cifrata']), $d['nome'], (int) $d['importo_centesimi'], $d['destinatario']);
            }
        }
        return ['ricevuto' => true];
    }

    // ============================================================
    // Ticket di assistenza
    // ============================================================

    public static function creaTicket(): array
    {
        $account = Account::richiedi();
        Account::limita('ticket', 20, 60);
        Account::segna('ticket');
        $giornaleId = Http::intero('giornale_id', false);
        if ($giornaleId !== null) {
            self::giornaleMio($account, $giornaleId);
        }
        $codice = 'T-' . strtoupper(bin2hex(random_bytes(5)));
        $oggetto = (string) Http::testo('oggetto', 200);
        $id = Database::inserisci('ticket', [
            'codice' => $codice,
            'aperto_da' => $account['id'],
            'giornale_id' => $giornaleId,
            'oggetto' => $oggetto,
            'descrizione' => Http::testo('descrizione', 10000),
            'priorita' => Http::scelta('priorita', ['bassa', 'normale', 'alta', 'urgente'], 'normale'),
        ]);
        Mailer::ticketAperto(Account::email($account), $account['nome'], $codice, $oggetto);
        return ['id' => $id, 'codice' => $codice];
    }

    public static function ticket(): array
    {
        $account = Account::richiedi();
        $tecnico = $account['tipo'] === 'tecnico';
        return ['ticket' => Database::righe(
            'SELECT id, codice, oggetto, priorita, stato, giornale_id, tecnico_id, creato_il, aggiornato_il FROM ticket '
            . ($tecnico ? '' : 'WHERE aperto_da = ? ') . 'ORDER BY aggiornato_il DESC LIMIT 200',
            $tecnico ? [] : [$account['id']]
        )];
    }

    /** Il ticket, solo se è di chi lo chiede oppure chi lo chiede è un tecnico. */
    private static function ticketVisibile(array $account, int $ticketId): array
    {
        $ticket = Database::riga('SELECT * FROM ticket WHERE id = ?', [$ticketId]);
        if ($ticket === null || ($account['tipo'] !== 'tecnico' && (int) $ticket['aperto_da'] !== (int) $account['id'])) {
            throw new ErroreApi('Ticket non trovato.', 404);
        }
        return $ticket;
    }

    public static function dettaglioTicket(int $ticketId): array
    {
        $ticket = self::ticketVisibile(Account::richiedi(), $ticketId);
        $ticket['messaggi'] = Database::righe(
            "SELECT m.id, m.testo, m.creato_il, g.tipo AS ruolo, CONCAT(g.nome, ' ', COALESCE(g.cognome, '')) AS autore
             FROM ticket_messaggi m JOIN generalita g ON g.id = m.autore_id WHERE m.ticket_id = ? ORDER BY m.creato_il, m.id",
            [$ticketId]
        );
        return ['ticket' => $ticket];
    }

    public static function rispondiTicket(int $ticketId): array
    {
        $account = Account::richiedi();
        $ticket = self::ticketVisibile($account, $ticketId);
        if ($ticket['stato'] === 'chiuso') {
            throw new ErroreApi('Il ticket è chiuso.', 409);
        }
        $id = Database::inserisci('ticket_messaggi', [
            'ticket_id' => $ticketId,
            'autore_id' => $account['id'],
            'testo' => Http::testo('testo', 10000),
        ]);
        Database::esegui('UPDATE ticket SET aggiornato_il = NOW() WHERE id = ?', [$ticketId]);
        return ['id' => $id];
    }

    /** Solo tecnici: prende in carico e/o cambia stato. */
    public static function gestisciTicket(int $ticketId): array
    {
        $tecnico = Account::richiedi('tecnico');
        self::ticketVisibile($tecnico, $ticketId);
        $stato = Http::scelta('stato', ['aperto', 'in_lavorazione', 'in_attesa_cliente', 'risolto', 'chiuso'], 'in_lavorazione');
        Database::esegui(
            "UPDATE ticket SET stato = ?, tecnico_id = ?, chiuso_il = IF(? = 'chiuso', NOW(), NULL) WHERE id = ?",
            [$stato, Account::profiloId($tecnico), $stato, $ticketId]
        );
        return ['id' => $ticketId, 'stato' => $stato];
    }

    // ============================================================
    // Tecnici: approvazione dei profili (editori, politici, giornalisti)
    // ============================================================

    public static function accountInAttesa(): array
    {
        Account::richiedi('tecnico');
        return ['account' => Database::righe(
            "SELECT id, tipo, nome, cognome, creato_il FROM generalita WHERE stato = 'in_approvazione' ORDER BY creato_il"
        )];
    }

    public static function statoAccount(int $generalitaId): array
    {
        $tecnico = Account::richiedi('tecnico');
        $azione = Http::scelta('azione', ['approva', 'sospendi']);
        $account = Database::riga("SELECT * FROM generalita WHERE id = ? AND tipo NOT IN ('giornale','tecnico')", [$generalitaId]);
        if ($account === null || (int) $account['id'] === (int) $tecnico['id']) {
            throw new ErroreApi('Account non trovato.', 404);
        }
        if ($azione === 'approva') {
            if ($account['stato'] !== 'in_approvazione') {
                throw new ErroreApi('L\'account non è in attesa di approvazione.', 409);
            }
            Database::esegui("UPDATE generalita SET stato = 'attivo' WHERE id = ?", [$generalitaId]);
            Mailer::approvato(Cifratura::decifra($account['email_cifrata']), $account['nome']);
        } else {
            Database::esegui("UPDATE generalita SET stato = 'sospeso' WHERE id = ?", [$generalitaId]);
        }
        return ['id' => $generalitaId, 'stato' => $azione === 'approva' ? 'attivo' : 'sospeso'];
    }
}
