<?php
/**
 * Pagine informative del sito (testo fisso).
 */
declare(strict_types=1);

final class PagineController extends SitoController
{
    public function chiSiamo(): void
    {
        $this->voce = 'chi-siamo';
        $this->pagina('Chi siamo', [
            'WolfPress è una piattaforma per giornali digitali indipendenti: il fornitore gestisce la tecnologia, ogni editore gestisce in autonomia la propria testata.',
            'Su questo sito i lettori trovano le notizie pubblicate dalle testate ospitate e le schede di partiti, candidati ed eletti, organizzate per livello istituzionale.',
            'Redazioni, politici e tecnici lavorano invece nel portale riservato, dove scrivono gli articoli, aggiornano le schede e seguono l\'assistenza.',
        ]);
    }

    public function privacy(): void
    {
        $this->pagina('Privacy', [
            'Questo sito dimostrativo non usa cookie di profilazione, non incorpora tracciatori e non carica risorse da servizi esterni.',
            'Il sito pubblico non richiede alcuna registrazione. Il portale riservato usa un solo cookie tecnico di sessione, necessario per l\'accesso.',
            'Prima dell\'uso reale servono un titolare del trattamento, un\'informativa completa e la valutazione dei servizi di hosting.',
        ]);
    }

    public function accessibilita(): void
    {
        $this->pagina('Accessibilità', [
            'Il sito è costruito con struttura semantica, navigazione da tastiera, contrasti leggibili e rispetto della preferenza di movimento ridotto.',
            'È ancora necessaria una verifica completa WCAG 2.2 AA con tecnologie assistive prima della pubblicazione.',
        ]);
    }

    private function pagina(string $titolo, array $paragrafi): void
    {
        $this->vista('pagina', [
            'titolo' => $titolo . ' · WolfPress',
            'intestazione' => $titolo,
            'paragrafi' => $paragrafi,
        ]);
    }
}
