<?php
/**
 * Testate (giornali) ospitate dalla piattaforma WolfPress, con cliente e stato del servizio.
 */
declare(strict_types=1);

final class Testata extends Model
{
    protected const ARCHIVIO = 'testate';

    /** Testata principale usata dal configuratore e dall'area cliente. */
    public const PRINCIPALE = 'wolfpress';

    public const STATI = ['Operativo', 'Degradato', 'Manutenzione'];
    public const PIANI = ['Essenziale', 'Professionale'];
    public const SOCIAL = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'];

    public static function nome(string $id): string
    {
        return self::trova($id)['nome'] ?? $id;
    }

    /** Canali social compilati di una testata: ['Instagram' => 'https://...']. */
    public static function socialAttivi(array $testata): array
    {
        $attivi = [];
        foreach (self::SOCIAL as $chiave => $etichetta) {
            if (!empty($testata['social'][$chiave])) {
                $attivi[$etichetta] = $testata['social'][$chiave];
            }
        }
        return $attivi;
    }

    public static function operative(): int
    {
        return count(array_filter(self::tutti(), static fn (array $t): bool => $t['stato'] === 'Operativo'));
    }
}
