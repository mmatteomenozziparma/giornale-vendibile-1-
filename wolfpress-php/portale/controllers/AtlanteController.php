<?php
/**
 * ATLANTE CIVICO nel portale: clienti (candidati, partiti, liste, comitati),
 * siti, offerte, modelli documentali e fascicoli con documenti, firme e verifiche.
 *
 * Lo staff del fornitore (tecnici) gestisce tutto; un referente vede solo i
 * clienti per cui ha un mandato verificato. I controlli veri sono nel backend
 * (classe Atlante): qui si raccolgono i dati dei form e si sceglie la pagina.
 */
declare(strict_types=1);

final class AtlanteController extends PortaleController
{
    protected string $area = 'atlante';
    protected string $voce = 'clienti';

    /* ---------- Pagine ---------- */

    /** atlante.php: elenco dei clienti visibili a chi è collegato. */
    public function index(): void
    {
        $this->vista('atlante', [
            'titolo' => 'Clienti · Atlante Civico',
            'staff' => Atlante::staff(),
            'clienti' => Atlante::clienti(),
        ]);
    }

    /** atlante-cliente.php: scheda completa di un cliente. */
    public function cliente(): void
    {
        $id = (int) $this->parametro('id', 20);
        try {
            $cliente = Atlante::cliente($id);
        } catch (ErroreApi) {
            $this->pagina404('Cliente non trovato.');
            return;
        }
        $staff = Atlante::staff();
        // Ogni sezione si carica solo se il ruolo di chi è collegato la può vedere.
        $puo = static fn (array $ruoli): bool => $staff || array_intersect($ruoli, $cliente['mie']) !== [];
        $this->vista('atlante-cliente', [
            'titolo' => $cliente['denominazione'] . ' · Atlante Civico',
            'staff' => $staff,
            'cliente' => $cliente,
            'referenti' => Atlante::referenti($id),
            'siti' => Atlante::siti($id),
            'puoChiedere' => !$staff && $puo(['firmatario', 'comunicazione', 'tecnico', 'amministrativo']),
            'offerte' => $puo(['firmatario', 'amministrativo']) ? Atlante::offerte($id) : null,
            'firmatario' => in_array('firmatario', $cliente['mie'], true),
            'campi' => Atlante::campi($id),
            'puoCampi' => $puo(['firmatario', 'comunicazione', 'amministrativo']),
            'fascicoli' => $cliente['vede_documenti'] ? Atlante::fascicoli($id) : null,
            'verifiche' => $puo(['firmatario', 'privacy', 'ia', 'amministrativo']) ? Atlante::verifiche($id) : null,
            'registro' => $staff ? Atlante::registro($id) : null,
            'politici' => $staff ? Evento::politici() : [],
            'altriClienti' => $staff ? array_column(array_filter(Atlante::clienti(), static fn ($c) => (int) $c['id'] !== $id), 'denominazione', 'id') : [],
        ]);
    }

    /** atlante-fascicolo.php: documenti, firme e stato di un fascicolo. */
    public function fascicolo(): void
    {
        try {
            $fascicolo = Atlante::fascicolo((int) $this->parametro('id', 20));
        } catch (ErroreApi) {
            $this->pagina404('Fascicolo non trovato.');
            return;
        }
        $staff = Atlante::staff();
        $this->vista('atlante-fascicolo', [
            'titolo' => $fascicolo['titolo'] . ' · Atlante Civico',
            'staff' => $staff,
            'fascicolo' => $fascicolo,
            'versioni' => $staff ? array_column(Atlante::versioniApprovate($fascicolo['tipo_cliente']), 'nome', 'id') : [],
        ]);
    }

    /** atlante-modelli.php: catalogo dei modelli MASTER e delle loro versioni (solo staff). */
    public function modelli(): void
    {
        $this->voce = 'modelli';
        try {
            $modelli = Atlante::modelli();
        } catch (ErroreApi) {
            $this->pagina404();
            return;
        }
        $this->vista('atlante-modelli', ['titolo' => 'Modelli documentali · Atlante Civico', 'modelli' => $modelli]);
    }

