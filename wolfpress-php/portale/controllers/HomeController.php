<?php
/**
 * Pagina iniziale del portale: mostra le aree disponibili per il ruolo scelto.
 */
declare(strict_types=1);

final class HomeController extends PortaleController
{
    protected string $area = 'home';
    protected string $voce = 'home';

    public function index(): void
    {
        $aree = [
            'editore' => [
                ['Configuratore', 'Imposta nome, colore, logo e moduli visibili della testata.', 'bi-sliders', url('configuratore')],
                ['Area cliente', 'Scrivania editoriale, letture e canali social.', 'bi-grid', url('cliente')],
                ['Area editore', 'Account dei giornalisti e chat di redazione.', 'bi-people', url('editore')],
                ['Assistenza', 'Apri un ticket e segui le risposte del fornitore.', 'bi-chat-left-text', url('assistenza')],
            ],
            'giornalista' => [
                ['Profilo giornalista', 'Portfolio, eventi in agenda e chat di redazione.', 'bi-person-badge', url('giornalista')],
                ['I miei articoli', 'Scrivi una bozza e mandala in revisione.', 'bi-file-earmark-text', url('articoli')],
            ],
            'politico' => [
                ['Candidatura', 'Profilo, proposte, agenda e contatti della campagna.', 'bi-megaphone', url('politico', ['scenario' => 'candidatura'])],
                ['Mandato', 'Incarichi, atti e iniziative da rendicontare.', 'bi-bank', url('politico', ['scenario' => 'mandato'])],
            ],
            'tecnico' => [
                ['Infrastruttura', 'Testate ospitate, domini, backup e incidenti.', 'bi-hdd-network', url('tecnico')],
                ['Personale tecnico', 'Operatori, ruoli e turni.', 'bi-person-gear', url('tecnico/personale')],
                ['Clienti e testate', 'Referenti, piano e canali social.', 'bi-building', url('tecnico/clienti')],
                ['Referente cliente', 'Storico delle segnalazioni per cliente.', 'bi-person-lines-fill', url('tecnico/referente')],
                ['Ticket di assistenza', 'Prendi in carico e rispondi.', 'bi-ticket-perforated', url('tecnico/ticket')],
                ['Chat di assistenza', 'Conversazioni legate ai ticket.', 'bi-chat-dots', url('tecnico/chat')],
            ],
        ];

        $this->vista('home', [
            'titolo' => 'Le tue aree · WolfPress',
            'aree' => $aree[Auth::ruolo()] ?? [],
        ]);
    }
}
