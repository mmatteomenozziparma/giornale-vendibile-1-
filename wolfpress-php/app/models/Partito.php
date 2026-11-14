<?php
/**
 * Partiti e relative sezioni regionali (notizie, circoli, appuntamenti, contatti).
 */
declare(strict_types=1);

final class Partito extends Model
{
    protected const ARCHIVIO = 'partiti';

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
