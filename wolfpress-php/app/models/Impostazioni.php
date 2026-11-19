<?php
/**
 * Impostazioni della testata scelte nel configuratore (nome, colore, logo,
 * moduli visibili al cliente, canali della chat): colonna JSON "impostazioni"
 * del giornale di chi è collegato. I valori di partenza sono in app/data/impostazioni.json.
 */
declare(strict_types=1);

final class Impostazioni
{
    public const MODULI = [
        'articoli' => ['Articoli', 'Bozze e stato del lavoro editoriale.', 'bi-file-earmark-text'],
        'lettori' => ['Lettori', 'Andamento delle letture degli ultimi sette giorni.', 'bi-bar-chart'],
        'redazione' => ['Redazione', 'Ruoli e referenti del giornale.', 'bi-people'],
        'assistenza' => ['Assistenza', 'Contatto per richieste tecniche.', 'bi-chat-left-text'],
    ];

    private static ?array $memoria = null;

    /** Valori di partenza, uguali per tutte le testate. */
    public static function predefinite(): array
    {
        return da_json((string) file_get_contents(WP_APP . '/data/impostazioni.json'));
    }

    public static function leggi(): array
    {
        if (self::$memoria !== null) {
            return self::$memoria;
        }
        $predefinite = self::predefinite();
        $testata = Auth::testata() !== '' ? Testata::trova(Auth::testata()) : null;
        if ($testata === null) {
            return self::$memoria = $predefinite;
        }
        $salvate = da_json(Database::valore('SELECT impostazioni FROM giornali WHERE id = ?', [$testata['db_id']]));
        // Una testata nuova parte con il proprio nome e senza le letture di esempio.
        $nuova = ['testata' => $testata['nome'], 'cliente' => 'Redazione ' . $testata['nome'], 'letture' => array_fill_keys(array_keys($predefinite['letture']), 0)];
        return self::$memoria = $salvate + $nuova + $predefinite;
    }

    public static function salva(array $impostazioni): void
    {
        $giornale = Testata::dbId(Auth::testata());
        if ($giornale !== 0) {
            Database::esegui('UPDATE giornali SET impostazioni = ? WHERE id = ?', [in_json($impostazioni), $giornale]);
        }
        self::$memoria = null;
    }

    public static function moduloAttivo(string $modulo): bool
    {
        return !empty(self::leggi()['moduli'][$modulo]);
    }

    /** Link https della videochiamata di un canale della chat di redazione. */
    public static function video(string $canale): string
    {
        return self::leggi()['video'][$canale] ?? '';
    }

    /** File del logo della testata di chi è collegato (fuori dalla cartella pubblica). */
    public static function fileLogo(): string
    {
        return WP_STORAGE . '/logo-' . (preg_replace('/[^a-z0-9-]/', '', Auth::testata()) ?: 'nessuna') . '.bin';
    }
}
