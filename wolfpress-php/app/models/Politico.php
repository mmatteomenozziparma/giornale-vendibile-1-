<?php
/**
 * Schede dei politici (tabelle "politici" + "generalita"). Ogni scheda ha due
 * scenari indipendenti, salvati nella colonna JSON "dati":
 * "candidatura" (campagna elettorale) e "mandato" (rendiconto dell'eletto).
 * L'agenda, uguale nei due scenari, viene dalle tabelle degli eventi.
 * L'id usato da viste e indirizzi è lo slug della scheda.
 */
declare(strict_types=1);

final class Politico
{
    public const LIVELLI = [
        'comune' => 'Comune',
        'regione' => 'Regione',
        'camera' => 'Camera dei deputati',
        'senato' => 'Senato della Repubblica',
        'europa' => 'Parlamento europeo',
    ];

    public const SCENARI = ['candidatura' => 'Candidatura', 'mandato' => 'Mandato'];

    private static ?array $memoria = null;

    /** Nome del livello istituzionale di una scheda. */
    public static function livello(array $politico): string
    {
        return self::LIVELLI[$politico['livello']] ?? self::LIVELLI['comune'];
    }

    /** Schede dei politici con account attivo. */
    public static function tutti(): array
    {
        if (self::$memoria !== null) {
            return self::$memoria;
        }
        $righe = Database::righe(
            "SELECT p.id AS db_id, p.slug, p.partito, p.carica, p.territorio, p.biografia, p.livello, p.dati, g.nome, g.cognome
             FROM politici p JOIN generalita g ON g.id = p.generalita_id
             WHERE g.stato = 'attivo' ORDER BY FIELD(p.livello, 'comune', 'regione', 'camera', 'senato', 'europa'), p.id"
        );
        // L'agenda non è più un elenco libero: sono gli eventi pubblici organizzati dal politico.
        $agende = Evento::agendePolitici();
        self::$memoria = [];
        foreach ($righe as $r) {
            $dati = da_json($r['dati']);
            $agenda = ['agenda' => $agende[(int) $r['db_id']] ?? []];
            $scenario = ['ruolo' => (string) $r['carica'], 'luogo' => (string) $r['territorio'], 'voci' => [], 'agenda' => [], 'contatto' => '', 'email' => ''];
            self::$memoria[] = [
                'id' => $r['slug'],
                'db_id' => (int) $r['db_id'],
                'nome' => trim($r['nome'] . ' ' . $r['cognome']),
                'partito' => (string) ($dati['partito'] ?? ''),
                'partito_nome' => (string) $r['partito'],
                'bio' => (string) $r['biografia'],
                'livello' => $r['livello'],
                'candidatura' => $agenda + ($dati['candidatura'] ?? []) + $scenario,
                'mandato' => $agenda + ($dati['mandato'] ?? []) + $scenario + ['incarico' => '', 'link_etichetta' => '', 'link_url' => ''],
            ];
        }
        return self::$memoria;
    }

    public static function trova(string $id): ?array
    {
        foreach (self::tutti() as $politico) {
            if ($politico['id'] === $id) {
                return $politico;
            }
        }
        return null;
    }

    public static function salva(array $politico): void
    {
        // L'agenda sta nelle tabelle degli eventi, non nel JSON della scheda.
        unset($politico['candidatura']['agenda'], $politico['mandato']['agenda']);
        Database::esegui('UPDATE politici SET biografia = ?, partito = ?, dati = ? WHERE id = ?', [
            $politico['bio'],
            $politico['partito_nome'],
            in_json(['partito' => $politico['partito'], 'candidatura' => $politico['candidatura'], 'mandato' => $politico['mandato']]),
            $politico['db_id'],
        ]);
        self::$memoria = null;
    }

    /** Cerca nei nomi, nei ruoli, nei luoghi e nei testi delle schede. */
    public static function cerca(string $ricerca): array
    {
        return array_values(array_filter(self::tutti(), static function (array $p) use ($ricerca): bool {
            $testo = $p['nome'] . ' ' . $p['partito_nome'] . ' ' . $p['bio'] . ' ' . self::livello($p);
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
