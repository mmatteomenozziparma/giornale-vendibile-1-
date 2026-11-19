<?php
/**
 * Account dei giornalisti (tabelle "giornalisti" + "generalita").
 * Ogni redazione vede soltanto i giornalisti della propria testata.
 */
declare(strict_types=1);

final class Giornalista
{
    public const STATI = ['Attivo', 'Sospeso'];

    private const STATI_DB = ['attivo' => 'Attivo', 'sospeso' => 'Sospeso', 'in_approvazione' => 'Da approvare', 'da_verificare' => 'Email da confermare'];

    /** Giornalisti della testata di chi è collegato. */
    public static function tutti(): array
    {
        $righe = Database::righe(
            "SELECT gi.id, gi.qualifica, gi.sezione, gi.bio, g.nome, g.cognome, g.stato, g.email_cifrata, g.password_hash IS NULL AS invitato
             FROM giornalisti gi JOIN generalita g ON g.id = gi.generalita_id JOIN giornali j ON j.id = gi.giornale_id
             WHERE j.slug = ? ORDER BY g.cognome, g.nome",
            [Auth::testata()]
        );
        return array_map(static fn (array $r): array => [
            'id' => (string) $r['id'],
            'nome' => trim($r['nome'] . ' ' . $r['cognome']),
            'ruolo' => $r['qualifica'],
            'sezione' => (string) $r['sezione'],
            'stato' => self::STATI_DB[$r['stato']],
            'email' => Cifratura::decifra($r['email_cifrata']),
            'bio' => (string) $r['bio'],
        ], $righe);
    }

    public static function trova(string $id): ?array
    {
        foreach (self::tutti() as $giornalista) {
            if ($giornalista['id'] === $id) {
                return $giornalista;
            }
        }
        return null;
    }

    public static function attivi(): array
    {
        return array_values(array_filter(self::tutti(), static fn (array $g): bool => $g['stato'] === 'Attivo'));
    }

    /** Crea l'account: il giornalista riceve un'email per scegliere la password. */
    public static function crea(array $dati): void
    {
        [$nome, $cognome] = nome_cognome($dati['nome']);
        Account::creaInvitato('giornalista', $nome, $cognome, $dati['email'], [
            'giornale_id' => Testata::dbId(Auth::testata()),
            'qualifica' => $dati['ruolo'],
            'sezione' => $dati['sezione'],
            'bio' => $dati['bio'],
        ]);
    }

    /**
     * Aggiorna scheda e stato. L'editore può attivare (cioè approvare) o sospendere,
     * ma non può attivare chi non ha ancora confermato la propria email.
     */
    public static function salva(array $giornalista): void
    {
        [$nome, $cognome] = nome_cognome($giornalista['nome']);
        Database::esegui(
            "UPDATE giornalisti gi JOIN generalita g ON g.id = gi.generalita_id JOIN giornali j ON j.id = gi.giornale_id
             SET g.nome = ?, g.cognome = ?, gi.qualifica = ?, gi.sezione = ?, gi.bio = ?,
                 g.stato = IF(g.stato = 'da_verificare', g.stato, ?)
             WHERE gi.id = ? AND j.slug = ?",
            [$nome, $cognome, $giornalista['ruolo'], $giornalista['sezione'], $giornalista['bio'],
                $giornalista['stato'] === 'Attivo' ? 'attivo' : 'sospeso', (int) $giornalista['id'], Auth::testata()]
        );
    }
}
