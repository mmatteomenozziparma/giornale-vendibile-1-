<?php
/**
 * Partiti e relative sezioni regionali (tabella "partiti": notizie, circoli,
 * appuntamenti e contatti sono nella colonna JSON "dati").
 */
declare(strict_types=1);

final class Partito
{
    private static ?array $memoria = null;

    public static function tutti(): array
    {
        return self::$memoria ??= array_map(static fn (array $r): array => ['id' => $r['slug'], 'nome' => $r['nome']] + da_json($r['dati']) + [
            'sezione' => '', 'descrizione' => '', 'notizie' => [], 'rete_intro' => '', 'rete' => [], 'appuntamenti' => [], 'orari' => '', 'email' => '',
        ], Database::righe('SELECT slug, nome, dati FROM partiti ORDER BY nome'));
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
