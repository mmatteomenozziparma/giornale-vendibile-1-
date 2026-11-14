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
            $this->pagina404('Scheda politica non trovata: scegli di nuovo il profilo demo.');
            return;
        }
        $scenario = $this->tra($this->parametro('scenario'), array_keys(Politico::SCENARI), 'candidatura');
        $this->voce = $scenario;

        $this->vista('politico', [
            'titolo' => Politico::SCENARI[$scenario] . ' · ' . $politico['nome'] . ' · WolfPress',
            'politico' => $politico,
            'scenario' => $scenario,
            'dati' => $politico[$scenario],
            'livello' => Politico::LIVELLI[$politico['id']],
        ]);
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
        $politico[$scenario]['agenda'][] = ['data' => $data, 'titolo' => $titolo, 'dettaglio' => $this->campo('dettaglio', 80)];
        usort($politico[$scenario]['agenda'], static fn (array $a, array $b): int => strcmp($a['data'], $b['data']));
        Politico::salva($politico);
        $this->avviso('Appuntamento aggiunto all\'agenda.');
        $this->vai('politico', ['scenario' => $scenario], 'agenda');
    }

    public function rimuoviEvento(): void
    {
        [$politico, $scenario] = $this->richiesta();
        $indice = (int) $this->campo('indice', 4);
        if (isset($politico[$scenario]['agenda'][$indice])) {
            array_splice($politico[$scenario]['agenda'], $indice, 1);
            Politico::salva($politico);
            $this->avviso('Appuntamento rimosso.');
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
