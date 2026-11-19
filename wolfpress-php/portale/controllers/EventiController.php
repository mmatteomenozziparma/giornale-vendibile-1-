<?php
/**
 * Eventi: li organizza un politico, una testata o entrambi insieme.
 * Gli organizzatori invitano politici e giornalisti; possono partecipare
 * politici, giornalisti e (dal sito) i lettori.
 * Le regole su chi può fare cosa sono nel backend: classe Api.
 */
declare(strict_types=1);

final class EventiController extends PortaleController
{
    protected string $area = 'eventi';
    protected string $voce = 'elenco';

    public function index(): void
    {
        $organizza = in_array(Auth::ruolo(), ['politico', 'editore'], true);
        $this->vista('eventi/elenco', [
            'titolo' => 'Eventi · WolfPress',
            'puoCreare' => $organizza,
            'organizzati' => Evento::organizzati(),
            'inviti' => Evento::inviti(),
            'partecipazioni' => Evento::partecipazioni(),
            'politici' => $organizza ? array_diff_key(Evento::politici(), Auth::ruolo() === 'politico' ? [Auth::profiloId() => 1] : []) : [],
            'testate' => $organizza ? array_column(Testata::tutti(), 'nome', 'db_id') : [],
            'miaTestata' => Testata::dbId(Auth::testata()),
        ]);
    }

    public function crea(): void
    {
        $this->soloPost();
        $data = $this->campo('data', 10);
        $ora = $this->campo('ora', 5);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || strtotime($data) === false || ($ora !== '' && !preg_match('/^\d{2}:\d{2}$/', $ora))) {
            $this->avviso('Per creare un evento servono titolo e data.', 'errore');
            $this->vai('eventi', [], 'nuovo');
        }
        $giornali = $this->elenco('testate');
        // L'evento di un editore è sempre organizzato (anche) dalla testata su cui sta lavorando.
        if (Auth::ruolo() === 'editore' && !in_array(Testata::dbId(Auth::testata()), $giornali, true)) {
            $giornali[] = Testata::dbId(Auth::testata());
        }
        $esito = $this->esegui([
            'titolo' => $this->campo('titolo', 220),
            'descrizione' => $this->campo('descrizione', 5000),
            'luogo' => $this->campo('luogo', 220),
            'inizio' => $data . ' ' . ($ora !== '' ? $ora : '00:00'),
            'pubblico' => $this->campo('pubblico', 1) === '1',
            'politici_organizzatori' => $this->elenco('politici'),
            'giornali_organizzatori' => $giornali,
        ], static fn (): array => Api::creaEvento(), 'eventi');
        $this->avviso('Evento creato. Ora puoi invitare politici e giornalisti.');
        $this->vai('eventi/scheda', ['id' => $esito['id']]);
    }

    public function scheda(): void
    {
        $evento = Evento::scheda($this->parametro('id', 20));
        if ($evento === null) {
            $this->pagina404('Evento non trovato o riservato.');
            return;
        }
        // Si possono invitare solo le persone non ancora invitate.
        $gia = ['politico_id' => [], 'giornalista_id' => []];
        foreach ($evento['invitati'] as $i) {
            $gia[$i['politico_id'] !== null ? 'politico_id' : 'giornalista_id'][(int) ($i['politico_id'] ?? $i['giornalista_id'])] = 1;
        }
        $this->vista('eventi/scheda', [
            'titolo' => $evento['titolo'] . ' · WolfPress',
            'evento' => $evento,
            'politici' => $evento['organizzo'] ? array_diff_key(Evento::politici(), $gia['politico_id']) : [],
            'giornalisti' => $evento['organizzo'] ? array_diff_key(Evento::giornalisti(), $gia['giornalista_id']) : [],
            'puoPartecipare' => in_array(Auth::ruolo(), ['politico', 'giornalista'], true),
        ]);
    }

    /** Accetta di co-organizzare un evento creato da un altro. */
    public function conferma(): void
    {
        $id = $this->idEvento();
        $this->esegui([], static fn (): array => Api::confermaOrganizzazione($id), 'eventi/scheda', ['id' => $id]);
        $this->avviso('Ora sei tra gli organizzatori dell\'evento.');
        $this->vai('eventi/scheda', ['id' => $id]);
    }

    public function invita(): void
    {
        $id = $this->idEvento();
        $esito = $this->esegui(
            ['politici' => $this->elenco('politici'), 'giornalisti' => $this->elenco('giornalisti')],
            static fn (): array => Api::invita($id),
            'eventi/scheda',
            ['id' => $id]
        );
        $this->avviso($esito['invitati'] === 0 ? 'Nessun nuovo invito da spedire.' : 'Inviti spediti per email: ' . $esito['invitati'] . '.');
        $this->vai('eventi/scheda', ['id' => $id], 'invitati');
    }

    /** Il politico o il giornalista invitato accetta o rifiuta. */
    public function rispondi(): void
    {
        $id = $this->idEvento();
        $accetta = $this->campo('risposta', 10) === 'accetta';
        $this->esegui(['accetta' => $accetta], static fn (): array => Api::rispondiInvito($id), 'eventi');
        $this->avviso($accetta ? 'Invito accettato: l\'evento è nella tua agenda.' : 'Invito rifiutato.');
        $this->vai('eventi');
    }

    public function partecipa(): void
    {
        $id = $this->idEvento();
        $this->esegui([], static fn (): array => Api::partecipa($id), 'eventi/scheda', ['id' => $id]);
        $this->avviso('Partecipazione registrata.');
        $this->vai('eventi/scheda', ['id' => $id]);
    }

    public function annulla(): void
    {
        $id = $this->idEvento();
        $this->esegui([], static fn (): array => Api::annullaEvento($id), 'eventi/scheda', ['id' => $id]);
        $this->avviso('Evento annullato.');
        $this->vai('eventi');
    }

    private function idEvento(): int
    {
        $this->soloPost();
        return (int) $this->campo('id', 20);
    }

    /** Numeri scelti in una selezione multipla del form (name="campo[]"). */
    private function elenco(string $campo): array
    {
        $valori = is_array($_POST[$campo] ?? null) ? $_POST[$campo] : [];
        $numeri = [];
        foreach (array_slice($valori, 0, 100) as $valore) {
            if (is_string($valore) && ctype_digit($valore) && (int) $valore > 0) {
                $numeri[] = (int) $valore;
            }
        }
        return array_values(array_unique($numeri));
    }

    /** Passa i dati al backend; in caso di errore mostra il messaggio e torna alla pagina indicata. */
    private function esegui(array $dati, callable $operazione, string $ritorno, array $parametri = []): array
    {
        Http::imposta(array_filter($dati, static fn ($v): bool => $v !== ''));
        try {
            return $operazione();
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        } catch (PDOException $errore) {
            $this->avviso('Uno degli elementi indicati non esiste.', 'errore');
        }
        $this->vai($ritorno, $parametri);
    }
}
