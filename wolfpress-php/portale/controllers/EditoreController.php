<?php
/**
 * Area editore: account dei giornalisti e chat di redazione.
 */
declare(strict_types=1);

final class EditoreController extends PortaleController
{
    protected string $area = 'editore';
    protected string $voce = 'account';

    public function index(): void
    {
        $impostazioni = Impostazioni::leggi();
        $canale = $this->tra($this->parametro('canale'), $impostazioni['canali'], $impostazioni['canali'][0]);

        $this->vista('editore', [
            'titolo' => 'Area editore · WolfPress',
            'giornalisti' => Giornalista::tutti(),
            'attivi' => count(Giornalista::attivi()),
            'canali' => $impostazioni['canali'],
            'canale' => $canale,
            'messaggi' => Messaggio::perCanale($canale),
            'video' => Impostazioni::video($canale),
        ]);
    }

    /** Scheda di un giornalista (sola lettura). */
    public function profilo(): void
    {
        $giornalista = Giornalista::trova($this->parametro('id'));
        if ($giornalista === null) {
            $this->pagina404('Account non trovato.');
            return;
        }
        $this->vista('editore_giornalista', [
            'titolo' => $giornalista['nome'] . ' · WolfPress',
            'giornalista' => $giornalista,
            'modifica' => false,
            'portfolio' => Articolo::piuLetti(3, $giornalista['nome']),
            'eventi' => Evento::perGiornalista($giornalista['id']),
        ]);
    }

    public function nuovo(): void
    {
        $this->vista('editore_giornalista', [
            'titolo' => 'Nuovo account · WolfPress',
            'giornalista' => ['id' => '', 'nome' => '', 'ruolo' => 'Giornalista', 'sezione' => '', 'stato' => 'Attivo', 'email' => '', 'bio' => ''],
            'modifica' => true,
        ]);
    }

    public function modifica(): void
    {
        $giornalista = Giornalista::trova($this->parametro('id'));
        if ($giornalista === null) {
            $this->pagina404('Account non trovato.');
            return;
        }
        $this->vista('editore_giornalista', [
            'titolo' => 'Modifica ' . $giornalista['nome'] . ' · WolfPress',
            'giornalista' => $giornalista,
            'modifica' => true,
        ]);
    }

    public function salva(): void
    {
        $this->soloPost();
        $id = $this->campo('id', 40);
        $esistente = $id !== '' ? Giornalista::trova($id) : null;
        $email = $this->campo('email', 120);

        $dati = [
            'nome' => $this->campo('nome', 80),
            'ruolo' => $this->campo('ruolo', 60),
            'sezione' => $this->campo('sezione', 40),
            'stato' => $this->tra($this->campo('stato', 20), Giornalista::STATI, 'Attivo'),
            'email' => $email,
            'bio' => $this->campo('bio', 400),
        ];

        if ($dati['nome'] === '' || $dati['ruolo'] === '' || ($id !== '' && $esistente === null)
            || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->avviso('Controlla i dati: nome e ruolo sono obbligatori e l\'email deve essere valida.', 'errore');
            $this->vai($esistente ? 'editore/modifica' : 'editore/nuovo', $esistente ? ['id' => $id] : []);
        }

        if ($esistente !== null) {
            Giornalista::salva($dati + $esistente);
            $this->avviso('Account di ' . $dati['nome'] . ' aggiornato.');
        } else {
            Giornalista::crea($dati);
            $this->avviso('Account di ' . $dati['nome'] . ' creato.');
        }
        $this->vai('editore');
    }

    public function messaggio(): void
    {
        $this->soloPost();
        $canali = Impostazioni::leggi()['canali'];
        $canale = $this->tra($this->campo('canale', 60), $canali, $canali[0]);
        $testo = $this->campo('testo', 600);
        if ($testo !== '') {
            Messaggio::invia($canale, 'Editore', $testo);
        }
        $this->vai('editore', ['canale' => $canale], 'chat');
    }

    /** Collega (o rimuove) il link della videochiamata di un canale. */
    public function video(): void
    {
        $this->soloPost();
        $impostazioni = Impostazioni::leggi();
        $canale = $this->tra($this->campo('canale', 60), $impostazioni['canali'], $impostazioni['canali'][0]);
        $link = $this->campo('link', 300);

        if ($link !== '' && !url_https_valido($link)) {
            $this->avviso('Il link della videochiamata deve iniziare con https://', 'errore');
        } else {
            if ($link === '') {
                unset($impostazioni['video'][$canale]);
            } else {
                $impostazioni['video'][$canale] = $link;
            }
            Impostazioni::salva($impostazioni);
            $this->avviso($link === '' ? 'Videochiamata rimossa dal canale.' : 'Videochiamata collegata al canale ' . $canale . '.');
        }
        $this->vai('editore', ['canale' => $canale], 'chat');
    }
}
