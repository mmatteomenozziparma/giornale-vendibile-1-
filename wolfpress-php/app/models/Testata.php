<?php
/**
 * Testate (tabella "giornali") ospitate dalla piattaforma, con cliente e stato del servizio.
 * L'id usato da viste e indirizzi è lo slug del giornale.
 */
declare(strict_types=1);

final class Testata
{
    public const STATI = ['Operativo', 'Degradato', 'Manutenzione'];
    public const PIANI = ['Essenziale', 'Professionale'];
    public const SOCIAL = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'];

    /** Dati di servizio (colonna JSON "dati") con i valori di partenza. */
    private const SERVIZIO = [
        'stato' => 'Operativo', 'nodo' => '', 'backup' => '', 'incidente' => '', 'piano' => 'Essenziale',
        'referente' => '', 'cliente' => '',
        'social' => ['instagram' => '', 'facebook' => '', 'linkedin' => '', 'youtube' => ''],
    ];

    private static ?array $memoria = null;

    public static function tutti(): array
    {
        if (self::$memoria !== null) {
            return self::$memoria;
        }
        $righe = Database::righe(
            "SELECT j.id AS db_id, j.slug, j.nome, j.descrizione, j.dominio, j.dati, j.prezzo_mensile_cent, j.prezzo_annuale_cent,
                    e.ragione_sociale, g.email_cifrata
             FROM giornali j JOIN editori e ON e.id = j.editore_id JOIN generalita g ON g.id = j.generalita_id
             WHERE j.stato <> 'sospeso' ORDER BY j.id"
        );
        self::$memoria = [];
        foreach ($righe as $r) {
            $servizio = array_replace_recursive(self::SERVIZIO, da_json($r['dati']));
            self::$memoria[] = [
                'id' => $r['slug'],
                'db_id' => (int) $r['db_id'],
                'nome' => $r['nome'],
                'descrizione' => (string) $r['descrizione'],
                'dominio' => (string) $r['dominio'],
                'email' => Cifratura::decifra($r['email_cifrata']),
                'prezzo_mensile_cent' => $r['prezzo_mensile_cent'],
                'prezzo_annuale_cent' => $r['prezzo_annuale_cent'],
            ] + ['cliente' => $servizio['cliente'] !== '' ? $servizio['cliente'] : $r['ragione_sociale']] + $servizio;
        }
        return self::$memoria;
    }

    public static function trova(string $id): ?array
    {
        foreach (self::tutti() as $testata) {
            if ($testata['id'] === $id) {
                return $testata;
            }
        }
        return null;
    }

    /** Numero del giornale nel database a partire dallo slug (0 se non esiste). */
    public static function dbId(string $id): int
    {
        return self::trova($id)['db_id'] ?? 0;
    }

    public static function nome(string $id): string
    {
        return self::trova($id)['nome'] ?? $id;
    }

    /** Salva i dati di servizio (stato, piano, social, cliente) e l'email di contatto. */
    public static function salva(array $testata): void
    {
        $servizio = array_intersect_key($testata, self::SERVIZIO);
        Database::esegui('UPDATE giornali SET dati = ? WHERE id = ?', [in_json($servizio), $testata['db_id']]);
        $attuale = self::trova($testata['id']);
        if ($attuale !== null && $testata['email'] !== '' && $testata['email'] !== $attuale['email']) {
            Database::esegui(
                'UPDATE generalita g JOIN giornali j ON j.generalita_id = g.id SET g.email_cifrata = ?, g.email_hash = ? WHERE j.id = ?',
                [Cifratura::cifra($testata['email']), Cifratura::impronta('giornale|' . $testata['id'] . '|' . $testata['email']), $testata['db_id']]
            );
        }
        self::$memoria = null;
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
