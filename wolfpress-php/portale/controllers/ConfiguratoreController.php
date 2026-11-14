<?php
/**
 * Configuratore della testata: identità del giornale e moduli visibili al cliente.
 */
declare(strict_types=1);

final class ConfiguratoreController extends PortaleController
{
    protected string $area = 'configuratore';
    protected string $voce = 'identita';

    private const LOGO_MASSIMO = 1048576; // 1 MB
    private const LOGO_TIPI = [IMAGETYPE_PNG => 'image/png', IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_WEBP => 'image/webp'];

    public function index(): void
    {
        $this->vista('configuratore', ['titolo' => 'Configuratore · WolfPress']);
    }

    public function salva(): void
    {
        $this->soloPost();
        $impostazioni = Impostazioni::leggi();
        $errori = [];

        $testata = $this->campo('testata', 60);
        $cliente = $this->campo('cliente', 80);
        $colore = $this->campo('colore', 7);
        $email = $this->campo('email_assistenza', 120);

        if ($testata === '') {
            $errori[] = 'Il nome della testata è obbligatorio.';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colore)) {
            $errori[] = 'Il colore principale non è valido.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errori[] = 'L\'email di assistenza non è valida.';
        }

        $logo = $this->leggiLogo($errori);

        if ($errori) {
            $this->avviso(implode(' ', $errori), 'errore');
            $this->vai('configuratore');
        }

        $impostazioni['testata'] = $testata;
        $impostazioni['cliente'] = $cliente !== '' ? $cliente : 'Redazione ' . $testata;
        $impostazioni['benvenuto'] = $this->campo('benvenuto', 240);
        $impostazioni['colore'] = strtolower($colore);
        $impostazioni['email_assistenza'] = $email;

        $scelti = is_array($_POST['moduli'] ?? null) ? $_POST['moduli'] : [];
        foreach (array_keys(Impostazioni::MODULI) as $modulo) {
            $impostazioni['moduli'][$modulo] = in_array($modulo, $scelti, true);
        }
        if ($logo !== null) {
            $impostazioni['logo'] = $logo;
        }

        Impostazioni::salva($impostazioni);
        $this->avviso('Configurazione salvata: l\'area cliente è aggiornata.');
        $this->vai('configuratore');
    }

    public function rimuoviLogo(): void
    {
        $this->soloPost();
        $impostazioni = Impostazioni::leggi();
        $impostazioni['logo'] = '';
        Impostazioni::salva($impostazioni);
        @unlink(WP_STORAGE . '/logo.bin');
        $this->avviso('Logo rimosso.');
        $this->vai('configuratore');
    }

    /** Controlla e salva il logo caricato. Restituisce null se non è stato scelto alcun file. */
    private function leggiLogo(array &$errori): ?array
    {
        $file = $_FILES['logo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > self::LOGO_MASSIMO || !is_uploaded_file($file['tmp_name'])) {
            $errori[] = 'Il logo non è stato caricato: il file deve pesare al massimo 1 MB.';
            return null;
        }
        // Si controlla il contenuto reale del file, non il nome o l'estensione.
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !isset(self::LOGO_TIPI[$info[2]])) {
            $errori[] = 'Il logo deve essere un\'immagine PNG, JPEG o WebP.';
            return null;
        }
        if ($errori) {
            return null;
        }
        if (!is_dir(WP_STORAGE)) {
            @mkdir(WP_STORAGE, 0775, true);
        }
        if (!@move_uploaded_file($file['tmp_name'], WP_STORAGE . '/logo.bin')) {
            $errori[] = 'Impossibile salvare il logo: controlla i permessi della cartella storage/.';
            return null;
        }
        return ['tipo' => self::LOGO_TIPI[$info[2]], 'versione' => time()];
    }
}
