<?php
/**
 * Area politico: il candidato o l'eletto aggiorna la propria pagina pubblica.
 * Due scenari indipendenti: "candidatura" (proposte) e "mandato" (atti e iniziative).
 * Le modifiche compaiono subito nella sezione Politica del sito.
 */
declare(strict_types=1);

final class PoliticoController extends PortaleController
{
    protected string $area = 'politico';

    public function index(): void
    {
        $politico = $this->scheda();
        if ($politico === null) {
            $this->pagina404('Scheda politica non trovata.');
            return;
        }
        $scenario = $this->tra($this->parametro('scenario'), array_keys(Politico::SCENARI), 'candidatura');
        $this->voce = $scenario;

        $this->vista('politico', [
            'titolo' => Politico::SCENARI[$scenario] . ' · ' . $politico['nome'] . ' · WolfPress',
            'politico' => $politico,
            'scenario' => $scenario,
            'dati' => $politico[$scenario],
            'livello' => Politico::livello($politico),
            'partiti' => Partito::nomi(),
        ]);
    }

    /* ---------- Articoli del politico ---------- */
    public function articoli(): void
    {
        $politico = $this->scheda();
        if ($politico === null) {
            $this->pagina404('Scheda politica non trovata.');
            return;
        }
        $this->voce = 'articoli';
        $vuoto = ['id' => '', 'titolo' => '', 'categoria' => '', 'sommario' => '', 'corpo' => '', 'stato' => 'Bozza'];
        $this->vista('politico_articoli', [
            'titolo' => 'I tuoi articoli · WolfPress',
            'articoli' => ArticoloPolitico::perPolitico($politico['db_id']),
            'articolo' => ArticoloPolitico::trova($this->parametro('id', 20), $politico['db_id']) ?? $vuoto,
        ]);
    }

    public function salvaArticolo(): void
    {
        [$politico] = $this->richiesta();
        $id = $this->campo('id', 20);
        $dati = [
            'id' => $id,
            'titolo' => $this->campo('titolo', 220),
            'categoria' => $this->campo('categoria', 120),
            'sommario' => $this->campo('sommario', 500),
            'corpo' => $this->campo('corpo', 20000),
            'stato' => $this->tra($this->campo('stato', 20), ArticoloPolitico::STATI, 'Bozza'),
        ];
        if ($dati['titolo'] === '' || $dati['corpo'] === '' || ($id !== '' && ArticoloPolitico::trova($id, $politico['db_id']) === null)) {
            $this->avviso('Titolo e testo sono obbligatori.', 'errore');
            $this->vai('politico/articoli', $id !== '' ? ['id' => $id] : []);
        }
        ArticoloPolitico::salva($dati, $politico['db_id']);
        $this->avviso($dati['stato'] === 'Pubblicato' ? 'Articolo pubblicato: è visibile nella tua scheda sul sito.' : 'Bozza salvata.');
        $this->vai('politico/articoli');
    }

    public function eliminaArticolo(): void
    {
        [$politico] = $this->richiesta();
        ArticoloPolitico::elimina($this->campo('id', 20), $politico['db_id']);
        $this->avviso('Articolo eliminato.');
        $this->vai('politico/articoli');
    }

    public function salva(): void
    {
        [$politico, $scenario] = $this->richiesta();
        $email = $this->campo('email', 120);
        $ruolo = $this->campo('ruolo', 80);

        if ($ruolo === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->avviso('Il ruolo è obbligatorio e l\'email deve essere valida.', 'errore');
            $this->vai('politico', ['scenario' => $scenario]);
        }

        $politico['bio'] = $this->campo('bio', 400);
        // Il partito si sceglie tra quelli inseriti dai tecnici (oppure nessuno).
        $partiti = Partito::nomi();
        $partito = $this->campo('partito', 160);
        if (isset($_POST['partito']) && ($partito === '' || isset($partiti[$partito]))) {
            $politico['partito'] = $partito;
            $politico['partito_nome'] = $partiti[$partito] ?? '';
        }
        $politico[$scenario]['ruolo'] = $ruolo;
        $politico[$scenario]['luogo'] = $this->campo('luogo', 80);
        $politico[$scenario]['contatto'] = $this->campo('contatto', 80);
        $politico[$scenario]['email'] = $email;
        if ($scenario === 'mandato') {
            $politico[$scenario]['incarico'] = $this->campo('incarico', 160);
        }
        Politico::salva($politico);
        $this->avviso('Pagina di ' . mb_strtolower(Politico::SCENARI[$scenario]) . ' aggiornata.');
        $this->vai('politico', ['scenario' => $scenario]);
    }

