<?php
/**
 * Articoli delle testate. Solo quelli "Pubblicato" compaiono sul sito pubblico.
 */
declare(strict_types=1);

final class Articolo extends Model
{
    protected const ARCHIVIO = 'articoli';

    public const STATI = ['Bozza', 'In revisione', 'Pubblicato'];

    /** Articoli pubblicati, dal più recente. */
    public static function pubblicati(): array
    {
        $righe = array_filter(self::tutti(), static fn (array $a): bool => $a['stato'] === 'Pubblicato');
        usort($righe, static fn (array $a, array $b): int => strcmp($b['data'], $a['data']));
        return $righe;
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
        $righe = array_filter(self::tutti(), static fn (array $a): bool => $a['testata'] === $testata);
        usort($righe, static fn (array $a, array $b): int => strcmp($b['data'], $a['data']));
        return $righe;
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
        $sezioni = array_unique(array_column(self::pubblicati(), 'sezione'));
        sort($sezioni, SORT_LOCALE_STRING);
        return $sezioni;
    }

    /** I più letti tra i pubblicati (eventualmente di un solo autore). */
    public static function piuLetti(int $quanti = 3, string $autore = ''): array
    {
        $righe = self::pubblicati();
        if ($autore !== '') {
            $righe = array_filter($righe, static fn (array $a): bool => $a['autore'] === $autore);
        }
        usort($righe, static fn (array $a, array $b): int => $b['letture'] <=> $a['letture']);
        return array_slice($righe, 0, $quanti);
    }

    /** Quanti articoli ci sono per ogni stato in una testata. */
    public static function contaPerStato(string $testata): array
    {
        $conteggio = array_fill_keys(self::STATI, 0);
        foreach (self::perTestata($testata) as $articolo) {
            $conteggio[$articolo['stato']] = ($conteggio[$articolo['stato']] ?? 0) + 1;
        }
        return $conteggio;
    }

    /** Crea l'indirizzo leggibile dell'articolo a partire dal titolo. */
    public static function creaSlug(string $titolo, string $idEscluso = ''): string
    {
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titolo);
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $base)), '-');
        $base = $base !== '' ? substr($base, 0, 70) : 'articolo';

        $slug = $base;
        $n = 2;
        while (self::slugOccupato($slug, $idEscluso)) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    private static function slugOccupato(string $slug, string $idEscluso): bool
    {
        foreach (self::tutti() as $articolo) {
            if ($articolo['slug'] === $slug && (string) $articolo['id'] !== $idEscluso) {
                return true;
            }
        }
        return false;
    }

    public static function crea(array $dati): array
    {
        $articolo = $dati + [
            'id' => self::nuovoId('a'),
            'letture' => 0,
            'data' => date('Y-m-d'),
        ];
        $articolo['slug'] = self::creaSlug($articolo['titolo']);
        self::salva($articolo);
        return $articolo;
    }
}
