<?php
/**
 * Gestione tecnica: la console degli ingegneri dell'azienda fornitrice.
 * Infrastruttura, personale, clienti e testate, referente cliente, ticket e chat.
 */
declare(strict_types=1);

final class TecnicoController extends PortaleController
{
    protected string $area = 'tecnico';
    protected string $voce = 'infrastruttura';

    /* ---------- Infrastruttura ---------- */

    public function index(): void
    {
        $testate = Testata::tutti();
        $this->vista('tecnico/infrastruttura', [
            'titolo' => 'Infrastruttura · WolfPress',
            'testate' => $testate,
            'scelta' => $this->testataScelta($testate),
            'operative' => Testata::operative(),
            'nonRisolti' => Ticket::nonRisolti(),
        ]);
    }

    public function salvaStato(): void
    {
        $this->soloPost();
        $testata = Testata::trova($this->campo('id', 40));
        if ($testata === null) {
            $this->avviso('Testata non trovata.', 'errore');
            $this->vai('tecnico');
        }
        $testata['stato'] = $this->tra($this->campo('stato', 20), Testata::STATI, $testata['stato']);
        $testata['incidente'] = $this->campo('incidente', 160);
        Testata::salva($testata);
        $this->avviso('Stato di ' . $testata['nome'] . ' aggiornato: ' . $testata['stato'] . '.');
        $this->vai('tecnico', ['id' => $testata['id']]);
    }

    /* ---------- Personale tecnico ---------- */

    public function personale(): void
    {
        $this->voce = 'personale';
        $operatore = Operatore::trova($this->parametro('id'));
        $this->vista('tecnico/personale', [
            'titolo' => 'Personale tecnico · WolfPress',
            'operatori' => Operatore::tutti(),
            'operatore' => $operatore ?? ['id' => '', 'nome' => '', 'ruolo' => Operatore::RUOLI[0], 'turno' => '', 'stato' => 'Attivo'],
        ]);
    }

    public function salvaOperatore(): void
    {
        $this->soloPost();
        $id = $this->campo('id', 40);
        $esistente = $id !== '' ? Operatore::trova($id) : null;
        $dati = [
            'nome' => $this->campo('nome', 80),
            'ruolo' => $this->tra($this->campo('ruolo', 40), Operatore::RUOLI, Operatore::RUOLI[0]),
            'turno' => $this->campo('turno', 40),
            'stato' => $this->tra($this->campo('stato', 20), Operatore::STATI, 'Attivo'),
        ];
        if ($dati['nome'] === '' || ($id !== '' && $esistente === null)) {
            $this->avviso('Il nome dell\'operatore è obbligatorio.', 'errore');
            $this->vai('tecnico/personale');
        }
        if ($esistente !== null) {
            Operatore::salva($dati + $esistente);
        } else {
            Operatore::crea($dati);
        }
        $this->avviso('Operatore ' . $dati['nome'] . ' salvato.');
        $this->vai('tecnico/personale');
    }

    /* ---------- Clienti e testate ---------- */

    public function clienti(): void
    {
        $this->voce = 'clienti';
        $testate = Testata::tutti();
        $this->vista('tecnico/clienti', [
            'titolo' => 'Clienti e testate · WolfPress',
            'testate' => $testate,
            'scelta' => $this->testataScelta($testate),
            'nonRisolti' => Ticket::nonRisolti(),
        ]);
    }

    public function salvaCliente(): void
    {
        $this->soloPost();
        $testata = Testata::trova($this->campo('id', 40));
        if ($testata === null) {
            $this->avviso('Testata non trovata.', 'errore');
            $this->vai('tecnico/clienti');
        }
        $email = $this->campo('email', 120);
        $errori = [];
        if ($this->campo('cliente', 80) === '') {
            $errori[] = 'Il nome del cliente è obbligatorio.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errori[] = 'L\'email del referente non è valida.';
        }
        $social = [];
        foreach (Testata::SOCIAL as $chiave => $etichetta) {
            $link = $this->campo($chiave, 300);
            if ($link !== '' && !url_https_valido($link)) {
                $errori[] = 'Il link ' . $etichetta . ' deve iniziare con https://';
            }
            $social[$chiave] = $link;
        }
        if ($errori) {
            $this->avviso(implode(' ', $errori), 'errore');
            $this->vai('tecnico/clienti', ['id' => $testata['id']]);
        }
        $testata['cliente'] = $this->campo('cliente', 80);
        $testata['email'] = $email;
        $testata['piano'] = $this->tra($this->campo('piano', 20), Testata::PIANI, $testata['piano']);
        $testata['social'] = $social;
        Testata::salva($testata);
        $this->avviso('Cliente e canali social di ' . $testata['nome'] . ' salvati.');
        $this->vai('tecnico/clienti', ['id' => $testata['id']]);
    }

    /* ---------- Referente cliente e storico ---------- */

