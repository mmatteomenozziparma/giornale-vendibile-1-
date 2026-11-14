<?php
/**
 * Schede dei politici. Ogni scheda ha due scenari indipendenti:
 * "candidatura" (campagna elettorale) e "mandato" (rendiconto dell'eletto).
 * L'id della scheda coincide con il livello istituzionale.
 */
declare(strict_types=1);

final class Politico extends Model
{
    protected const ARCHIVIO = 'politici';

    public const LIVELLI = [
        'comune' => 'Comune',
        'regione' => 'Regione',
        'camera' => 'Camera dei deputati',
        'senato' => 'Senato della Repubblica',
        'europa' => 'Parlamento europeo',
    ];

    public const SCENARI = ['candidatura' => 'Candidatura', 'mandato' => 'Mandato'];

    /** Cerca nei nomi, nei ruoli, nei luoghi e nei testi delle schede. */
    public static function cerca(string $ricerca): array
    {
        return array_values(array_filter(self::tutti(), static function (array $p) use ($ricerca): bool {
            $testo = $p['nome'] . ' ' . $p['partito_nome'] . ' ' . $p['bio'] . ' ' . self::LIVELLI[$p['id']];
            foreach (['candidatura', 'mandato'] as $scenario) {
                $testo .= ' ' . $p[$scenario]['ruolo'] . ' ' . $p[$scenario]['luogo'];
                foreach ($p[$scenario]['voci'] as $voce) {
                    $testo .= ' ' . $voce['titolo'];
                }
            }
            return contiene($testo, $ricerca);
        }));
    }

    public static function perPartito(string $partito): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $p): bool => $p['partito'] === $partito));
    }
}
