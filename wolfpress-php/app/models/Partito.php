<?php
/**
 * Partiti e relative sezioni (tabella "partiti"). Sezione, descrizione, circoli
 * e contatti sono nella colonna JSON "dati"; notizie e appuntamenti si
 * compongono anche da soli con gli articoli pubblicati e gli eventi pubblici
 * dei politici che hanno scelto quel partito.
 */
declare(strict_types=1);

final class Partito
{
    private const VUOTO = ['sezione' => '', 'descrizione' => '', 'notizie' => [], 'rete_intro' => '', 'rete' => [], 'appuntamenti' => [], 'orari' => '', 'email' => ''];

    private static ?array $memoria = null;

    public static function tutti(): array
    {
        if (self::$memoria !== null) {
            return self::$memoria;
        }
        $partiti = [];
        foreach (Database::righe('SELECT slug, nome, dati FROM partiti ORDER BY nome') as $r) {
            $partiti[$r['slug']] = ['id' => $r['slug'], 'nome' => $r['nome']] + da_json($r['dati']) + self::VUOTO;
        }
        if ($partiti !== []) {
            $articoli = ArticoloPolitico::pubblicati();
            foreach (Politico::tutti() as $politico) {
                if (!isset($partiti[$politico['partito']])) {
                    continue;
                }
                $p = &$partiti[$politico['partito']];
                foreach ($articoli as $a) {
                    if ($a['politico'] === $politico['id']) {
                        $p['notizie'][] = ['titolo' => $a['titolo'], 'categoria' => $a['categoria'] !== '' ? $a['categoria'] : $politico['nome'], 'data' => $a['data'], 'testo' => $a['sommario'] !== '' ? $a['sommario'] : mb_strimwidth($a['corpo'], 0, 300, '…')];
                    }
                }
                foreach ($politico['candidatura']['agenda'] as $e) {
                    $p['appuntamenti'][] = ['data' => $e['data'], 'titolo' => $e['titolo'], 'dettaglio' => trim($politico['nome'] . ' · ' . $e['dettaglio'], ' ·')];
                }
                unset($p);
            }
            foreach ($partiti as &$p) {
                usort($p['notizie'], static fn (array $a, array $b): int => strcmp($b['data'], $a['data']));
                usort($p['appuntamenti'], static fn (array $a, array $b): int => strcmp($a['data'], $b['data']));
            }
            unset($p);
        }
        return self::$memoria = array_values($partiti);
    }

    public static function trova(string $id): ?array
    {
        foreach (self::tutti() as $partito) {
            if ($partito['id'] === $id) {
                return $partito;
            }
        }
        return null;
    }

    /** Elenco per le tendine: [slug => nome]. */
    public static function nomi(): array
    {
        return array_column(Database::righe('SELECT slug, nome FROM partiti ORDER BY nome'), 'nome', 'slug');
    }

    /** Dati modificabili dai tecnici (senza notizie e appuntamenti composti in automatico). */
    public static function scheda(string $id): ?array
    {
        $r = Database::riga('SELECT slug, nome, dati FROM partiti WHERE slug = ?', [$id]);
        return $r === null ? null : ['id' => $r['slug'], 'nome' => $r['nome']] + da_json($r['dati']) + self::VUOTO;
    }

    /** Crea ($partito['id'] vuoto) o aggiorna un partito; restituisce lo slug. */
    public static function salva(array $partito): string
    {
        $dati = in_json(array_intersect_key($partito, self::VUOTO) + self::VUOTO);
        self::$memoria = null;
        if ($partito['id'] !== '') {
            Database::esegui('UPDATE partiti SET nome = ?, dati = ? WHERE slug = ?', [$partito['nome'], $dati, $partito['id']]);
            // Il nome mostrato nelle schede dei politici segue quello del partito.
            Database::esegui(
                "UPDATE politici SET partito = ? WHERE JSON_VALID(dati) AND JSON_UNQUOTE(JSON_EXTRACT(dati, '$.partito')) = ?",
                [$partito['nome'], $partito['id']]
            );
            return $partito['id'];
        }
        $base = Http::slug($partito['nome']);
        $slug = $base;
        $n = 2;
        while (Database::valore('SELECT 1 FROM partiti WHERE slug = ?', [$slug]) !== null) {
            $slug = $base . '-' . $n++;
        }
        Database::inserisci('partiti', ['slug' => $slug, 'nome' => $partito['nome'], 'dati' => $dati]);
        return $slug;
    }

    public static function cerca(string $ricerca): array
    {
        return array_values(array_filter(self::tutti(), static function (array $p) use ($ricerca): bool {
            $testo = $p['nome'] . ' ' . $p['sezione'] . ' ' . $p['descrizione'];
            foreach ($p['rete'] as $circolo) {
                $testo .= ' ' . $circolo['nome'] . ' ' . $circolo['descrizione'];
            }
            foreach ($p['notizie'] as $notizia) {
                $testo .= ' ' . $notizia['titolo'];
            }
            return contiene($testo, $ricerca);
        }));
    }
}
