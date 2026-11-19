<?php
/**
 * Base dei controller del sito pubblico: prepara i dati del menu,
 * gli avvisi e il controllo dei form.
 */
declare(strict_types=1);

abstract class SitoController extends Controller
{
    /** Voce del menu principale da evidenziare (vedi views/menu.php). */
    protected string $voce = '';

    protected function datiComuni(): array
    {
        $sessione = session_status() === PHP_SESSION_ACTIVE;
        // Se il database non risponde la pagina (di errore) si vede lo stesso, con menu e footer.
        try {
            $sezioni = Articolo::sezioni();
            $utente = Auth::utente();
        } catch (Throwable) {
            $sezioni = [];
            $utente = null;
        }
        return [
            'voce' => $this->voce,
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
