<?php
/**
 * Letture reali degli articoli: contatore di ogni articolo e totale
 * giornaliero di ogni testata (tabella "letture_giornaliere").
 */
declare(strict_types=1);

final class Letture
{
    private const GIORNI = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];

    /** Conta una lettura dell'articolo aperto sul sito. */
    public static function segna(string $articoloId): void
    {
        Database::esegui('UPDATE articoli_giornali SET letture = letture + 1 WHERE id = ?', [(int) $articoloId]);
        Database::esegui(
            'INSERT INTO letture_giornaliere (giornale_id, giorno, letture)
             SELECT giornale_id, ?, 1 FROM articoli_giornali WHERE id = ?
             ON DUPLICATE KEY UPDATE letture = letture_giornaliere.letture + 1',
            [date('Y-m-d'), (int) $articoloId]
        );
    }

    /** Letture degli ultimi sette giorni di una testata: ['Ven' => 12, 'Sab' => 30, ...] fino a oggi. */
    public static function settimana(int $giornaleId): array
    {
        $salvate = array_column(Database::righe(
            'SELECT giorno, letture FROM letture_giornaliere WHERE giornale_id = ? AND giorno > ?',
            [$giornaleId, date('Y-m-d', strtotime('-7 days'))]
        ), 'letture', 'giorno');
        $settimana = [];
        for ($i = 6; $i >= 0; $i--) {
            $giorno = strtotime("-$i days");
            $settimana[self::GIORNI[(int) date('w', $giorno)]] = (int) ($salvate[date('Y-m-d', $giorno)] ?? 0);
        }
        return $settimana;
    }
}