    /** Scarica un file dell'archivio privato (modello, documento o prova di firma) dopo i controlli. */
    public function scarica(): void
    {
        try {
            [$contenuto, $tipo, $nome] = Atlante::scarica($this->parametro('cosa', 20), (int) $this->parametro('id', 20));
        } catch (ErroreApi) {
            $this->pagina404('File non trovato.');
            return;
        }
        header('Content-Type: ' . ($tipo !== '' ? $tipo : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Content-Length: ' . strlen($contenuto));
        header('X-Content-Type-Options: nosniff');
        echo $contenuto;
    }

    /* ---------- Azioni sui clienti ---------- */

    public function salvaCliente(): void
    {
        $this->soloPost();
        $id = (int) $this->campo('id', 20);
        $nuovo = $this->fai(fn (): int => Atlante::salvaCliente($id, [
            'tipo' => $this->campo('tipo', 20),
            'denominazione' => $this->campo('denominazione', 220),
            'codice_fiscale' => $this->campo('codice_fiscale', 30),
            'partita_iva' => $this->campo('partita_iva', 30),
            'pec' => $this->campo('pec', 254),
            'sede' => $this->campo('sede', 500),
        ]), 'Anagrafica salvata.', $id > 0 ? 'atlante/cliente' : 'atlante', $id > 0 ? ['id' => $id] : []);
        $this->vai('atlante/cliente', ['id' => $nuovo]);
    }

    public function statoCliente(): void
    {
        $id = $this->idCliente();
        $stato = $this->campo('stato', 20);
        $this->fai(static fn () => Atlante::statoCliente($id, $stato), 'Stato del cliente aggiornato.', 'atlante/cliente', ['id' => $id]);
        $this->torna($id);
    }

    public function referente(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::aggiungiReferente($id, $this->campo('email', 254), $this->campo('ruolo', 20), $this->campo('evidenza', 500)),
            'Referente aggiunto: vedrà il cliente dopo la verifica del mandato.', 'atlante/cliente', ['id' => $id], 'referenti');
        $this->torna($id, 'referenti');
    }

    public function statoReferente(): void
    {
        $id = $this->idCliente();
        $azione = $this->campo('cosa', 10);
        $this->fai(fn () => Atlante::statoReferente((int) $this->campo('referente', 20), $azione),
            $azione === 'verifica' ? 'Mandato verificato.' : 'Mandato revocato.', 'atlante/cliente', ['id' => $id], 'referenti');
        $this->torna($id, 'referenti');
    }

    /* ---------- Siti e funzionalità ---------- */

    public function sito(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::salvaSito($id, (int) $this->campo('sito', 20), [
            'nome' => $this->campo('nome', 220),
            'dominio' => $this->campo('dominio', 190),
            'tipo_sito' => $this->campo('tipo_sito', 30),
            'politico_id' => $this->campo('politico_id', 20),
            'beneficiario_cliente_id' => $this->campo('beneficiario_cliente_id', 20),
        ]), 'Sito salvato.', 'atlante/cliente', ['id' => $id], 'siti');
        $this->torna($id, 'siti');
    }

