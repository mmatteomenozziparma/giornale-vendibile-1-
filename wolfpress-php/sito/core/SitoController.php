<?php
/**
 * Base dei controller del sito pubblico: prepara i dati del menu,
 * gli avvisi e il controllo dei form.
 */
declare(strict_types=1);

abstract class SitoController extends Controller
{
    /** Voce del menu principale da evidenziare (vedi menu.php). */
    protected string $voce = '';

    /**
     * Contesto del menu (vedi menu.php):
     *   wolfpress  la piattaforma: home, ricerca, pagine informative, account
     *   giornale   le testate: notizie, articoli, elenco dei giornali
     *   atlante    Atlante Civico: partiti, candidati ed eletti, eventi
     */
    protected string $contesto = 'wolfpress';

    /** Slug della testata che si sta leggendo: il menu prende il suo nome e le sue sezioni. */
    protected string $testataMenu = '';

    protected function datiComuni(): array
    {
        $sessione = session_status() === PHP_SESSION_ACTIVE;
        // Se il database non risponde la pagina (di errore) si vede lo stesso, con menu e footer.
        try {
            $testata = $this->testataMenu !== '' ? Testata::trova($this->testataMenu) : null;
            // Dentro una testata il menu mostra solo le sue sezioni.
            $sezioni = $testata === null
                ? Articolo::sezioni()
                : array_values(array_unique(array_filter(array_column(Articolo::filtra('', $testata['id']), 'sezione'))));
            $partiti = $this->contesto === 'atlante' ? Partito::nomi() : [];
            $utente = Auth::utente();
        } catch (Throwable) {
            $testata = null;
            $sezioni = $partiti = [];
            $utente = null;
        }
        return [
            'voce' => $this->voce,
            'contesto' => $this->contesto,
            'testataMenu' => $testata,
            'partitiMenu' => $partiti,
            'sezioniMenu' => $sezioni,
            'ricercaCorrente' => '',
            'descrizione' => 'WolfPress: notizie, politici e partiti in un unico sito.',
            'utente' => $utente,
            'avviso' => $sessione ? Auth::leggiAvviso() : null,
        ];
    }

    /** Da chiamare all'inizio di ogni azione che modifica dati. */
    protected function soloPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !Csrf::valido()) {
            $this->vista('errore', [
                'titolo' => 'Richiesta non valida',
                'messaggio' => 'Il modulo è scaduto o non è stato inviato correttamente. Torna indietro e riprova.',
            ], 400);
            exit;
        }
    }

    protected function avviso(string $testo, string $tipo = 'ok'): void
    {
        Auth::avviso($testo, $tipo);
    }
}
