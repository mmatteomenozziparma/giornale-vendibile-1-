<?php
/**
 * Personale tecnico dell'azienda fornitrice (tabelle "tecnici" + "generalita").
 */
declare(strict_types=1);

final class Operatore
{
    public const RUOLI = ['Sistemista', 'Supporto tecnico', 'Responsabile tecnico', 'Sviluppatore'];
    public const STATI = ['Attivo', 'Sospeso'];

    private static ?array $memoria = null;

    public static function tutti(): array
    {
        return self::$memoria ??= array_map(static fn (array $r): array => [
            'id' => (string) $r['id'],
            'generalita_id' => (int) $r['generalita_id'],
            'nome' => trim($r['nome'] . ' ' . $r['cognome']),
            'ruolo' => $r['qualifica'],
            'turno' => (string) $r['turno'],
            'stato' => $r['stato'] === 'attivo' ? 'Attivo' : 'Sospeso',
            'email' => Cifratura::decifra($r['email_cifrata']),
        ], Database::righe(
            'SELECT t.id, t.generalita_id, t.qualifica, t.turno, g.nome, g.cognome, g.stato, g.email_cifrata
             FROM tecnici t JOIN generalita g ON g.id = t.generalita_id ORDER BY t.id'
        ));
    }

    public static function trova(string $id): ?array
    {
        foreach (self::tutti() as $operatore) {
            if ($operatore['id'] === $id) {
                return $operatore;
            }
        }
        return null;
    }

    public static function attivi(): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $o): bool => $o['stato'] === 'Attivo'));
    }

    /** Crea l'account: l'operatore riceve un'email per scegliere la password. */
    public static function crea(array $dati): void
    {
        [$nome, $cognome] = nome_cognome($dati['nome']);
        Account::creaInvitato('tecnico', $nome, $cognome, $dati['email'], ['qualifica' => $dati['ruolo'], 'turno' => $dati['turno']]);
        self::$memoria = null;
    }

    /** Aggiorna scheda e stato. Nessuno può sospendere se stesso. */
    public static function salva(array $operatore): void
    {
        [$nome, $cognome] = nome_cognome($operatore['nome']);
        $stato = $operatore['stato'] === 'Attivo' || $operatore['generalita_id'] === Auth::id() ? 'attivo' : 'sospeso';
        Database::esegui(
            'UPDATE tecnici t JOIN generalita g ON g.id = t.generalita_id
             SET g.nome = ?, g.cognome = ?, t.qualifica = ?, t.turno = ?, g.stato = ? WHERE t.id = ?',
            [$nome, $cognome, $operatore['ruolo'], $operatore['turno'], $stato, (int) $operatore['id']]
        );
        self::$memoria = null;
    }
}
