<?php
/**
 * Archivio privato dei file di Atlante Civico (modelli, documenti, prove di firma).
 *
 * I file NON stanno in una cartella pubblica e NON sono salvati in chiaro:
 * ogni file è cifrato con la chiave dell'applicazione (CHIAVE_CIFRATURA) e nel
 * database finiscono solo un riferimento casuale e l'impronta SHA-256 del
 * contenuto originale. Il riferimento non è un indirizzo web: il file si
 * scarica solo passando dai controlli di Atlante::scarica().
 */
declare(strict_types=1);

final class Archivio
{
    public const MASSIMO = 10485760;   // 10 MB

    /** Tipi ammessi: si guarda il contenuto reale del file, non il nome. */
    public const DOCUMENTI = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.oasis.opendocument.text' => 'odt',
    ];
    public const PROVE = self::DOCUMENTI + ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    private static function cartella(): string
    {
        $cartella = RADICE . '/storage/atlante';
        if (!is_dir($cartella) && !@mkdir($cartella, 0700, true)) {
            throw new RuntimeException('La cartella storage/atlante non è scrivibile.');
        }
        return $cartella;
    }

    private static function percorso(string $rif): string
    {
        if (!preg_match('/^atlante\/[a-f0-9]{32}\.(pdf|docx|odt|png|jpg)$/', $rif)) {
            throw new RuntimeException('Riferimento di archivio non valido');
        }
        return RADICE . '/storage/' . substr($rif, 0, strrpos($rif, '.')) . '.bin';
    }

    /**
     * Salva un file caricato con un form ($_FILES['campo']).
     * Restituisce [riferimento da salvare nel database, sha256 del contenuto originale].
     */
    public static function salva(?array $caricato, array $tipi): array
    {
        if (!is_array($caricato) || ($caricato['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ErroreApi('Scegli un file da caricare.', 422);
        }
        if ($caricato['error'] !== UPLOAD_ERR_OK || $caricato['size'] > self::MASSIMO || !is_uploaded_file($caricato['tmp_name'])) {
            throw new ErroreApi('File non caricato: il limite è 10 MB.', 422);
        }
        $tipo = (string) (new finfo(FILEINFO_MIME_TYPE))->file($caricato['tmp_name']);
        // I file DOCX e ODT sono archivi zip: si riconoscono dall'estensione dichiarata, dopo aver visto che è uno zip.
        if ($tipo === 'application/zip' || $tipo === 'application/octet-stream') {
            $estensione = strtolower(pathinfo((string) $caricato['name'], PATHINFO_EXTENSION));
            $tipo = (string) array_search($estensione, $tipi, true);
        }
        if (!isset($tipi[$tipo])) {
            throw new ErroreApi('Tipo di file non ammesso. Ammessi: ' . implode(', ', array_unique($tipi)) . '.', 422);
        }
        return self::scrivi((string) file_get_contents($caricato['tmp_name']), $tipi[$tipo]);
    }

    /** Cifra e salva un contenuto; restituisce [riferimento, sha256]. */
    public static function scrivi(string $contenuto, string $estensione): array
    {
        $nome = bin2hex(random_bytes(16));
        $file = self::cartella() . '/' . $nome . '.bin';
        if (file_put_contents($file, Cifratura::cifra($contenuto), LOCK_EX) === false) {
            throw new RuntimeException('Impossibile salvare il file in archivio.');
        }
        @chmod($file, 0600);
        return ['atlante/' . $nome . '.' . $estensione, hash('sha256', $contenuto)];
    }

    /** Contenuto originale di un file dell'archivio. */
    public static function leggi(string $rif): string
    {
        $file = self::percorso($rif);
        if (!is_file($file)) {
            throw new ErroreApi('File non più presente in archivio.', 404);
        }
        return Cifratura::decifra((string) file_get_contents($file));
    }

    public static function tipo(string $rif): string
    {
        return (string) array_search(pathinfo($rif, PATHINFO_EXTENSION), self::PROVE, true);
    }
}