    public function referente(): void
    {
        $this->voce = 'referente';
        $testate = Testata::tutti();
        $scelta = $this->testataScelta($testate);
        $this->vista('tecnico/referente', [
            'titolo' => 'Referente cliente · WolfPress',
            'testate' => $testate,
            'scelta' => $scelta,
            'referente' => Operatore::trova((string) ($scelta['referente'] ?? '')),
            'ticket' => Ticket::perTestata($scelta['id']),
        ]);
    }

    /* ---------- Ticket di assistenza ---------- */

    public function ticket(): void
    {
        $this->voce = 'ticket';
        $ticket = Ticket::tutti();
        $this->vista('tecnico/ticket', [
            'titolo' => 'Ticket di assistenza · WolfPress',
            'ticket' => $ticket,
            'conteggio' => Ticket::conta($ticket),
            'scelto' => $this->ticketScelto($ticket),
            'operatori' => Operatore::attivi(),
        ]);
    }

    public function salvaTicket(): void
    {
        $this->soloPost();
        $ticket = Ticket::trova($this->campo('id', 40));
        if ($ticket === null) {
            $this->avviso('Ticket non trovato.', 'errore');
            $this->vai('tecnico/ticket');
        }
        $nomi = array_merge(['Team tecnico'], array_column(Operatore::attivi(), 'nome'));
        $ticket['stato'] = $this->tra($this->campo('stato', 20), Ticket::STATI, $ticket['stato']);
        $ticket['assegnatario'] = $this->tra($this->campo('assegnatario', 80), $nomi, '');
        Ticket::salva($ticket);

        $risposta = $this->campo('risposta', 600);
        if ($risposta !== '') {
            Ticket::aggiungiMessaggio($ticket['id'], Auth::nome(), 'tecnico', $risposta);
        }
        $this->avviso('Ticket ' . $ticket['id'] . ' aggiornato.');
        $this->vai('tecnico/ticket', ['id' => $ticket['id']]);
    }

    /** Assegna il ticket all'operatore collegato e lo mette in lavorazione. */
    public function prendi(): void
    {
        $this->soloPost();
        $ticket = Ticket::trova($this->campo('id', 40));
        if ($ticket === null) {
            $this->avviso('Ticket non trovato.', 'errore');
            $this->vai('tecnico/ticket');
        }
        $ticket['assegnatario'] = Auth::nome();
        if ($ticket['stato'] === 'Aperto') {
            $ticket['stato'] = 'In lavorazione';
        }
        Ticket::salva($ticket);
        $this->avviso('Hai preso in carico il ticket ' . $ticket['id'] . '.');
        $this->vai('tecnico/ticket', ['id' => $ticket['id']]);
    }

    /* ---------- Chat di assistenza ---------- */

    public function chat(): void
    {
        $this->voce = 'chat';
        $ticket = Ticket::tutti();
        $this->vista('tecnico/chat', [
            'titolo' => 'Chat di assistenza · WolfPress',
            'ticket' => $ticket,
            'scelto' => $this->ticketScelto($ticket),
        ]);
    }

    public function messaggio(): void
    {
        $this->soloPost();
        $ticket = Ticket::trova($this->campo('id', 40));
        if ($ticket === null) {
            $this->avviso('Ticket non trovato.', 'errore');
            $this->vai('tecnico/chat');
        }
        $testo = $this->campo('testo', 600);
        if ($testo !== '') {
            Ticket::aggiungiMessaggio($ticket['id'], Auth::nome(), 'tecnico', $testo);
        }
        $this->vai('tecnico/chat', ['id' => $ticket['id']]);
    }

    /** Salva (o rimuove) il link https della videochiamata legata al ticket. */
    public function video(): void
    {
        $this->soloPost();
        $ticket = Ticket::trova($this->campo('id', 40));
        if ($ticket === null) {
            $this->avviso('Ticket non trovato.', 'errore');
            $this->vai('tecnico/chat');
        }
        $link = $this->campo('link', 300);
        if ($link !== '' && !url_https_valido($link)) {
            $this->avviso('Il link della videochiamata deve iniziare con https://', 'errore');
        } else {
            $ticket['video'] = $link;
            Ticket::salva($ticket);
            $this->avviso($link === '' ? 'Link video rimosso.' : 'Link video salvato: è visibile anche all\'editore.');
        }
        $this->vai('tecnico/chat', ['id' => $ticket['id']]);
    }

    /* ---------- Funzioni interne ---------- */

    private function testataScelta(array $testate): array
    {
        $id = $this->parametro('id');
        foreach ($testate as $testata) {
            if ($testata['id'] === $id) {
                return $testata;
            }
        }
        return $testate[0];
    }

    private function ticketScelto(array $ticket): ?array
    {
        $id = $this->parametro('id');
        foreach ($ticket as $t) {
            if ($t['id'] === $id) {
                return $t;
            }
        }
        // In mancanza di scelta si apre il primo ticket ancora da prendere in carico.
        foreach ($ticket as $t) {
            if ($t['stato'] === 'Aperto') {
                return $t;
            }
        }
        return $ticket[0] ?? null;
    }
}
