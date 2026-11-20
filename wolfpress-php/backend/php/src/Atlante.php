<?php
/**
 * ATLANTE CIVICO: clienti (candidati, partiti, liste, comitati), i loro siti,
 * le offerte, i modelli documentali MASTER e i fascicoli con documenti, firme
 * e verifiche.
 *
 * Regole applicate QUI, perché lo schema da solo non le garantisce:
 *  - ogni lettura e ogni modifica controlla chi è collegato e per QUALE cliente:
 *    lo staff (account tecnico) gestisce tutto; un referente vede solo i clienti
 *    per cui ha un mandato VERIFICATO e NON revocato, entro i limiti del suo ruolo;
 *  - essere candidato o iscritto a un partito non dà accesso ai dati di altri;
 *  - codice fiscale, partita IVA, PEC, sede e campi riservati sono cifrati;
 *  - i file sono in un archivio privato e cifrato (classe Archivio);
 *  - ogni operazione, riuscita o negata, finisce nel registro "audit_atlante"
 *    con soli metadati (mai dati personali o contenuti).
 *
 * Nulla di ciò che si registra qui è una certificazione di conformità:
 * verifiche, approvazioni e firme valgono per le prove reali che le sostengono.
 */
declare(strict_types=1);

final class Atlante
{
    public const TIPI_CLIENTE = ['candidato' => 'Candidato', 'partito' => 'Partito', 'lista_civica' => 'Lista civica', 'comitato' => 'Comitato', 'agenzia' => 'Agenzia', 'altro' => 'Altro'];
    public const STATI_CLIENTE = ['bozza' => 'Bozza', 'da_verificare' => 'Da verificare', 'verificato' => 'Verificato', 'sospeso' => 'Sospeso', 'cessato' => 'Cessato'];
    public const RUOLI = ['firmatario' => 'Firmatario', 'comunicazione' => 'Comunicazione', 'privacy' => 'Privacy', 'ia' => 'Intelligenza artificiale', 'tecnico' => 'Tecnico', 'amministrativo' => 'Amministrativo', 'altro' => 'Altro'];
    public const TIPI_SITO = ['comunicazione_politica' => 'Comunicazione politica', 'testata' => 'Testata', 'altro' => 'Altro'];
    public const STATI_SITO = ['bozza' => 'Bozza', 'collaudo' => 'Collaudo', 'attivo' => 'Attivo', 'sospeso' => 'Sospeso', 'cessato' => 'Cessato'];
    public const FUNZIONI = ['cms' => 'Gestione contenuti', 'moduli' => 'Moduli di contatto', 'newsletter' => 'Newsletter', 'donazioni' => 'Donazioni', 'tesseramento' => 'Tesseramento', 'analytics' => 'Statistiche', 'pubblicita_politica' => 'Pubblicità politica', 'targeting' => 'Targeting', 'ia' => 'Intelligenza artificiale', 'altro' => 'Altro'];
    public const STATI_FUNZIONE = ['non_attiva' => 'Non attiva', 'richiesta' => 'Richiesta', 'da_verificare' => 'Da verificare', 'autorizzata' => 'Autorizzata', 'attiva' => 'Attiva', 'sospesa' => 'Sospesa'];
    public const PERIODICITA = ['una_tantum' => 'Una tantum', 'mensile' => 'Mensile', 'annuale' => 'Annuale'];
    public const STATI_OFFERTA = ['bozza' => 'Bozza', 'inviata' => 'Inviata', 'accettata' => 'Accettata', 'rifiutata' => 'Rifiutata', 'scaduta' => 'Scaduta'];
    public const CATEGORIE = ['contratto' => 'Contratto', 'privacy' => 'Privacy', 'ai_act' => 'AI Act', 'pubblicita_politica' => 'Pubblicità politica', 'onboarding' => 'Avvio', 'commerciale' => 'Commerciale', 'altro' => 'Altro'];
    public const DESTINATARI = ['tutti' => 'Tutti'] + self::TIPI_CLIENTE;
    public const STATI_VERSIONE = ['bozza' => 'Bozza', 'da_verificare' => 'Da verificare', 'approvata' => 'Approvata', 'ritirata' => 'Ritirata'];
    public const STATI_FASCICOLO = ['aperto' => 'Aperto', 'in_verifica' => 'In verifica', 'completo' => 'Completo', 'archiviato' => 'Archiviato'];
    public const STATI_DOCUMENTO = ['bozza' => 'Bozza', 'da_verificare' => 'Da verificare', 'pronto_firma' => 'Pronto per la firma', 'firmato' => 'Firmato', 'annullato' => 'Annullato', 'scaduto' => 'Scaduto'];
    public const PARTI = ['cliente' => 'Cliente', 'fornitore' => 'Fornitore', 'altro' => 'Altro'];
    public const OGGETTI_VERIFICA = ['gdpr_ruoli' => 'Ruoli GDPR', 'dpa' => 'Accordo sul trattamento (DPA)', 'dpia' => 'Valutazione d\'impatto (DPIA)', 'cookie' => 'Cookie', 'sicurezza' => 'Sicurezza', 'ai_act' => 'AI Act', 'pubblicita_politica' => 'Pubblicità politica', 'revisione_legale' => 'Revisione legale', 'altro' => 'Altro'];
    public const ESITI = ['da_fare' => 'Da fare', 'integrazioni' => 'Integrazioni richieste', 'conclusa' => 'Conclusa', 'non_applicabile' => 'Non applicabile'];

    /** Ruoli di referente che possono vedere i dati fiscali e i documenti del cliente. */
    private const RUOLI_DOCUMENTI = ['firmatario', 'amministrativo', 'privacy'];

    // ============================================================
    // Chi è collegato e che cosa può fare
    // ============================================================

    private static function io(): array
    {
        return Account::richiedi();
    }

    /** Lo staff del fornitore (account tecnico) gestisce tutti i clienti. */
    public static function staff(): bool
    {
        return (Account::corrente()['tipo'] ?? '') === 'tecnico';
    }

    /** Ruoli di referente dell'account collegato sul cliente: solo mandati verificati e non revocati. */
    public static function ruoli(int $clienteId): array
    {
        return array_column(Database::righe(
            'SELECT ruolo FROM referenti_clienti_atlante
             WHERE cliente_id = ? AND generalita_id = ? AND verificato_il IS NOT NULL AND revocato_il IS NULL',
            [$clienteId, self::io()['id']]
        ), 'ruolo');
    }

