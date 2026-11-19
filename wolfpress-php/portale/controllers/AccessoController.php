<?php
/**
 * Accesso al portale con account veri: login, registrazione, conferma
 * dell'email, password dimenticata e uscita. Le regole di sicurezza
 * (hash della password, blocco dopo troppi errori, token monouso)
 * sono nel backend: classe Account.
 */
declare(strict_types=1);

final class AccessoController extends PortaleController
{
    protected string $area = 'accesso';
    protected string $voce = 'entra';

    public function index(): void
    {
        if (Auth::utente() !== null) {
            $this->vai('home');
        }
        $this->vista('accesso', ['titolo' => 'Accedi · WolfPress']);
    }

    public function entra(): void
    {
        $this->soloPost();
        $errore = Accesso::entra();
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
            $this->vai('accesso');
        }
        $this->avviso('Bentornato, ' . Auth::nome() . '.');
        $this->vai('home');
    }

    public function registrazione(): void
    {
        $this->voce = 'registrazione';
        $tipo = $this->tra($this->parametro('tipo'), ['editore', 'giornalista', 'politico'], 'editore');
        $this->vista('accesso_registrazione', [
            'titolo' => 'Registrati · WolfPress',
            'tipo' => $tipo,
            'testate' => Testata::tutti(),
        ]);
    }

    public function registra(): void
    {
        $this->soloPost();
        // Dal portale non ci si può registrare come tecnico: quegli account li crea un altro tecnico.
        $tipo = $this->tra($this->campo('tipo', 20), ['editore', 'giornalista', 'politico'], '');
        $errore = $tipo === '' ? 'Scegli il tipo di account.' : Accesso::registra($tipo);
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
            $this->vai('accesso/registrazione', $tipo !== '' ? ['tipo' => $tipo] : []);
        }
        $this->avviso('Controlla la tua casella di posta: ti abbiamo inviato un\'email per confermare l\'indirizzo.');
        $this->vai('accesso');
    }

    /** Link cliccato dall'email di registrazione. */
    public function verifica(): void
    {
        [$errore, $stato] = Accesso::verifica($this->parametro('token', 64));
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
        } elseif ($stato === 'attivo') {
            $this->avviso('Indirizzo confermato: ora puoi accedere.');
        } else {
            $this->avviso('Indirizzo confermato. Il tuo profilo è in attesa di approvazione: ti avviseremo per email.');
        }
        $this->vai('accesso');
    }

    public function password(): void
    {
        $this->voce = 'password';
        $this->vista('accesso_password', ['titolo' => 'Password dimenticata · WolfPress', 'token' => '']);
    }

    public function richiesta(): void
    {
        $this->soloPost();
        $errore = Accesso::richiediReset();
        // Stessa risposta che l'indirizzo sia registrato oppure no.
        $this->avviso($errore !== '' ? $errore : 'Se l\'indirizzo è registrato riceverai un\'email con il link per scegliere una nuova password.', $errore !== '' ? 'errore' : 'ok');
        $this->vai('accesso');
    }

    /** Link cliccato dall'email di reset o di invito: mostra il modulo della nuova password. */
    public function nuovaPassword(): void
    {
        $this->voce = 'password';
        $token = $this->parametro('token', 64);
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->avviso('Link non valido o scaduto.', 'errore');
            $this->vai('accesso/password');
        }
        $this->vista('accesso_password', ['titolo' => 'Nuova password · WolfPress', 'token' => $token]);
    }

    public function salvaPassword(): void
    {
        $this->soloPost();
        $errore = Accesso::nuovaPassword();
        if ($errore !== '') {
            $this->avviso($errore, 'errore');
            $token = $this->campo('token', 64);
            $this->vai(preg_match('/^[a-f0-9]{64}$/', $token) ? 'accesso/nuova-password' : 'accesso/password', preg_match('/^[a-f0-9]{64}$/', $token) ? ['token' => $token] : []);
        }
        $this->avviso('Password salvata: ora puoi accedere.');
        $this->vai('accesso');
    }

    public function esci(): void
    {
        $this->soloPost();
        Auth::esci();
        $this->vai('accesso');
    }
}
