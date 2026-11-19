<?php
/**
 * Articoli delle testate (tabella "articoli_giornali").
 * Solo quelli "Pubblicato" compaiono sul sito pubblico.
 */
declare(strict_types=1);

final class Articolo
{
    public const STATI = ['Bozza', 'In revisione', 'Pubblicato'];

    private const STATI_DB = ['bozza' => 'Bozza', 'in_revisione' => 'In revisione', 'pubblicato' => 'Pubblicato', 'archiviato' => 'Bozza'];

    private const SELEZIONE = "SELECT a.id, a.slug, a.titolo, a.sezione, a.sommario, a.corpo, a.stato, a.letture, a.giornalista_id,
            a.pubblicato_il, a.creato_il, j.slug AS testata,
            COALESCE(NULLIF(TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))), ''), a.firma, j.nome) AS autore
        FROM articoli_giornali a
        JOIN giornali j ON j.id = a.giornale_id
        LEFT JOIN giornalisti gi ON gi.id = a.giornalista_id
        LEFT JOIN generalita g ON g.id = gi.generalita_id";

    private static ?array $pubblicati = null;

    private static function riga(array $r): array
    {
        return [
            'id' => (string) $r['id'],
            'slug' => $r['slug'],
            'titolo' => $r['titolo'],
            'sezione' => (string) $r['sezione'],
            'testata' => $r['testata'],
            'autore' => (string) $r['autore'],
            'giornalista_id' => (string) $r['giornalista_id'],
            'data' => ora_locale($r['pubblicato_il'] ?? $r['creato_il'], 'Y-m-d'),
            'sommario' => (string) $r['sommario'],
            'corpo' => $r['corpo'],
            'stato' => self::STATI_DB[$r['stato']],
            'letture' => (int) $r['letture'],
        ];
    }

    private static function leggi(string $dove, array $parametri): array
    {
        return array_map(
            [self::class, 'riga'],
            Database::righe(self::SELEZIONE . " $dove ORDER BY COALESCE(a.pubblicato_il, a.creato_il) DESC, a.id DESC", $parametri)
        );
    }

    public static function trova(string $id): ?array
    {
        return ctype_digit($id) ? (self::leggi('WHERE a.id = ?', [(int) $id])[0] ?? null) : null;
    }

    /** Articoli pubblicati di tutte le testate attive, dal più recente. */
    public static function pubblicati(): array
    {
        return self::$pubblicati ??= self::leggi("WHERE a.stato = 'pubblicato' AND j.stato = 'attivo'", []);
    }

    public static function pubblicatoPerSlug(string $slug): ?array
    {
        foreach (self::pubblicati() as $articolo) {
            if ($articolo['slug'] === $slug) {
                return $articolo;
            }
        }
        return null;
    }

    /** Tutti gli articoli di una testata (anche bozze), dal più recente. */
    public static function perTestata(string $testata): array
    {
        return self::leggi('WHERE j.slug = ?', [$testata]);
    }

    /** Filtra gli articoli pubblicati per sezione, testata e testo cercato. */
    public static function filtra(string $sezione = '', string $testata = '', string $ricerca = ''): array
    {
        return array_values(array_filter(self::pubblicati(), static function (array $a) use ($sezione, $testata, $ricerca): bool {
            if ($sezione !== '' && mb_strtolower($a['sezione']) !== mb_strtolower($sezione)) {
                return false;
            }
            if ($testata !== '' && $a['testata'] !== $testata) {
                return false;
            }
            return contiene($a['titolo'] . ' ' . $a['sommario'] . ' ' . $a['corpo'] . ' ' . $a['sezione'] . ' ' . $a['autore'], $ricerca);
        }));
    }

    /** Elenco delle sezioni che hanno almeno un articolo pubblicato. */
    public static function sezioni(): array
    {
        $sezioni = array_values(array_filter(array_unique(array_column(self::pubblicati(), 'sezione'))));
        sort($sezioni, SORT_LOCALE_STRING);
        return $sezioni;
    }

    /** I più letti tra i pubblicati (eventualmente di un solo giornalista, indicato con il suo id). */
    public static function piuLetti(int $quanti = 3, string $giornalista = ''): array
    {
        $righe = self::pubblicati();
        if ($giornalista !== '') {
            $righe = array_filter($righe, static fn (array $a): bool => $a['giornalista_id'] === $giornalista);
        }
        usort($righe, static fn (array $a, array $b): int => $b['letture'] <=> $a['letture']);
        return array_slice($righe, 0, $quanti);
    }

    /** Quanti articoli ci sono per ogni stato in una testata. */
    public static function contaPerStato(string $testata): array
    {
        $conteggio = array_fill_keys(self::STATI, 0);
        foreach (self::perTestata($testata) as $articolo) {
            $conteggio[$articolo['stato']]++;
        }
        return $conteggio;
    }

    /** Crea l'indirizzo leggibile dell'articolo a partire dal titolo (unico in tutto il sito). */
    public static function creaSlug(string $titolo): string
    {
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titolo);
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $base)), '-');
        $base = $base !== '' ? substr($base, 0, 70) : 'articolo';

        $slug = $base;
        $n = 2;
        while (Database::valore('SELECT 1 FROM articoli_giornali WHERE slug = ?', [$slug]) !== null) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    private static function statoDb(string $stato): string
    {
        return (string) array_search($stato, ['bozza' => 'Bozza', 'in_revisione' => 'In revisione', 'pubblicato' => 'Pubblicato'], true) ?: 'bozza';
    }

    /** Nuovo articolo nella testata indicata, firmato da chi è collegato. */
    public static function crea(array $dati): array
    {
        $stato = self::statoDb($dati['stato']);
        $id = Database::inserisci('articoli_giornali', [
            'giornale_id' => Testata::dbId($dati['testata']),
            'giornalista_id' => Auth::ruolo() === 'giornalista' ? Auth::profiloId() : null,
            'firma' => Auth::nome(),
            'titolo' => $dati['titolo'],
            'slug' => self::creaSlug($dati['titolo']),
            'sezione' => $dati['sezione'],
            'sommario' => $dati['sommario'],
            'corpo' => $dati['corpo'],
            'stato' => $stato,
            'pubblicato_il' => $stato === 'pubblicato' ? gmdate('Y-m-d H:i:s') : null,
        ]);
        self::$pubblicati = null;
        return self::trova((string) $id);
    }

    /** Aggiorna testo e stato di un articolo esistente. */
    public static function salva(array $articolo): void
    {
        $stato = self::statoDb($articolo['stato']);
        Database::esegui(
            "UPDATE articoli_giornali SET titolo = ?, sezione = ?, sommario = ?, corpo = ?, stato = ?,
                    pubblicato_il = IF(? = 'pubblicato', COALESCE(pubblicato_il, NOW()), pubblicato_il)
             WHERE id = ?",
            [$articolo['titolo'], $articolo['sezione'], $articolo['sommario'], $articolo['corpo'], $stato, $stato, (int) $articolo['id']]
        );
        self::$pubblicati = null;
    }

    public static function elimina(string $id): void
    {
        Database::esegui('DELETE FROM articoli_giornali WHERE id = ?', [(int) $id]);
        self::$pubblicati = null;
    }
}