    /** Scrive una riga del registro: solo metadati, nessun dato personale. */
    private static function registra(int $clienteId, string $oggetto, int $oggettoId, string $azione, string $esito = 'riuscita'): void
    {
        Database::inserisci('audit_atlante', [
            'cliente_id' => $clienteId,
            'attore_id' => Account::corrente()['id'] ?? null,
            'oggetto' => $oggetto,
            'oggetto_id' => $oggettoId,
            'azione' => mb_substr($azione, 0, 80),
            'esito' => $esito,
        ]);
    }

    /**
     * Ferma l'operazione se chi è collegato non può agire su questo cliente.
     * $ruoli = null: solo staff. $ruoli = []: staff o qualunque referente. Altrimenti staff o uno dei ruoli elencati.
     * Chi non ha titolo riceve "non trovato": non si rivela nemmeno che il cliente esiste.
     */
    private static function esigi(int $clienteId, ?array $ruoli, string $oggetto, int $oggettoId, string $azione): void
    {
        if (self::staff()) {
            return;
        }
        $miei = $ruoli === null ? [] : self::ruoli($clienteId);
        if ($ruoli !== null && $miei !== [] && ($ruoli === [] || array_intersect($ruoli, $miei) !== [])) {
            return;
        }
        if (Database::valore('SELECT 1 FROM clienti_atlante WHERE id = ?', [$clienteId]) !== null) {
            self::registra($clienteId, $oggetto, $oggettoId, $azione, 'negata');
        }
        throw new ErroreApi('Elemento non trovato.', 404);
    }

    private static function scelta(string $valore, array $ammessi, string $campo): string
    {
        if (!isset($ammessi[$valore])) {
            throw new ErroreApi("Valore non ammesso: $campo.", 422);
        }
        return $valore;
    }

    private static function testo(string $valore, int $massimo, string $campo, bool $obbligatorio = true): ?string
    {
        $valore = trim($valore);
        if ($valore === '') {
            if ($obbligatorio) {
                throw new ErroreApi("Campo obbligatorio: $campo.", 422);
            }
            return null;
        }
        if (mb_strlen($valore) > $massimo) {
            throw new ErroreApi("Campo troppo lungo: $campo (massimo $massimo caratteri).", 422);
        }
        return $valore;
    }

    private static function riga(string $tabella, int $id): array
    {
        $riga = Database::riga("SELECT * FROM `$tabella` WHERE id = ?", [$id]);
        if ($riga === null) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        return $riga;
    }

    // ============================================================
    // Clienti
    // ============================================================

    /** Clienti visibili a chi è collegato: tutti per lo staff, i propri per un referente. */
    public static function clienti(): array
    {
        $io = self::io();
        return self::staff()
            ? Database::righe('SELECT id, tipo, denominazione, stato, creato_il FROM clienti_atlante ORDER BY denominazione')
            : Database::righe(
                'SELECT DISTINCT c.id, c.tipo, c.denominazione, c.stato, c.creato_il FROM clienti_atlante c
                 JOIN referenti_clienti_atlante r ON r.cliente_id = c.id
                 WHERE r.generalita_id = ? AND r.verificato_il IS NOT NULL AND r.revocato_il IS NULL ORDER BY c.denominazione',
                [$io['id']]
            );
    }

    /** Scheda del cliente. I dati fiscali in chiaro solo a staff e ai referenti che ne hanno titolo. */
    public static function cliente(int $id): array
    {
        self::esigi($id, [], 'cliente', $id, 'lettura');
        $c = self::riga('clienti_atlante', $id);
        $riservati = self::staff() || array_intersect(self::RUOLI_DOCUMENTI, self::ruoli($id)) !== [];
        foreach (['codice_fiscale' => 'codice_fiscale_cifrato', 'partita_iva' => 'partita_iva_cifrata', 'pec' => 'pec_cifrata', 'sede' => 'sede_cifrata'] as $campo => $colonna) {
            $c[$campo] = $c[$colonna] === null ? '' : ($riservati ? Cifratura::decifra($c[$colonna]) : '••••••');
            unset($c[$colonna]);
        }
        unset($c['codice_fiscale_hash'], $c['partita_iva_hash']);
        $c['mie'] = self::staff() ? ['staff'] : self::ruoli($id);
        $c['vede_documenti'] = $riservati;
        return $c;
    }