    /** Aggiunge una proposta (candidatura) oppure un atto (mandato). */
    public function aggiungiVoce(): void
    {
        [$politico, $scenario] = $this->richiesta();
        $titolo = $this->campo('titolo', 120);
        if ($titolo === '') {
            $this->avviso('Il titolo è obbligatorio.', 'errore');
            $this->vai('politico', ['scenario' => $scenario], 'voci');
        }
        $voce = ['titolo' => $titolo, 'testo' => $this->campo('testo', 500)];
        if ($scenario === 'mandato') {
            $voce['tipo'] = $this->campo('tipo', 60) ?: 'Atto';
            $voce['stato'] = $this->campo('stato', 60) ?: 'Presentato';
        }
        $politico[$scenario]['voci'][] = $voce;
        Politico::salva($politico);
        $this->avviso($scenario === 'mandato' ? 'Atto aggiunto al rendiconto.' : 'Proposta aggiunta al programma.');
        $this->vai('politico', ['scenario' => $scenario], 'voci');
    }

    public function rimuoviVoce(): void
    {
        [$politico, $scenario] = $this->richiesta();
        $indice = (int) $this->campo('indice', 4);
        if (isset($politico[$scenario]['voci'][$indice])) {
            array_splice($politico[$scenario]['voci'], $indice, 1);
            Politico::salva($politico);
            $this->avviso('Voce rimossa.');
        }
        $this->vai('politico', ['scenario' => $scenario], 'voci');
    }

    public function aggiungiEvento(): void
    {
        [$politico, $scenario] = $this->richiesta();
        $titolo = $this->campo('titolo', 80);
        $data = $this->campo('data', 10);
        if ($titolo === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || strtotime($data) === false) {
            $this->avviso('Per l\'agenda servono titolo e data.', 'errore');
            $this->vai('politico', ['scenario' => $scenario], 'agenda');
        }
        // L'appuntamento è un evento pubblico organizzato dal politico (tabelle degli eventi).
        $ora = $this->campo('ora', 5);
        Http::imposta(array_filter([
            'titolo' => $titolo,
            'luogo' => $this->campo('dettaglio', 80),
            'inizio' => $data . ' ' . (preg_match('/^\d{2}:\d{2}$/', $ora) ? $ora : '00:00'),
            'pubblico' => true,
        ], static fn ($v): bool => $v !== ''));
        try {
            Api::creaEvento();
            $this->avviso('Appuntamento aggiunto all\'agenda. Da «Eventi» puoi invitare politici e giornalisti.');
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        }
        $this->vai('politico', ['scenario' => $scenario], 'agenda');
    }

    public function rimuoviEvento(): void
    {
        [$politico, $scenario] = $this->richiesta();
        try {
            Api::annullaEvento((int) $this->campo('id', 20));
            $this->avviso('Appuntamento rimosso.');
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        }
        $this->vai('politico', ['scenario' => $scenario], 'agenda');
    }

    /** Scheda del politico collegato: ognuno modifica solo la propria. */
    private function scheda(): ?array
    {
        return Politico::trova(Auth::profilo());
    }

    /** Controlli comuni alle azioni di modifica: restituisce [scheda, scenario]. */
    private function richiesta(): array
    {
        $this->soloPost();
        $politico = $this->scheda();
        if ($politico === null) {
            $this->avviso('Scheda politica non trovata.', 'errore');
            $this->vai('home');
        }
        $scenario = $this->tra($this->campo('scenario', 20), array_keys(Politico::SCENARI), 'candidatura');
        return [$politico, $scenario];
    }
}
