<?php
/**
 * Base dei controller del portale: aggiunge menu laterale, avvisi e controllo dei form.
 */
declare(strict_types=1);

abstract class PortaleController extends Controller
{
    /** Area corrente: decide quale menu laterale mostrare (vedi views/menu.php). */
    protected string $area = 'home';

    /** Voce del menu laterale da evidenziare. */
    protected string $voce = '';

    protected function datiComuni(): array
    {
        return [
            'area' => $this->area,
            'voce' => $this->voce,
            'utente' => Auth::utente(),
            'impostazioni' => Impostazioni::leggi(),
            'avviso' => Auth::leggiAvviso(),
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
