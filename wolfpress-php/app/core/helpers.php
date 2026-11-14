<?php
/**
 * Funzioni di aiuto usate nelle viste e nei controller.
 */
declare(strict_types=1);

/** Protegge un testo prima di stamparlo in HTML (anti XSS). */
function e($valore): string
{
    return htmlspecialchars((string) $valore, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Legge una voce di app/config.php. */
function config(string $chiave, $predefinito = null)
{
    return $GLOBALS['wp_config'][$chiave] ?? $predefinito;
}

/** Costruisce l'indirizzo di una rotta del front end corrente: url('notizie/articolo', ['slug' => 'x']). */
function url(string $rotta = '', array $parametri = [], string $ancora = ''): string
{
    return costruisci_url(APP_BASE, $rotta, $parametri, $ancora);
}

/** Indirizzo di una rotta del sito pubblico (usabile anche dal portale). */
function url_sito(string $rotta = '', array $parametri = [], string $ancora = ''): string
{
    return costruisci_url(ROOT_BASE . '/sito', $rotta, $parametri, $ancora);
}

/** Indirizzo di una rotta del portale (usabile anche dal sito). */
function url_portale(string $rotta = '', array $parametri = [], string $ancora = ''): string
{
    return costruisci_url(ROOT_BASE . '/portale', $rotta, $parametri, $ancora);
}

function costruisci_url(string $base, string $rotta, array $parametri, string $ancora): string
{
    $query = $rotta !== '' ? ['r' => $rotta] + $parametri : $parametri;
    $indirizzo = $base . '/index.php';
    if ($query) {
        $indirizzo .= '?' . str_replace('%2F', '/', http_build_query($query));
    }
    return $indirizzo . ($ancora !== '' ? '#' . $ancora : '');
}

/** File statico del front end corrente (css, js). */
function asset(string $percorso): string
{
    return APP_BASE . '/assets/' . ltrim($percorso, '/') . versione_file(dirname($_SERVER['SCRIPT_FILENAME']) . '/assets/' . $percorso);
}

/** File statico condiviso (Bootstrap, base.css). */
function asset_comune(string $percorso): string
{
    return ROOT_BASE . '/assets/' . ltrim($percorso, '/') . versione_file(WP_ROOT . '/assets/' . $percorso);
}

function versione_file(string $file): string
{
    return is_file($file) ? '?v=' . filemtime($file) : '';
}

/** Iniziali di un nome: "Giulia Serra" diventa "GS". */
function iniziali(string $nome): string
{
    $lettere = '';
    foreach (preg_split('/\s+/', trim($nome)) ?: [] as $parola) {
        if ($parola !== '') {
            $lettere .= mb_strtoupper(mb_substr($parola, 0, 1));
        }
    }
    return mb_substr($lettere, 0, 2);
}

/** Sigla della testata per il riquadro del marchio: "WolfPress" diventa "WP", "Il Punto" diventa "IP". */
function sigla(string $nome): string
{
    if (preg_match_all('/\p{Lu}/u', $nome, $maiuscole) && count($maiuscole[0]) >= 2) {
        return $maiuscole[0][0] . $maiuscole[0][1];
    }
    $iniziali = iniziali($nome);
    return mb_strlen($iniziali) >= 2 ? $iniziali : mb_strtoupper(mb_substr(trim($nome), 0, 2));
}

/** Classe CSS del bollino in base allo stato. */
function classe_stato(string $stato): string
{
    $mappa = [
        'Pubblicato' => 'ok', 'Attivo' => 'ok', 'Operativo' => 'ok', 'Aperto' => 'ok', 'Risolto' => 'neutro',
        'Normale' => 'ok', 'Bassa' => 'neutro', 'Alta' => 'attenzione', 'Urgente' => 'errore',
        'In revisione' => 'attenzione', 'In lavorazione' => 'attenzione', 'Manutenzione' => 'attenzione',
        'Bozza' => 'neutro', 'Sospeso' => 'neutro', 'Degradato' => 'errore',
    ];
    return 'wp-stato wp-stato-' . ($mappa[$stato] ?? 'neutro');
}

function bollino(string $stato): string
{
    return '<span class="' . classe_stato($stato) . '">' . e($stato) . '</span>';
}

/** Data in italiano: "2026-11-05" diventa "5 novembre 2026". */
function data_it(string $data, bool $conAnno = true): string
{
    $mesi = ['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
    $t = strtotime($data);
    if ($t === false) {
        return $data;
    }
    return (int) date('j', $t) . ' ' . $mesi[(int) date('n', $t)] . ($conAnno ? ' ' . date('Y', $t) : '');
}

/** Giorno e mese breve per i riquadri dell'agenda: ['18', 'nov']. */
function giorno_mese(string $data): array
{
    $mesi = ['', 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
    $t = strtotime($data);
    if ($t === false) {
        return ['--', ''];
    }
    return [date('d', $t), $mesi[(int) date('n', $t)]];
}

/** Numero con il punto delle migliaia: 14840 diventa "14.840". */
function numero(int $n): string
{
    return number_format($n, 0, ',', '.');
}

/** Trasforma il testo semplice di un articolo in paragrafi HTML sicuri. */
function paragrafi(string $testo): string
{
    $html = '';
    foreach (preg_split('/\R{2,}/', trim($testo)) ?: [] as $blocco) {
        if (trim($blocco) !== '') {
            $html .= '<p>' . nl2br(e(trim($blocco))) . '</p>';
        }
    }
    return $html;
}

/** Confronto di testo senza distinzione tra maiuscole e minuscole. */
function contiene(string $testo, string $ricerca): bool
{
    return $ricerca === '' || mb_stripos($testo, $ricerca) !== false;
}

/** Accetta solo indirizzi https validi (link delle videochiamate e dei social). */
function url_https_valido(string $indirizzo): bool
{
    return $indirizzo !== ''
        && mb_strlen($indirizzo) <= 300
        && filter_var($indirizzo, FILTER_VALIDATE_URL) !== false
        && stripos($indirizzo, 'https://') === 0;
}

/** Rende selezionata un'opzione di una select. */
function selezionato($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}
