<?php
/**
 * Area dei lettori: registrazione, accesso, conferma dell'email,
 * password dimenticata e riepilogo di iscrizioni, abbonamenti e donazioni.
 */
declare(strict_types=1);

final class AccountController extends SitoController
{
    protected string $voce = 'account';

    public function index(): void
    {
        $utente = Auth::utente();
        if ($utente === null) {
            $this->vista('account/accedi', ['titolo' => 'Accedi · WolfPress']);
            return;
        }
        $lettore = $utente['ruolo'] === 'user';
        $id = $utente['profilo_id'];
        $this->vista('account/profilo', [
            'titolo' => 'Il tuo account · WolfPress',
            'lettore' => $lettore,
            'iscrizioni' => !$lettore ? [] : Database::righe(
                "SELECT COALESCE(j.nome, TRIM(CONCAT(g.nome, ' ', g.cognome))) AS nome, j.slug AS testata, p.slug AS politico
                 FROM iscrizioni i LEFT JOIN giornali j ON j.id = i.giornale_id
                 LEFT JOIN politici p ON p.id = i.politico_id LEFT JOIN generalita g ON g.id = p.generalita_id
                 WHERE i.user_id = ? ORDER BY i.creato_il DESC",
                [$id]
            ),
            'abbonamenti' => !$lettore ? [] : Database::righe(
                "SELECT j.nome, a.piano, a.importo_centesimi, a.stato, a.scade_il FROM abbonamenti a JOIN giornali j ON j.id = a.giornale_id
                 WHERE a.user_id = ? AND a.stato <> 'in_attesa' ORDER BY a.creato_il DESC",
                [$id]
            ),
            'donazioni' => !$lettore ? [] : Database::righe(
                "SELECT COALESCE(j.nome, TRIM(CONCAT(g.nome, ' ', g.cognome))) AS nome, d.importo_centesimi, d.pagata_il
                 FROM donazioni d LEFT JOIN giornali j ON j.id = d.giornale_id
                 LEFT JOIN politici p ON p.id = d.politico_id LEFT JOIN generalita g ON g.id = p.generalita_id
                 WHERE d.user_id = ? AND d.stato = 'pagata' ORDER BY d.pagata_il DESC",
                [$id]
            ),
        ]);
    }

    public function entra(): void
    {
        $this->soloPost();
        $errore = Accesso::entra();
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
        }
        $this->vai('account');
    }

    public function registrati(): void
    {
        $this->vista('account/registrati', ['titolo' => 'Registrati · WolfPress']);
    }

    public function registra(): void
    {
        $this->soloPost();
        // Dal sito ci si registra solo come lettori.
        $errore = Accesso::registra('user');
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
            $this->vai('account/registrati');
        }
        $this->avviso('Controlla la tua casella di posta: ti abbiamo inviato un\'email per confermare l\'indirizzo.');
        $this->vai('account');
    }

    /** Link cliccato dall'email di registrazione. */
    public function verifica(): void
    {
        [$errore] = Accesso::verifica($this->parametro('token', 64));
        $this->avviso($errore !== '' ? $errore : 'Indirizzo confermato: ora puoi accedere.', $errore !== '' ? 'errore' : 'ok');
        $this->vai('account');
    }

    public function password(): void
    {
        $this->vista('account/password', ['titolo' => 'Password dimenticata · WolfPress', 'token' => '']);
    }

    public function richiesta(): void
    {
        $this->soloPost();
        $errore = Accesso::richiediReset();
        $this->avviso($errore !== '' ? $errore : 'Se l\'indirizzo è registrato riceverai un\'email con il link per scegliere una nuova password.', $errore !== '' ? 'errore' : 'ok');
        $this->vai('account');
    }

    public function nuovaPassword(): void
    {
        $token = $this->parametro('token', 64);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->avviso('Link non valido o scaduto.', 'errore');
            $this->vai('account/password');
        }
        $this->vista('account/password', ['titolo' => 'Nuova password · WolfPress', 'token' => $token]);
    }

    public function salvaPassword(): void
    {
        $this->soloPost();
        $errore = Accesso::nuovaPassword();
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
            $token = $this->campo('token', 64);
            $valido = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
            $this->vai($valido ? 'account/nuova-password' : 'account/password', $valido ? ['token' => $token] : []);
        }
        $this->avviso('Password salvata: ora puoi accedere.');
        $this->vai('account');
    }

    public function esci(): void
    {
        $this->soloPost();
        Auth::esci();
        $this->vai();
    }
}
