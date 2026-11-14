<?php
/**
 * Impostazioni della testata scelte nel configuratore
 * (nome, colore, logo, moduli visibili al cliente, canali della chat).
 */
declare(strict_types=1);

final class Impostazioni
{
    public const MODULI = [
        'articoli' => ['Articoli', 'Bozze e stato del lavoro editoriale.', 'bi-file-earmark-text'],
        'lettori' => ['Lettori', 'Andamento delle letture di esempio.', 'bi-bar-chart'],
        'redazione' => ['Redazione', 'Ruoli e referenti del giornale.', 'bi-people'],
        'assistenza' => ['Assistenza', 'Contatto per richieste tecniche.', 'bi-chat-left-text'],
    ];

    public static function leggi(): array
    {
        return Store::leggi('impostazioni');
    }

    public static function salva(array $impostazioni): void
    {
        Store::scrivi('impostazioni', $impostazioni);
    }

    public static function moduloAttivo(string $modulo): bool
    {
        return !empty(self::leggi()['moduli'][$modulo]);
    }

    /** Link https della videochiamata di un canale della chat di redazione. */
    public static function video(string $canale): string
    {
        return self::leggi()['video'][$canale] ?? '';
    }
}
