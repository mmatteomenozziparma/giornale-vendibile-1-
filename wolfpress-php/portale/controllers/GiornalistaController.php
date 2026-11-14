<?php
/**
 * Profilo giornalista: portfolio (i tre articoli più letti), eventi in agenda e chat di redazione.
 */
declare(strict_types=1);

final class GiornalistaController extends PortaleController
{
    protected string $area = 'giornalista';
    protected string $voce = 'portfolio';

    public function index(): void
    {
        $giornalista = Giornalista::trova(Auth::profilo());
        if ($giornalista === null) {
            $this->pagina404('Profilo giornalista non trovato: scegli di nuovo il profilo demo.');
            return;
        }
        $impostazioni = Impostazioni::leggi();
        $canale = $this->tra($this->parametro('canale'), $impostazioni['canali'], $impostazioni['canali'][0]);

        $this->vista('giornalista', [
            'titolo' => 'Profilo giornalista · WolfPress',
            'giornalista' => $giornalista,
            'portfolio' => Articolo::piuLetti(3, $giornalista['nome']),
            'eventi' => Evento::perGiornalista($giornalista['id']),
            'canali' => $impostazioni['canali'],
            'canale' => $canale,
            'messaggi' => Messaggio::perCanale($canale),
            'video' => Impostazioni::video($canale),
        ]);
    }

    public function messaggio(): void
    {
        $this->soloPost();
        $canali = Impostazioni::leggi()['canali'];
        $canale = $this->tra($this->campo('canale', 60), $canali, $canali[0]);
        $testo = $this->campo('testo', 600);
        if ($testo !== '') {
            Messaggio::invia($canale, Auth::nome(), $testo);
        }
        $this->vai('giornalista', ['canale' => $canale], 'chat');
    }

    public function evento(): void
    {
        $this->soloPost();
        $titolo = $this->campo('titolo', 80);
        $data = $this->campo('data', 10);
        $ora = $this->campo('ora', 5);

        if ($titolo === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || strtotime($data) === false
            || ($ora !== '' && !preg_match('/^\d{2}:\d{2}$/', $ora))) {
            $this->avviso('Per aggiungere un evento servono titolo e data.', 'errore');
        } else {
            Evento::crea([
                'giornalista' => Auth::profilo(),
                'titolo' => $titolo,
                'data' => $data,
                'ora' => $ora,
                'luogo' => $this->campo('luogo', 60),
            ]);
            $this->avviso('Evento aggiunto all\'agenda.');
        }
        $this->vai('giornalista', [], 'eventi');
    }

    public function rimuoviEvento(): void
    {
        $this->soloPost();
        $evento = Evento::trova($this->campo('id', 40));
        if ($evento !== null && $evento['giornalista'] === Auth::profilo()) {
            Evento::elimina((string) $evento['id']);
            $this->avviso('Evento rimosso dall\'agenda.');
        }
        $this->vai('giornalista', [], 'eventi');
    }
}