    /** Crea ($id = 0) o aggiorna l'anagrafica di un cliente. Solo staff. */
    public static function salvaCliente(int $id, array $dati): int
    {
        self::esigi($id, null, 'cliente', $id, 'modifica');
        $cf = strtoupper(preg_replace('/\s+/', '', (string) ($dati['codice_fiscale'] ?? '')));
        $piva = preg_replace('/\D+/', '', (string) ($dati['partita_iva'] ?? ''));
        $pec = Cifratura::normalizzaEmail((string) ($dati['pec'] ?? ''));
        if ($cf !== '' && !preg_match('/^([A-Z0-9]{16}|\d{11})$/', $cf)) {
            throw new ErroreApi('Codice fiscale non valido.', 422);
        }
        if ($piva !== '' && strlen($piva) !== 11) {
            throw new ErroreApi('La partita IVA deve avere 11 cifre.', 422);
        }
        if ($pec !== '' && filter_var($pec, FILTER_VALIDATE_EMAIL) === false) {
            throw new ErroreApi('Indirizzo PEC non valido.', 422);
        }
        $sede = self::testo((string) ($dati['sede'] ?? ''), 500, 'sede', false);
        $riga = [
            'tipo' => self::scelta((string) ($dati['tipo'] ?? ''), self::TIPI_CLIENTE, 'tipo'),
            'denominazione' => self::testo((string) ($dati['denominazione'] ?? ''), 220, 'denominazione'),
            'codice_fiscale_cifrato' => $cf !== '' ? Cifratura::cifra($cf) : null,
            'codice_fiscale_hash' => $cf !== '' ? Cifratura::impronta("atlante-cf|$cf") : null,
            'partita_iva_cifrata' => $piva !== '' ? Cifratura::cifra($piva) : null,
            'partita_iva_hash' => $piva !== '' ? Cifratura::impronta("atlante-piva|$piva") : null,
            'pec_cifrata' => $pec !== '' ? Cifratura::cifra($pec) : null,
            'sede_cifrata' => $sede !== null ? Cifratura::cifra($sede) : null,
        ];
        try {
            if ($id === 0) {
                $id = Database::inserisci('clienti_atlante', $riga);
                self::registra($id, 'cliente', $id, 'creazione');
                return $id;
            }
            self::riga('clienti_atlante', $id);
            Database::esegui(
                'UPDATE clienti_atlante SET ' . implode(', ', array_map(static fn ($c) => "`$c` = ?", array_keys($riga))) . ' WHERE id = ?',
                [...array_values($riga), $id]
            );
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Esiste già un cliente con questo codice fiscale o questa partita IVA.', 409);
            }
            throw $e;
        }
        self::registra($id, 'cliente', $id, 'modifica');
        return $id;
    }

    /** Cambia lo stato del cliente. "Verificato" registra chi ha verificato e quando. */
    public static function statoCliente(int $id, string $stato): void
    {
        self::esigi($id, null, 'cliente', $id, 'stato');
        self::scelta($stato, self::STATI_CLIENTE, 'stato');
        self::riga('clienti_atlante', $id);
        Database::esegui(
            "UPDATE clienti_atlante SET stato = ?, verificato_da = IF(? = 'verificato', ?, verificato_da),
                    verificato_il = IF(? = 'verificato', NOW(), verificato_il) WHERE id = ?",
            [$stato, $stato, self::io()['id'], $stato, $id]
        );
        self::registra($id, 'cliente', $id, "stato:$stato");
    }

    // ============================================================
    // Referenti: persone con un mandato per conto del cliente
    // ============================================================

    public static function referenti(int $clienteId): array
    {
        self::esigi($clienteId, [], 'referente', 0, 'elenco');
        return Database::righe(
            "SELECT r.id, r.ruolo, r.mandato_evidenza_rif, r.verificato_il, r.revocato_il, r.creato_il,
                    TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS nome, g.tipo
             FROM referenti_clienti_atlante r JOIN generalita g ON g.id = r.generalita_id
             WHERE r.cliente_id = ? ORDER BY r.revocato_il IS NOT NULL, g.cognome, g.nome",
            [$clienteId]
        );
    }

    /** Collega a un cliente un account esistente (cercato per email). Il mandato va poi verificato. */
    public static function aggiungiReferente(int $clienteId, string $email, string $ruolo, string $evidenza = ''): void
    {
        self::esigi($clienteId, null, 'referente', 0, 'aggiunta');
        self::scelta($ruolo, self::RUOLI, 'ruolo');
        $persona = Database::valore(
            "SELECT id FROM generalita WHERE email_hash = ? AND tipo <> 'giornale' AND stato = 'attivo'",
            [Cifratura::indiceEmail($email)]
        );
        if ($persona === null) {
            throw new ErroreApi('Nessun account attivo con questa email: la persona deve prima registrarsi.', 404);
        }
        try {
            $id = Database::inserisci('referenti_clienti_atlante', [
                'cliente_id' => $clienteId,
                'generalita_id' => $persona,
                'ruolo' => $ruolo,
                'mandato_evidenza_rif' => self::testo($evidenza, 500, 'riferimento del mandato', false),
            ]);
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Questa persona ha già questo ruolo per il cliente.', 409);
            }
            throw $e;
        }
        self::registra($clienteId, 'referente', $id, "aggiunta:$ruolo");
    }

    /** Verifica (il mandato è stato controllato) o revoca un referente. Senza verifica il referente non vede nulla. */
    public static function statoReferente(int $id, string $azione): void
    {
        $r = self::riga('referenti_clienti_atlante', $id);
        self::esigi((int) $r['cliente_id'], null, 'referente', $id, $azione);
        if ($azione === 'verifica') {
            Database::esegui('UPDATE referenti_clienti_atlante SET verificato_il = NOW() WHERE id = ? AND revocato_il IS NULL', [$id]);
        } elseif ($azione === 'revoca') {
            Database::esegui('UPDATE referenti_clienti_atlante SET revocato_il = NOW() WHERE id = ? AND revocato_il IS NULL', [$id]);
        } else {
            throw new ErroreApi('Azione non ammessa.', 422);
        }
        self::registra((int) $r['cliente_id'], 'referente', $id, $azione);
    }

    // ============================================================
    // Siti e funzionalità facoltative
    // ============================================================

    public static function siti(int $clienteId): array
    {
        self::esigi($clienteId, [], 'sito', 0, 'elenco');
        $siti = Database::righe(
            "SELECT s.*, b.denominazione AS beneficiario, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS politico
             FROM siti_atlante s
             LEFT JOIN clienti_atlante b ON b.id = s.beneficiario_cliente_id
             LEFT JOIN politici p ON p.id = s.politico_id LEFT JOIN generalita g ON g.id = p.generalita_id
             WHERE s.cliente_id = ? ORDER BY s.nome",
            [$clienteId]
        );
        foreach ($siti as &$sito) {
            // Una funzione senza riga è "non attiva": niente newsletter, donazioni, targeting o IA per default.
            $salvate = array_column(Database::righe('SELECT codice, stato, attivato_il FROM funzionalita_siti_atlante WHERE sito_id = ?', [$sito['id']]), null, 'codice');
            $sito['funzioni'] = [];
            foreach (self::FUNZIONI as $codice => $nome) {
                $sito['funzioni'][$codice] = $salvate[$codice]['stato'] ?? 'non_attiva';
            }
        }
        return $siti;
    }

    /** Crea ($id = 0) o aggiorna un sito del cliente. Solo staff. */
    public static function salvaSito(int $clienteId, int $id, array $dati): int
    {
        self::esigi($clienteId, null, 'sito', $id, 'modifica');
        self::riga('clienti_atlante', $clienteId);
        $nome = self::testo((string) ($dati['nome'] ?? ''), 220, 'nome');
        $dominio = strtolower(trim((string) ($dati['dominio'] ?? '')));
        if ($dominio !== '' && !preg_match('/^(?=.{4,190}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $dominio)) {
            throw new ErroreApi('Dominio non valido (esempio: mariorossi.it).', 422);
        }
        $politico = (int) ($dati['politico_id'] ?? 0);
        $beneficiario = (int) ($dati['beneficiario_cliente_id'] ?? 0);
        $riga = [
            'nome' => $nome,
            'dominio' => $dominio !== '' ? $dominio : null,
            'tipo_sito' => self::scelta((string) ($dati['tipo_sito'] ?? 'comunicazione_politica'), self::TIPI_SITO, 'tipo di sito'),
            'politico_id' => $politico > 0 ? $politico : null,
            'beneficiario_cliente_id' => $beneficiario > 0 ? $beneficiario : null,
        ];
        try {
            if ($id === 0) {
                $id = Database::inserisci('siti_atlante', ['cliente_id' => $clienteId, 'slug' => Http::slug($nome)] + $riga);
                self::registra($clienteId, 'sito', $id, 'creazione');
                return $id;
            }
            Database::esegui(
                'UPDATE siti_atlante SET nome = ?, dominio = ?, tipo_sito = ?, politico_id = ?, beneficiario_cliente_id = ? WHERE id = ? AND cliente_id = ?',
                [...array_values($riga), $id, $clienteId]
            );
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Nome o dominio già usato da un altro sito.', 409);
            }
            if (($e->errorInfo[1] ?? 0) === 1452) {
                throw new ErroreApi('Politico o beneficiario inesistente.', 422);
            }
            throw $e;
        }
        self::registra($clienteId, 'sito', $id, 'modifica');
        return $id;
    }

    /** Cambia lo stato di un sito. "Attivo" vale solo in produzione e registra la data di attivazione. */
    public static function statoSito(int $id, string $stato): void
    {
        $sito = self::riga('siti_atlante', $id);
        self::esigi((int) $sito['cliente_id'], null, 'sito', $id, 'stato');
        self::scelta($stato, self::STATI_SITO, 'stato');
        if ($stato === 'attivo' && Database::valore("SELECT stato FROM clienti_atlante WHERE id = ?", [$sito['cliente_id']]) !== 'verificato') {
            throw new ErroreApi('Il sito si attiva solo dopo la verifica del cliente.', 409);
        }
        Database::esegui(
            "UPDATE siti_atlante SET ambiente = IF(? = 'attivo', 'produzione', IF(? IN ('bozza','collaudo'), 'test', ambiente)),
                    attivato_il = IF(? = 'attivo', COALESCE(attivato_il, NOW()), attivato_il), stato = ? WHERE id = ?",
            [$stato, $stato, $stato, $stato, $id]
        );
        self::registra((int) $sito['cliente_id'], 'sito', $id, "stato:$stato");
    }

    /** Un referente chiede una funzione facoltativa: resta "richiesta" finché lo staff non la verifica. */
    public static function richiediFunzione(int $sitoId, string $codice): void
    {
        $sito = self::riga('siti_atlante', $sitoId);
        self::esigi((int) $sito['cliente_id'], ['firmatario', 'comunicazione', 'tecnico', 'amministrativo'], 'funzionalita', $sitoId, "richiesta:$codice");
        self::scelta($codice, self::FUNZIONI, 'funzione');
        Database::esegui(
            "INSERT INTO funzionalita_siti_atlante (sito_id, codice, stato) VALUES (?, ?, 'richiesta')
             ON DUPLICATE KEY UPDATE stato = IF(stato IN ('non_attiva','sospesa'), 'richiesta', stato)",
            [$sitoId, $codice]
        );
        self::registra((int) $sito['cliente_id'], 'funzionalita', $sitoId, "richiesta:$codice");
    }

    /** Lo staff cambia lo stato di una funzione. "Attiva" registra chi l'ha autorizzata e quando. */
    public static function statoFunzione(int $sitoId, string $codice, string $stato, string $evidenza = ''): void
    {
        $sito = self::riga('siti_atlante', $sitoId);
        self::esigi((int) $sito['cliente_id'], null, 'funzionalita', $sitoId, "stato:$codice");
        self::scelta($codice, self::FUNZIONI, 'funzione');
        self::scelta($stato, self::STATI_FUNZIONE, 'stato');
        $io = self::io()['id'];
        $rif = self::testo($evidenza, 500, 'riferimento della verifica', false);
        Database::esegui(
            "INSERT INTO funzionalita_siti_atlante (sito_id, codice, stato, verifica_evidenza_rif, autorizzato_da, attivato_il)
             VALUES (?, ?, ?, ?, IF(? IN ('autorizzata','attiva'), ?, NULL), IF(? = 'attiva', NOW(), NULL))
             ON DUPLICATE KEY UPDATE
                 verifica_evidenza_rif = COALESCE(VALUES(verifica_evidenza_rif), verifica_evidenza_rif),
                 autorizzato_da = IF(VALUES(stato) IN ('autorizzata','attiva'), COALESCE(autorizzato_da, VALUES(autorizzato_da)), autorizzato_da),
                 attivato_il = IF(VALUES(stato) = 'attiva', COALESCE(attivato_il, NOW()), attivato_il),
                 stato = VALUES(stato)",
            [$sitoId, $codice, $stato, $rif, $stato, $io, $stato]
        );
        self::registra((int) $sito['cliente_id'], 'funzionalita', $sitoId, "$codice:$stato");
    }

    // ============================================================
    // Offerte economiche
    // ============================================================

    public static function offerte(int $clienteId): array
    {
        self::esigi($clienteId, ['firmatario', 'amministrativo'], 'offerta', 0, 'elenco');
        return Database::righe(
            'SELECT o.*, s.nome AS sito FROM offerte_atlante o LEFT JOIN siti_atlante s ON s.id = o.sito_id WHERE o.cliente_id = ? ORDER BY o.creato_il DESC',
            [$clienteId]
        );
    }

    /** Importo in euro scritto in un form ("120" o "120,50") → centesimi interi. */
    public static function centesimi(string $euro, string $campo): int
    {
        if (trim($euro) === '') {
            return 0;
        }
        if (!preg_match('/^(\d{1,7})(?:[.,](\d{1,2}))?$/', trim($euro), $m)) {
            throw new ErroreApi("Importo non valido: $campo.", 422);
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public static function creaOfferta(int $clienteId, array $dati): int
    {
        self::esigi($clienteId, null, 'offerta', 0, 'creazione');
        $data = static function (string $valore, string $campo): ?string {
            if ($valore === '') {
                return null;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valore) || strtotime($valore) === false) {
                throw new ErroreApi("Data non valida: $campo.", 422);
            }
            return $valore;
        };
        $decorrenza = $data((string) ($dati['decorrenza'] ?? ''), 'decorrenza');
        $termine = $data((string) ($dati['termine'] ?? ''), 'termine');
        if ($decorrenza !== null && $termine !== null && $termine < $decorrenza) {
            throw new ErroreApi('Il termine non può precedere la decorrenza.', 422);
        }
        $sito = (int) ($dati['sito_id'] ?? 0);
        try {
            $id = Database::inserisci('offerte_atlante', [
                'cliente_id' => $clienteId,
                'sito_id' => $sito > 0 ? $sito : null,
                'codice_offerta' => self::testo((string) ($dati['codice_offerta'] ?? ''), 80, 'codice dell\'offerta'),
                'attivazione_centesimi' => self::centesimi((string) ($dati['attivazione'] ?? ''), 'attivazione'),
                'canone_centesimi' => self::centesimi((string) ($dati['canone'] ?? ''), 'canone'),
                'periodicita' => self::scelta((string) ($dati['periodicita'] ?? ''), self::PERIODICITA, 'periodicità'),
                'decorrenza' => $decorrenza,
                'termine' => $termine,
            ]);
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Esiste già un\'offerta con questo codice per il cliente.', 409);
            }
            if (($e->errorInfo[1] ?? 0) === 1452) {
                throw new ErroreApi('Il sito indicato non appartiene a questo cliente.', 422);
            }
            throw $e;
        }
        self::registra($clienteId, 'offerta', $id, 'creazione');
        return $id;
    }

    /** Lo staff invia o fa scadere l'offerta; solo il firmatario del cliente la accetta o la rifiuta. */
    public static function statoOfferta(int $id, string $stato): void
    {
        $o = self::riga('offerte_atlante', $id);
        $cliente = (int) $o['cliente_id'];
        if (in_array($stato, ['accettata', 'rifiutata'], true)) {
            if (self::staff() || !in_array('firmatario', self::ruoli($cliente), true)) {
                self::esigi($cliente, [], 'offerta', $id, "stato:$stato");
                throw new ErroreApi('Solo il firmatario del cliente può accettare o rifiutare l\'offerta.', 403);
            }
            if ($o['stato'] !== 'inviata') {
                throw new ErroreApi('Si può rispondere solo a un\'offerta inviata.', 409);
            }
            Database::esegui('UPDATE offerte_atlante SET stato = ?, approvata_da = ? WHERE id = ?', [$stato, self::io()['id'], $id]);
        } elseif (in_array($stato, ['inviata', 'scaduta', 'bozza'], true)) {
            self::esigi($cliente, null, 'offerta', $id, "stato:$stato");
            if (in_array($o['stato'], ['accettata', 'rifiutata'], true)) {
                throw new ErroreApi('L\'offerta ha già avuto risposta: creane una nuova.', 409);
            }
            Database::esegui('UPDATE offerte_atlante SET stato = ? WHERE id = ?', [$stato, $id]);
        } else {
            throw new ErroreApi('Stato non ammesso.', 422);
        }
        self::registra($cliente, 'offerta', $id, "stato:$stato");
    }

    // ============================================================
    // Modelli documentali MASTER (uguali per tutti i clienti)
    // ============================================================

    /** Catalogo dei modelli con le loro versioni. Solo staff. */
    public static function modelli(): array
    {
        if (!self::staff()) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        $modelli = Database::righe('SELECT * FROM modelli_documentali_master ORDER BY categoria, titolo');
        $versioni = Database::righe('SELECT id, modello_id, versione, stato, sha256, approvato_il, creato_il FROM versioni_modelli_master ORDER BY creato_il DESC, id DESC');
        foreach ($modelli as &$m) {
            $m['versioni'] = array_values(array_filter($versioni, static fn ($v) => $v['modello_id'] === $m['id']));
        }
        return $modelli;
    }

    public static function creaModello(array $dati): int
    {
        if (!self::staff()) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        $codice = strtoupper(self::testo((string) ($dati['codice'] ?? ''), 80, 'codice'));
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $codice)) {
            throw new ErroreApi('Il codice può contenere solo lettere, cifre, trattini e trattini bassi.', 422);
        }
        try {
            return Database::inserisci('modelli_documentali_master', [
                'codice' => $codice,
                'titolo' => self::testo((string) ($dati['titolo'] ?? ''), 240, 'titolo'),
                'categoria' => self::scelta((string) ($dati['categoria'] ?? ''), self::CATEGORIE, 'categoria'),
                'destinatario' => self::scelta((string) ($dati['destinatario'] ?? 'tutti'), self::DESTINATARI, 'destinatario'),
                'condizione_utilizzo' => self::testo((string) ($dati['condizione_utilizzo'] ?? ''), 220, 'condizione di utilizzo', false),
            ]);
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Esiste già un modello con questo codice.', 409);
            }
            throw $e;
        }
    }

    /** Nuova versione di un modello: una versione esistente non si sovrascrive mai. */
    public static function creaVersione(int $modelloId, string $versione, ?array $file): int
    {
        if (!self::staff()) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        self::riga('modelli_documentali_master', $modelloId);
        $versione = self::testo($versione, 30, 'versione');
        if (Database::valore('SELECT 1 FROM versioni_modelli_master WHERE modello_id = ? AND versione = ?', [$modelloId, $versione]) !== null) {
            throw new ErroreApi('Questa versione esiste già: usa un numero nuovo.', 409);
        }
        [$rif, $sha] = Archivio::salva($file, Archivio::DOCUMENTI);
        return Database::inserisci('versioni_modelli_master', ['modello_id' => $modelloId, 'versione' => $versione, 'file_rif' => $rif, 'sha256' => $sha]);
    }

    /** Cambia lo stato di una versione. "Approvata" registra chi l'ha approvata e quando. */
    public static function statoVersione(int $id, string $stato): void
    {
        if (!self::staff()) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        $v = self::riga('versioni_modelli_master', $id);
        self::scelta($stato, self::STATI_VERSIONE, 'stato');
        if ($v['stato'] === 'ritirata') {
            throw new ErroreApi('Una versione ritirata non si riattiva: caricane una nuova.', 409);
        }
        $io = self::io()['id'];
        Database::esegui(
            "UPDATE versioni_modelli_master SET
                    verificato_da = IF(? = 'da_verificare', ?, verificato_da),
                    approvato_da = IF(? = 'approvata', ?, approvato_da),
                    approvato_il = IF(? = 'approvata', NOW(), approvato_il), stato = ? WHERE id = ?",
            [$stato, $io, $stato, $io, $stato, $stato, $id]
        );
    }

    /** Versioni approvate, utilizzabili per generare i documenti di un cliente di quel tipo. */
    public static function versioniApprovate(string $tipoCliente): array
    {
        return Database::righe(
            "SELECT v.id, CONCAT(m.titolo, ' · v', v.versione) AS nome FROM versioni_modelli_master v
             JOIN modelli_documentali_master m ON m.id = v.modello_id
             WHERE v.stato = 'approvata' AND m.attivo = 1 AND m.destinatario IN ('tutti', ?) ORDER BY m.titolo, v.versione DESC",
            [$tipoCliente]
        );
    }

    // ============================================================
    // Campi di personalizzazione del cliente
    // ============================================================

    public static function campi(int $clienteId): array
    {
        self::esigi($clienteId, [], 'cliente', $clienteId, 'campi');
        $vede = self::staff() || array_intersect(self::RUOLI_DOCUMENTI, self::ruoli($clienteId)) !== [];
        return array_map(static fn (array $c): array => [
            'id' => $c['id'],
            'chiave' => $c['chiave'],
            'riservato' => $c['valore_cifrato'] !== null,
            'valore' => $c['valore_cifrato'] !== null ? ($vede ? Cifratura::decifra($c['valore_cifrato']) : '••••••') : (string) $c['valore_pubblico'],
            'verificato_il' => $c['verificato_il'],
        ], Database::righe('SELECT * FROM campi_clienti_atlante WHERE cliente_id = ? ORDER BY chiave', [$clienteId]));
    }

    /** Salva un campo: riservato (cifrato) oppure pubblicabile, mai entrambi. Un valore vuoto elimina il campo. */
    public static function salvaCampo(int $clienteId, string $chiave, string $valore, bool $riservato): void
    {
        self::esigi($clienteId, ['firmatario', 'comunicazione', 'amministrativo'], 'cliente', $clienteId, 'campo');
        $chiave = strtolower(self::testo($chiave, 120, 'nome del campo'));
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $chiave)) {
            throw new ErroreApi('Il nome del campo può contenere solo lettere minuscole, cifre e trattini bassi.', 422);
        }
        $valore = self::testo($valore, 5000, 'valore', false);
        if ($valore === null) {
            Database::esegui('DELETE FROM campi_clienti_atlante WHERE cliente_id = ? AND chiave = ?', [$clienteId, $chiave]);
        } else {
            // Un valore modificato torna "da verificare".
            Database::esegui(
                'INSERT INTO campi_clienti_atlante (cliente_id, chiave, valore_pubblico, valore_cifrato) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE valore_pubblico = VALUES(valore_pubblico), valore_cifrato = VALUES(valore_cifrato), verificato_da = NULL, verificato_il = NULL',
                [$clienteId, $chiave, $riservato ? null : $valore, $riservato ? Cifratura::cifra($valore) : null]
            );
        }
        self::registra($clienteId, 'cliente', $clienteId, 'campo');
    }

    // ============================================================
    // Fascicoli, documenti, firme
    // ============================================================

    public static function fascicoli(int $clienteId): array
    {
        self::esigi($clienteId, self::RUOLI_DOCUMENTI, 'fascicolo', 0, 'elenco');
        return Database::righe(
            'SELECT f.*, s.nome AS sito, (SELECT COUNT(*) FROM documenti_atlante d WHERE d.fascicolo_id = f.id) AS documenti
             FROM fascicoli_atlante f LEFT JOIN siti_atlante s ON s.id = f.sito_id WHERE f.cliente_id = ? ORDER BY f.creato_il DESC',
            [$clienteId]
        );
    }

    public static function creaFascicolo(int $clienteId, array $dati): int
    {
        self::esigi($clienteId, null, 'fascicolo', 0, 'creazione');
        $sito = (int) ($dati['sito_id'] ?? 0);
        $codice = strtoupper(self::testo((string) ($dati['codice'] ?? ''), 100, 'codice'));
        try {
            $id = Database::inserisci('fascicoli_atlante', [
                'cliente_id' => $clienteId,
                'sito_id' => $sito > 0 ? $sito : null,
                'codice' => $codice,
                'titolo' => self::testo((string) ($dati['titolo'] ?? ''), 240, 'titolo'),
                'creato_da' => self::io()['id'],
            ]);
        } catch (PDOException $e) {
            if (Database::doppione($e)) {
                throw new ErroreApi('Esiste già un fascicolo con questo codice per il cliente.', 409);
            }
            if (($e->errorInfo[1] ?? 0) === 1452) {
                throw new ErroreApi('Il sito indicato non appartiene a questo cliente.', 422);
            }
            throw $e;
        }
        self::registra($clienteId, 'fascicolo', $id, 'creazione');
        return $id;
    }

    /** Fascicolo con documenti, firme e verifiche collegate. */
    public static function fascicolo(int $id): array
    {
        $f = Database::riga('SELECT f.*, c.denominazione AS cliente, c.tipo AS tipo_cliente FROM fascicoli_atlante f JOIN clienti_atlante c ON c.id = f.cliente_id WHERE f.id = ?', [$id]);
        if ($f === null) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        self::esigi((int) $f['cliente_id'], self::RUOLI_DOCUMENTI, 'fascicolo', $id, 'lettura');
        $f['documenti'] = Database::righe(
            "SELECT d.id, d.revisione_cliente, d.stato, d.sha256, d.file_rif IS NOT NULL AS ha_file, d.verificato_il, d.approvato_il, d.creato_il,
                    m.titolo, m.codice, v.versione
             FROM documenti_atlante d JOIN versioni_modelli_master v ON v.id = d.versione_master_id
             JOIN modelli_documentali_master m ON m.id = v.modello_id WHERE d.fascicolo_id = ? ORDER BY d.creato_il, d.id",
            [$id]
        );
        foreach ($f['documenti'] as &$d) {
            $d['firme'] = Database::righe(
                "SELECT f.id, f.parte, f.firmato_il, f.evidenza_sha256, f.verificata_il, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS firmatario
                 FROM firme_documenti_atlante f LEFT JOIN generalita g ON g.id = f.firmatario_id WHERE f.documento_id = ? ORDER BY f.id",
                [$d['id']]
            );
        }
        self::registra((int) $f['cliente_id'], 'fascicolo', $id, 'lettura');
        return $f;
    }

    public static function statoFascicolo(int $id, string $stato): void
    {
        $f = self::riga('fascicoli_atlante', $id);
        self::esigi((int) $f['cliente_id'], null, 'fascicolo', $id, 'stato');
        Database::esegui('UPDATE fascicoli_atlante SET stato = ? WHERE id = ?', [self::scelta($stato, self::STATI_FASCICOLO, 'stato'), $id]);
        self::registra((int) $f['cliente_id'], 'fascicolo', $id, "stato:$stato");
    }

    /** Fascicolo e cliente a cui appartiene un documento. */
    private static function documento(int $id): array
    {
        $d = Database::riga('SELECT d.*, f.cliente_id FROM documenti_atlante d JOIN fascicoli_atlante f ON f.id = d.fascicolo_id WHERE d.id = ?', [$id]);
        if ($d === null) {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        return $d;
    }

    /** Nuovo documento del cliente a partire da una versione APPROVATA di un modello MASTER. */
    public static function creaDocumento(int $fascicoloId, int $versioneId): int
    {
        $f = self::riga('fascicoli_atlante', $fascicoloId);
        self::esigi((int) $f['cliente_id'], null, 'documento', 0, 'creazione');
        if (Database::valore("SELECT 1 FROM versioni_modelli_master WHERE id = ? AND stato = 'approvata'", [$versioneId]) === null) {
            throw new ErroreApi('Si possono usare solo versioni approvate dei modelli.', 409);
        }
        $revisione = 1 + (int) Database::valore('SELECT MAX(revisione_cliente) FROM documenti_atlante WHERE fascicolo_id = ? AND versione_master_id = ?', [$fascicoloId, $versioneId]);
        $id = Database::inserisci('documenti_atlante', [
            'fascicolo_id' => $fascicoloId,
            'versione_master_id' => $versioneId,
            'revisione_cliente' => $revisione,
            'generato_da' => self::io()['id'],
        ]);
        self::registra((int) $f['cliente_id'], 'documento', $id, 'creazione');
        return $id;
    }

    /** Carica il file compilato del documento: torna "da verificare" e perde verifica e approvazione precedenti. */
    public static function caricaDocumento(int $id, ?array $file): void
    {
        $d = self::documento($id);
        self::esigi((int) $d['cliente_id'], null, 'documento', $id, 'caricamento');
        if (!in_array($d['stato'], ['bozza', 'da_verificare'], true)) {
            throw new ErroreApi('Il file non si sostituisce più: crea una nuova revisione del documento.', 409);
        }
        [$rif, $sha] = Archivio::salva($file, Archivio::DOCUMENTI);
        Database::esegui(
            "UPDATE documenti_atlante SET file_rif = ?, sha256 = ?, stato = 'da_verificare', verificato_da = NULL, verificato_il = NULL, approvato_da = NULL, approvato_il = NULL WHERE id = ?",
            [$rif, $sha, $id]
        );
        self::registra((int) $d['cliente_id'], 'documento', $id, 'caricamento');
    }

    /**
     * Avanza il documento: verifica → approva → pronto_firma → firmato, oppure annulla.
     * Ogni passo richiede il precedente; "firmato" richiede almeno una firma del cliente e una del fornitore, entrambe verificate.
     */
    public static function avanzaDocumento(int $id, string $azione): void
    {
        $d = self::documento($id);
        $cliente = (int) $d['cliente_id'];
        self::esigi($cliente, null, 'documento', $id, $azione);
        $io = self::io()['id'];
        switch ($azione) {
            case 'verifica':
                if ($d['file_rif'] === null || $d['stato'] !== 'da_verificare') {
                    throw new ErroreApi('Si verifica un documento con il file caricato e in stato «da verificare».', 409);
                }
                Database::esegui('UPDATE documenti_atlante SET verificato_da = ?, verificato_il = NOW() WHERE id = ?', [$io, $id]);
                break;
            case 'approva':
                if ($d['verificato_il'] === null || $d['stato'] !== 'da_verificare') {
                    throw new ErroreApi('Prima dell\'approvazione il documento va verificato.', 409);
                }
                Database::esegui("UPDATE documenti_atlante SET approvato_da = ?, approvato_il = NOW(), stato = 'pronto_firma' WHERE id = ?", [$io, $id]);
                break;
            case 'firmato':
                $parti = array_column(Database::righe(
                    'SELECT DISTINCT parte FROM firme_documenti_atlante WHERE documento_id = ? AND verificata_il IS NOT NULL',
                    [$id]
                ), 'parte');
                if ($d['stato'] !== 'pronto_firma' || !in_array('cliente', $parti, true) || !in_array('fornitore', $parti, true)) {
                    throw new ErroreApi('Servono una firma del cliente e una del fornitore, entrambe verificate.', 409);
                }
                Database::esegui("UPDATE documenti_atlante SET stato = 'firmato' WHERE id = ?", [$id]);
                break;
            case 'annulla':
                Database::esegui("UPDATE documenti_atlante SET stato = 'annullato' WHERE id = ?", [$id]);
                break;
            default:
                throw new ErroreApi('Azione non ammessa.', 422);
        }
        self::registra($cliente, 'documento', $id, $azione);
    }

    /** Registra la prova di una firma (file). La firma conta solo dopo la verifica. */
    public static function aggiungiFirma(int $documentoId, string $parte, string $firmatoIl, ?array $prova, string $emailFirmatario = ''): void
    {
        $d = self::documento($documentoId);
        self::esigi((int) $d['cliente_id'], null, 'firma', $documentoId, 'aggiunta');
        if ($d['stato'] !== 'pronto_firma') {
            throw new ErroreApi('Le firme si registrano su un documento pronto per la firma.', 409);
        }
        self::scelta($parte, self::PARTI, 'parte');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $firmatoIl) || strtotime($firmatoIl) === false || $firmatoIl > date('Y-m-d')) {
            throw new ErroreApi('Indica la data della firma (non futura).', 422);
        }
        $firmatario = $emailFirmatario === '' ? null : Database::valore(
            "SELECT id FROM generalita WHERE email_hash = ? AND tipo <> 'giornale'",
            [Cifratura::indiceEmail($emailFirmatario)]
        );
        if ($emailFirmatario !== '' && $firmatario === null) {
            throw new ErroreApi('Nessun account con l\'email del firmatario.', 404);
        }
        [$rif, $sha] = Archivio::salva($prova, Archivio::PROVE);
        $id = Database::inserisci('firme_documenti_atlante', [
            'documento_id' => $documentoId,
            'parte' => $parte,
            'firmatario_id' => $firmatario,
            'firmato_il' => $firmatoIl . ' 00:00:00',
            'evidenza_rif' => $rif,
            'evidenza_sha256' => $sha,
        ]);
        self::registra((int) $d['cliente_id'], 'firma', $id, "aggiunta:$parte");
    }

    public static function verificaFirma(int $id): void
    {
        $f = self::riga('firme_documenti_atlante', $id);
        $d = self::documento((int) $f['documento_id']);
        self::esigi((int) $d['cliente_id'], null, 'firma', $id, 'verifica');
        Database::esegui('UPDATE firme_documenti_atlante SET verificata_da = ?, verificata_il = NOW() WHERE id = ? AND verificata_il IS NULL', [self::io()['id'], $id]);
        self::registra((int) $d['cliente_id'], 'firma', $id, 'verifica');
    }

    /**
     * File da scaricare, dopo i controlli: ['contenuto', 'tipo MIME', 'nome del file'].
     * $cosa: "versione" (modello, solo staff), "documento" o "firma" (staff e referenti con titolo).
     */
    public static function scarica(string $cosa, int $id): array
    {
        if ($cosa === 'versione') {
            if (!self::staff()) {
                throw new ErroreApi('Elemento non trovato.', 404);
            }
            $rif = self::riga('versioni_modelli_master', $id)['file_rif'];
        } elseif ($cosa === 'documento') {
            $d = self::documento($id);
            self::esigi((int) $d['cliente_id'], self::RUOLI_DOCUMENTI, 'documento', $id, 'scaricamento');
            $rif = $d['file_rif'];
            self::registra((int) $d['cliente_id'], 'documento', $id, 'scaricamento');
        } elseif ($cosa === 'firma') {
            $f = self::riga('firme_documenti_atlante', $id);
            $d = self::documento((int) $f['documento_id']);
            self::esigi((int) $d['cliente_id'], self::RUOLI_DOCUMENTI, 'firma', $id, 'scaricamento');
            $rif = $f['evidenza_rif'];
            self::registra((int) $d['cliente_id'], 'firma', $id, 'scaricamento');
        } else {
            throw new ErroreApi('Elemento non trovato.', 404);
        }
        if ($rif === null) {
            throw new ErroreApi('Nessun file caricato.', 404);
        }
        return [Archivio::leggi($rif), Archivio::tipo($rif), "$cosa-$id." . pathinfo($rif, PATHINFO_EXTENSION)];
    }

    // ============================================================
    // Verifiche (GDPR, DPIA, cookie, AI Act, pubblicità politica...) e registro
    // ============================================================

    public static function verifiche(int $clienteId): array
    {
        self::esigi($clienteId, ['firmatario', 'privacy', 'ia', 'amministrativo'], 'verifica', 0, 'elenco');
        return Database::righe(
            "SELECT v.*, s.nome AS sito, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS validatore
             FROM verifiche_atlante v LEFT JOIN siti_atlante s ON s.id = v.sito_id LEFT JOIN generalita g ON g.id = v.validata_da
             WHERE v.cliente_id = ? ORDER BY v.esito IN ('conclusa','non_applicabile'), v.creato_il DESC",
            [$clienteId]
        );
    }

    public static function creaVerifica(int $clienteId, string $oggetto, int $sitoId = 0): int
    {
        self::esigi($clienteId, null, 'verifica', 0, 'creazione');
        try {
            $id = Database::inserisci('verifiche_atlante', [
                'cliente_id' => $clienteId,
                'sito_id' => $sitoId > 0 ? $sitoId : null,
                'oggetto' => self::scelta($oggetto, self::OGGETTI_VERIFICA, 'oggetto'),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1452) {
                throw new ErroreApi('Il sito indicato non appartiene a questo cliente.', 422);
            }
            throw $e;
        }
        self::registra($clienteId, 'verifica', $id, "creazione:$oggetto");
        return $id;
    }

    /** Aggiorna l'esito. Chiudere una verifica richiede una motivazione e registra chi la valida. */
    public static function esitoVerifica(int $id, string $esito, string $motivazione, string $evidenza = ''): void
    {
        $v = self::riga('verifiche_atlante', $id);
        self::esigi((int) $v['cliente_id'], null, 'verifica', $id, 'esito');
        self::scelta($esito, self::ESITI, 'esito');
        $chiusa = in_array($esito, ['conclusa', 'non_applicabile'], true);
        $motivazione = self::testo($motivazione, 5000, 'motivazione', $chiusa);
        Database::esegui(
            'UPDATE verifiche_atlante SET esito = ?, motivazione = ?, evidenza_rif = ?, validata_da = ?, validata_il = ? WHERE id = ?',
            [$esito, $motivazione, self::testo($evidenza, 500, 'riferimento della prova', false), $chiusa ? self::io()['id'] : null, $chiusa ? gmdate('Y-m-d H:i:s') : null, $id]
        );
        self::registra((int) $v['cliente_id'], 'verifica', $id, "esito:$esito");
    }

    /** Ultime operazioni registrate per il cliente. Solo staff. */
    public static function registro(int $clienteId): array
    {
        self::esigi($clienteId, null, 'cliente', $clienteId, 'registro');
        return Database::righe(
            "SELECT a.oggetto, a.oggetto_id, a.azione, a.esito, a.creato_il, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS attore
             FROM audit_atlante a LEFT JOIN generalita g ON g.id = a.attore_id WHERE a.cliente_id = ? ORDER BY a.id DESC LIMIT 100",
            [$clienteId]
        );
    }
}
