<?php
/**
 * Testate dell'editore: creazione (la prima è obbligatoria: finché non ne ha
 * una l'editore viene sempre riportato qui) e scelta di quella su cui lavorare.
 */
declare(strict_types=1);

final class TestataController extends PortaleController
{
    protected string $area = 'testata';
    protected string $voce = 'nuova';

    public function index(): void
    {
        $this->vista('testata', ['titolo' => 'Nuova testata · WolfPress', 'prima' => Auth::testata() === '']);
    }

    /** L'editore con più testate sceglie su quale lavorare. */
    public function scegli(): void
    {
        $this->soloPost();
        if (Auth::scegliTestata($this->campo('testata', 160))) {
            $this->avviso('Ora lavori su ' . Testata::nome(Auth::testata()) . '.');
        }
        $this->vai('cliente');
    }

    public function crea(): void
    {
        $this->soloPost();
        Http::imposta(array_filter([
            'nome' => $this->campo('nome', 160),
            'email' => $this->campo('email', 254),
            'descrizione' => $this->campo('descrizione', 2000),
            'dominio' => $this->campo('dominio', 190),
        ], static fn (string $v): bool => $v !== ''));
        try {
            $nuova = Api::creaGiornale();
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
            $this->vai('testata');
        } catch (PDOException $errore) {
            $this->avviso(Database::doppione($errore) ? 'Esiste già una testata con questo nome: scegline un altro.' : 'Impossibile creare la testata.', 'errore');
            $this->vai('testata');
        }
        Auth::aggiorna();
        Auth::scegliTestata($nuova['slug']);
        $this->avviso('Testata creata: ora puoi configurarla e invitare i giornalisti.');
        $this->vai('configuratore');
    }
}
