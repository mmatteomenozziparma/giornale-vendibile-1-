<?php
/**
 * Articoli: scrittura e flusso editoriale (Bozza -> In revisione -> Pubblicato).
 * Il giornalista scrive e manda in revisione, l'editore approva e pubblica.
 * Gli articoli pubblicati compaiono subito sul sito pubblico.
 */
declare(strict_types=1);

final class ArticoliController extends PortaleController
{
    public function __construct()
    {
        $editore = Auth::ruolo() === 'editore';
        $this->area = $editore ? 'cliente' : 'giornalista';
        $this->voce = 'articoli';
    }

    /** Elenco degli articoli del giornalista (l'editore usa la scrivania dell'area cliente). */
    public function index(): void
    {
        if (Auth::ruolo() === 'editore') {
            $this->vai('cliente', [], 'articoli');
        }
        $miei = array_values(array_filter(
            Articolo::perTestata(Testata::PRINCIPALE),
            static fn (array $a): bool => $a['autore'] === Auth::nome()
        ));
        $this->vista('articoli_elenco', ['titolo' => 'I miei articoli · WolfPress', 'articoli' => $miei]);
    }

    public function nuovo(): void
    {
        $this->vista('articoli_modulo', [
            'titolo' => 'Nuovo articolo · WolfPress',
            'articolo' => ['id' => '', 'titolo' => '', 'sezione' => '', 'sommario' => '', 'corpo' => '', 'stato' => 'Bozza', 'autore' => Auth::nome(), 'slug' => ''],
            'stati' => $this->statiAmmessi(),
            'modificabile' => true,
        ]);
    }

    public function modifica(): void
    {
        $articolo = Articolo::trova($this->parametro('id'));
        if ($articolo === null || !$this->visibile($articolo)) {
            $this->pagina404('Articolo non trovato.');
            return;
        }
        $this->vista('articoli_modulo', [
            'titolo' => $articolo['titolo'] . ' · WolfPress',
            'articolo' => $articolo,
            'stati' => $this->statiAmmessi(),
            'modificabile' => $this->modificabile($articolo),
        ]);
    }

    public function salva(): void
    {
        $this->soloPost();
        $id = $this->campo('id', 40);
        $esistente = $id !== '' ? Articolo::trova($id) : null;

        if ($id !== '' && ($esistente === null || !$this->modificabile($esistente))) {
            $this->avviso('Non puoi modificare questo articolo.', 'errore');
            $this->vai('articoli');
        }

        $titolo = $this->campo('titolo', 120);
        $sezione = $this->campo('sezione', 40);
        $corpo = $this->campo('corpo', 12000);
        $stato = $this->tra($this->campo('stato', 20), $this->statiAmmessi(), 'Bozza');

        if ($titolo === '' || $sezione === '') {
            $this->avviso('Titolo e sezione sono obbligatori.', 'errore');
            $this->vai($id !== '' ? 'articoli/modifica' : 'articoli/nuovo', $id !== '' ? ['id' => $id] : []);
        }

        $dati = [
            'titolo' => $titolo,
            'sezione' => $sezione,
            'sommario' => $this->campo('sommario', 240),
            'corpo' => $corpo,
            'stato' => $stato,
        ];

        if ($esistente !== null) {
            Articolo::salva($dati + $esistente);
        } else {
            Articolo::crea($dati + ['testata' => Testata::PRINCIPALE, 'autore' => Auth::nome()]);
        }

        $messaggi = [
            'Bozza' => 'Bozza salvata.',
            'In revisione' => 'Articolo inviato in revisione.',
            'Pubblicato' => 'Articolo pubblicato: ora è visibile sul sito.',
        ];
        $this->avviso($messaggi[$stato]);
        $this->tornaAllElenco();
    }

    public function elimina(): void
    {
        $this->soloPost();
        $articolo = Articolo::trova($this->campo('id', 40));
        if ($articolo === null || !$this->modificabile($articolo)) {
            $this->avviso('Non puoi eliminare questo articolo.', 'errore');
        } else {
            Articolo::elimina((string) $articolo['id']);
            $this->avviso('Articolo eliminato.');
        }
        $this->tornaAllElenco();
    }

    private function tornaAllElenco(): void
    {
        if (Auth::ruolo() === 'editore') {
            $this->vai('cliente', [], 'articoli');
        }
        $this->vai('articoli');
    }

    /** L'editore pubblica; il giornalista può solo salvare la bozza o chiedere la revisione. */
    private function statiAmmessi(): array
    {
        return Auth::ruolo() === 'editore' ? Articolo::STATI : ['Bozza', 'In revisione'];
    }

    private function visibile(array $articolo): bool
    {
        if ($articolo['testata'] !== Testata::PRINCIPALE) {
            return false;
        }
        return Auth::ruolo() === 'editore' || $articolo['autore'] === Auth::nome();
    }

    private function modificabile(array $articolo): bool
    {
        if (!$this->visibile($articolo)) {
            return false;
        }
        return Auth::ruolo() === 'editore' || $articolo['stato'] !== 'Pubblicato';
    }
}
