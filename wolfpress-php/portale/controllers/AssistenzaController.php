<?php
/**
 * Assistenza editore: apertura dei ticket e chat con il fornitore.
 * L'editore vede soltanto i ticket della propria testata.
 */
declare(strict_types=1);

final class AssistenzaController extends PortaleController
{
    protected string $area = 'assistenza';
    protected string $voce = 'ticket';

    public function index(): void
    {
        $ticket = Ticket::perTestata(Testata::PRINCIPALE);
        $nuovo = $this->parametro('nuovo') === '1';

        $this->vista('assistenza/ticket', [
            'titolo' => 'Assistenza editore · WolfPress',
            'ticket' => $ticket,
            'conteggio' => Ticket::conta($ticket),
            'scelto' => $nuovo ? null : $this->scelto($ticket),
            'nuovo' => $nuovo || !$ticket,
        ]);
    }

    public function crea(): void
    {
        $this->soloPost();
        $mittente = $this->campo('mittente', 80);
        $oggetto = $this->campo('oggetto', 120);
        $descrizione = $this->campo('descrizione', 1200);
        $priorita = $this->tra($this->campo('priorita', 20), Ticket::PRIORITA, 'Normale');

        if ($oggetto === '' || $descrizione === '') {
            $this->avviso('Oggetto e descrizione sono obbligatori.', 'errore');
            $this->vai('assistenza', ['nuovo' => '1']);
        }

        $ticket = Ticket::apri(Testata::PRINCIPALE, $mittente !== '' ? $mittente : Auth::nome(), $oggetto, $priorita, $descrizione);
        $this->avviso('Ticket ' . $ticket['id'] . ' aperto: il team tecnico lo prenderà in carico.');
        $this->vai('assistenza', ['id' => $ticket['id']]);
    }

    public function chat(): void
    {
        $this->voce = 'chat';
        $ticket = Ticket::perTestata(Testata::PRINCIPALE);

        $this->vista('assistenza/chat', [
            'titolo' => 'Chat di assistenza · WolfPress',
            'ticket' => $ticket,
            'scelto' => $this->scelto($ticket),
        ]);
    }

    public function messaggio(): void
    {
        $this->soloPost();
        $ticket = Ticket::trova($this->campo('id', 40));
        $testo = $this->campo('testo', 600);

        if ($ticket === null || $ticket['testata'] !== Testata::PRINCIPALE) {
            $this->avviso('Ticket non trovato.', 'errore');
            $this->vai('assistenza/chat');
        }
        if ($testo !== '') {
            Ticket::aggiungiMessaggio($ticket['id'], Auth::nome(), 'editore', $testo);
        }
        $this->vai('assistenza/chat', ['id' => $ticket['id']]);
    }

    /** Ticket selezionato con ?id=..., altrimenti il primo dell'elenco. */
    private function scelto(array $ticket): ?array
    {
        $id = $this->parametro('id');
        foreach ($ticket as $t) {
            if ($t['id'] === $id) {
                return $t;
            }
        }
        return $ticket[0] ?? null;
    }
}