    public function statoSito(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::statoSito((int) $this->campo('sito', 20), $this->campo('stato', 20)), 'Stato del sito aggiornato.', 'atlante/cliente', ['id' => $id], 'siti');
        $this->torna($id, 'siti');
    }

    public function richiediFunzione(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::richiediFunzione((int) $this->campo('sito', 20), $this->campo('codice', 30)),
            'Richiesta inviata: la funzione si attiva solo dopo le verifiche.', 'atlante/cliente', ['id' => $id], 'siti');
        $this->torna($id, 'siti');
    }

    public function statoFunzione(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::statoFunzione((int) $this->campo('sito', 20), $this->campo('codice', 30), $this->campo('stato', 20), $this->campo('evidenza', 500)),
            'Funzione aggiornata.', 'atlante/cliente', ['id' => $id], 'siti');
        $this->torna($id, 'siti');
    }

    /* ---------- Offerte, campi, verifiche ---------- */

    public function offerta(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::creaOfferta($id, [
            'codice_offerta' => $this->campo('codice_offerta', 80),
            'sito_id' => $this->campo('sito_id', 20),
            'attivazione' => $this->campo('attivazione', 12),
            'canone' => $this->campo('canone', 12),
            'periodicita' => $this->campo('periodicita', 20),
            'decorrenza' => $this->campo('decorrenza', 10),
            'termine' => $this->campo('termine', 10),
        ]), 'Offerta creata in bozza.', 'atlante/cliente', ['id' => $id], 'offerte');
        $this->torna($id, 'offerte');
    }

    public function statoOfferta(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::statoOfferta((int) $this->campo('offerta', 20), $this->campo('stato', 20)), 'Offerta aggiornata.', 'atlante/cliente', ['id' => $id], 'offerte');
        $this->torna($id, 'offerte');
    }

    public function salvaCampo(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::salvaCampo($id, $this->campo('chiave', 120), $this->campo('valore', 5000), $this->campo('riservato', 1) === '1'),
            'Campo salvato.', 'atlante/cliente', ['id' => $id], 'campi');
        $this->torna($id, 'campi');
    }

    public function verifica(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::creaVerifica($id, $this->campo('oggetto', 30), (int) $this->campo('sito_id', 20)), 'Verifica aperta.', 'atlante/cliente', ['id' => $id], 'verifiche');
        $this->torna($id, 'verifiche');
    }

    public function esitoVerifica(): void
    {
        $id = $this->idCliente();
        $this->fai(fn () => Atlante::esitoVerifica((int) $this->campo('verifica', 20), $this->campo('esito', 20), $this->campo('motivazione', 5000), $this->campo('evidenza', 500)),
            'Verifica aggiornata.', 'atlante/cliente', ['id' => $id], 'verifiche');
        $this->torna($id, 'verifiche');
    }

    /* ---------- Fascicoli, documenti, firme ---------- */

    public function creaFascicolo(): void
    {
        $id = $this->idCliente();
        $nuovo = $this->fai(fn (): int => Atlante::creaFascicolo($id, [
            'codice' => $this->campo('codice', 100),
            'titolo' => $this->campo('titolo', 240),
            'sito_id' => $this->campo('sito_id', 20),
        ]), 'Fascicolo aperto.', 'atlante/cliente', ['id' => $id], 'fascicoli');
        $this->vai('atlante/fascicolo', ['id' => $nuovo]);
    }

    public function statoFascicolo(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::statoFascicolo($id, $this->campo('stato', 20)), 'Stato del fascicolo aggiornato.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    public function documento(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::creaDocumento($id, (int) $this->campo('versione', 20)), 'Documento creato: ora carica il file compilato.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    public function caricaDocumento(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::caricaDocumento((int) $this->campo('documento', 20), $_FILES['file'] ?? null), 'File caricato: il documento è da verificare.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    public function avanzaDocumento(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::avanzaDocumento((int) $this->campo('documento', 20), $this->campo('cosa', 20)), 'Documento aggiornato.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    public function firma(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::aggiungiFirma((int) $this->campo('documento', 20), $this->campo('parte', 20), $this->campo('firmato_il', 10), $_FILES['prova'] ?? null, $this->campo('email', 254)),
            'Prova di firma registrata: va verificata.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    public function verificaFirma(): void
    {
        $id = $this->idFascicolo();
        $this->fai(fn () => Atlante::verificaFirma((int) $this->campo('firma', 20)), 'Firma verificata.', 'atlante/fascicolo', ['id' => $id]);
        $this->vai('atlante/fascicolo', ['id' => $id]);
    }

    /* ---------- Modelli MASTER ---------- */

    public function modello(): void
    {
        $this->soloPost();
        $this->fai(fn () => Atlante::creaModello([
            'codice' => $this->campo('codice', 80),
            'titolo' => $this->campo('titolo', 240),
            'categoria' => $this->campo('categoria', 30),
            'destinatario' => $this->campo('destinatario', 30),
            'condizione_utilizzo' => $this->campo('condizione_utilizzo', 220),
        ]), 'Modello aggiunto al catalogo.', 'atlante/modelli');
        $this->vai('atlante/modelli');
    }

    public function versione(): void
    {
        $this->soloPost();
        $this->fai(fn () => Atlante::creaVersione((int) $this->campo('modello', 20), $this->campo('versione', 30), $_FILES['file'] ?? null), 'Versione caricata in bozza.', 'atlante/modelli');
        $this->vai('atlante/modelli');
    }

    public function statoVersione(): void
    {
        $this->soloPost();
        $this->fai(fn () => Atlante::statoVersione((int) $this->campo('versione', 20), $this->campo('stato', 20)), 'Versione aggiornata.', 'atlante/modelli');
        $this->vai('atlante/modelli');
    }

    /* ---------- Funzioni interne ---------- */

    private function idCliente(): int
    {
        $this->soloPost();
        return (int) $this->campo('cliente', 20);
    }

    private function idFascicolo(): int
    {
        $this->soloPost();
        return (int) $this->campo('fascicolo', 20);
    }

    private function torna(int $cliente, string $ancora = ''): void
    {
        $this->vai('atlante/cliente', ['id' => $cliente], $ancora);
    }

    /** Esegue l'operazione: se va bene imposta l'avviso, altrimenti mostra l'errore e torna alla pagina indicata. */
    private function fai(callable $operazione, string $riuscita, string $ritorno, array $parametri = [], string $ancora = ''): mixed
    {
        try {
            $esito = $operazione();
            $this->avviso($riuscita);
            return $esito;
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        }
        $this->vai($ritorno, $parametri, $ancora);
    }
}
