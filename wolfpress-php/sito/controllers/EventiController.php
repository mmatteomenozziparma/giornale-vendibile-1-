<?php
/**
 * Eventi pubblici organizzati da politici e testate.
 * I lettori (e i politici e giornalisti collegati) possono segnare la propria partecipazione.
 */
declare(strict_types=1);

final class EventiController extends SitoController
{
    protected string $voce = 'eventi';
    protected string $contesto = 'atlante';

    public function index(): void
    {
        $miei = array_column(Evento::partecipazioni(), 'id');
        $this->vista('eventi', [
            'titolo' => 'Eventi · WolfPress',
            'eventi' => Evento::pubblici(),
            'miei' => $miei,
            'puoPartecipare' => in_array(Auth::ruolo(), ['user', 'politico', 'giornalista'], true),
        ]);
    }

    public function partecipa(): void
    {
        $this->soloPost();
        $id = (int) $this->campo('id', 20);
        Http::imposta([]);
        try {
            Api::partecipa($id);
            $this->avviso('Partecipazione registrata: trovi l\'evento nel tuo account.');
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        }
        $this->vai('eventi', [], 'evento-' . $id);
    }
}
