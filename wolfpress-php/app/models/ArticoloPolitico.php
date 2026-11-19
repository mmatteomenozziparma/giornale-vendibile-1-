<?php
/**
 * Articoli scritti dai politici (tabella "articoli_politici").
 * Quelli "Pubblicato" compaiono nella scheda pubblica del politico e tra le notizie del suo partito.
 */
declare(strict_types=1);

final class ArticoloPolitico
{
    public const STATI = ['Bozza', 'Pubblicato'];

    private static function leggi(string $dove, array $parametri): array
    {
        return array_map(static fn (array $r): array => [
            'id' => (string) $r['id'],
            'politico_id' => (int) $r['politico_id'],
            'politico' => $r['politico'],
            'autore' => $r['autore'],
            'slug' => $r['slug'],
            'titolo' => $r['titolo'],
            'categoria' => (string) $r['categoria'],
            'sommario' => (string) $r['sommario'],
            'corpo' => $r['corpo'],
            'stato' => $r['stato'] === 'pubblicato' ? 'Pubblicato' : 'Bozza',
            'data' => ora_locale($r['pubblicato_il'] ?? $r['creato_il'], 'Y-m-d'),
        ], Database::righe(
            "SELECT a.*, p.slug AS politico, TRIM(CONCAT(g.nome, ' ', COALESCE(g.cognome, ''))) AS autore
             FROM articoli_politici a JOIN politici p ON p.id = a.politico_id JOIN generalita g ON g.id = p.generalita_id
             $dove ORDER BY COALESCE(a.pubblicato_il, a.creato_il) DESC, a.id DESC",
            $parametri
        ));
    }

    /** Tutti gli articoli di un politico (anche le bozze): per la sua area del portale. */
    public static function perPolitico(int $politicoId): array
    {
        return self::leggi('WHERE a.politico_id = ?', [$politicoId]);
    }

    /** Articoli pubblicati dei politici con account attivo (tutti, o di un solo politico). */
    public static function pubblicati(?int $politicoId = null): array
    {
        return self::leggi(
            "WHERE a.stato = 'pubblicato' AND g.stato = 'attivo'" . ($politicoId !== null ? ' AND a.politico_id = ?' : ''),
            $politicoId !== null ? [$politicoId] : []
        );
    }

    public static function pubblicatoPerSlug(string $slug): ?array
    {
        return self::leggi("WHERE a.stato = 'pubblicato' AND g.stato = 'attivo' AND a.slug = ?", [$slug])[0] ?? null;
    }

    /** L'articolo, solo se è del politico indicato. */
    public static function trova(string $id, int $politicoId): ?array
    {
        return ctype_digit($id) ? (self::leggi('WHERE a.id = ? AND a.politico_id = ?', [(int) $id, $politicoId])[0] ?? null) : null;
    }

    private static function slug(string $titolo): string
    {
        $base = Http::slug($titolo);
        $slug = $base;
        $n = 2;
        while (Database::valore('SELECT 1 FROM articoli_politici WHERE slug = ?', [$slug]) !== null) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /** Crea ($dati['id'] vuoto) o aggiorna un articolo del politico indicato. */
    public static function salva(array $dati, int $politicoId): void
    {
        $stato = $dati['stato'] === 'Pubblicato' ? 'pubblicato' : 'bozza';
        if ($dati['id'] === '') {
            Database::inserisci('articoli_politici', [
                'politico_id' => $politicoId,
                'titolo' => $dati['titolo'],
                'slug' => self::slug($dati['titolo']),
                'categoria' => $dati['categoria'],
                'sommario' => $dati['sommario'],
                'corpo' => $dati['corpo'],
                'stato' => $stato,
                'pubblicato_il' => $stato === 'pubblicato' ? gmdate('Y-m-d H:i:s') : null,
            ]);
            return;
        }
        Database::esegui(
            "UPDATE articoli_politici SET titolo = ?, categoria = ?, sommario = ?, corpo = ?, stato = ?,
                    pubblicato_il = IF(? = 'pubblicato', COALESCE(pubblicato_il, NOW()), pubblicato_il)
             WHERE id = ? AND politico_id = ?",
            [$dati['titolo'], $dati['categoria'], $dati['sommario'], $dati['corpo'], $stato, $stato, (int) $dati['id'], $politicoId]
        );
    }

    public static function elimina(string $id, int $politicoId): void
    {
        Database::esegui('DELETE FROM articoli_politici WHERE id = ? AND politico_id = ?', [(int) $id, $politicoId]);
    }
}
