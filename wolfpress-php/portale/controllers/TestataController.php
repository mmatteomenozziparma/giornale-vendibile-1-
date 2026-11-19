<?php
/**
 * Primo avvio dell'editore: creazione della propria testata.
 * Finché non ne ha una, l'editore viene sempre riportato qui.
 */
declare(strict_types=1);

final class TestataController extends PortaleController
{
    protected string $area = 'testata';
    protected string $voce = 'nuova';

    public function index(): void
    {
        if (Auth::testata() !== '') {
            $this->vai('home');
        }
        $this->vista('testata_nuova', ['titolo' => 'Crea la tua testata · WolfPress']);
    }

    public function crea(): void
    {
        $this->soloPost();
        if (Auth::testata() !== '') {
            $this->vai('home');
        }
        Http::imposta(array_filter([
            'nome' => $this->campo('nome', 160),
            'email' => $this->campo('email', 254),
            'descrizione' => $this->campo('descrizione', 2000),
            'dominio' => $this->campo('dominio', 190),
        ], static fn (string $v): bool => $v !== ''));
        try {
            Api::creaGiornale();
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
            $this->vai('testata');
        } catch (PDOException $errore) {
            $this->avviso(Database::doppione($errore) ? 'Esiste già una testata con questo nome: scegline un altro.' : 'Impossibile creare la testata.', 'errore');
            $this->vai('testata');
        }
        Auth::aggiorna();
        $this->avviso('Testata creata: ora puoi configurarla e invitare i giornalisti.');
        $this->vai('configuratore');
    }
}
