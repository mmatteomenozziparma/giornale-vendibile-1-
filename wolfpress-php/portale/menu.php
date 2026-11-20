<?php
if (!function_exists('pagina')) { http_response_code(404); exit; }   // non si apre da solo: lo includono le pagine
/**
 * MENU del portale: menu laterale e barra in alto. Incluso in ogni pagina dopo header.php;
 * apre anche <main>, che footer.php chiude.
 *
 * Il menu è CONTESTUALE. Il portale ha tre contesti, ognuno con nome, colore e voci proprie:
 *
 *   wolfpress  la piattaforma del fornitore: infrastruttura, personale, account, assistenza
 *   giornale   il lavoro delle redazioni: testata, articoli, redazione, assistenza
 *   atlante    Atlante Civico: schede dei politici, eventi, partiti, clienti e fascicoli
 *
 * Il contesto dipende dalla pagina aperta ($area e $voce, impostate dal controller) e dal ruolo.
 * Per aggiungere una pagina al menu basta una riga nell'elenco del ruolo e del contesto giusti:
 *     [area, voce, etichetta, icona Bootstrap, indirizzo]
 * La voce è evidenziata quando area e voce coincidono con quelle della pagina.
 */
$ruolo = $utente['ruolo'] ?? '';
$moduli = $impostazioni['moduli'] ?? [];
$testataNome = $impostazioni['testata'] ?? 'Giornale';

// --- 1. In quale contesto siamo ---
if ($area === 'tecnico') {
    $contesto = in_array($voce, ['clienti', 'referente'], true) ? 'giornale' : ($voce === 'partiti' ? 'atlante' : 'wolfpress');
} elseif (in_array($area, ['atlante', 'politico'], true) || ($area === 'eventi' && $ruolo === 'politico')) {
    $contesto = 'atlante';
} elseif (in_array($area, ['configuratore', 'cliente', 'editore', 'assistenza', 'giornalista', 'testata', 'eventi'], true)) {
    $contesto = 'giornale';
} else {   // pagina iniziale, accesso, errori: il contesto principale del ruolo
    $contesto = ['politico' => 'atlante', 'editore' => 'giornale', 'giornalista' => 'giornale'][$ruolo] ?? 'wolfpress';
}

// --- 2. Nome, sigla e colore di ogni contesto ---
$staffTecnico = $ruolo === 'tecnico';
$contesti = [
    'wolfpress' => ['WolfPress', $utente ? 'Piattaforma del fornitore' : 'Portale editoriale', 'WP', '#286347', url($staffTecnico ? 'tecnico' : 'home')],
    'giornale' => [$staffTecnico ? 'Giornali' : $testataNome, $staffTecnico ? 'Testate ospitate' : 'Redazione', $staffTecnico ? 'GI' : (sigla($testataNome) ?: 'GI'),
        $staffTecnico ? '#1f5a7a' : ($impostazioni['colore'] ?? '#286347'),
        url(['tecnico' => 'tecnico/clienti', 'giornalista' => 'giornalista'][$ruolo] ?? 'cliente')],
    'atlante' => ['Atlante Civico', 'Politica e comunicazione', 'AC', '#8a4631', url($ruolo === 'politico' ? 'politico' : 'atlante')],
];

// --- 3. Contesti disponibili per il ruolo (compaiono come schede in alto) ---
$contestiRuolo = [
    'tecnico' => ['wolfpress', 'giornale', 'atlante'],
    'editore' => ['giornale', 'atlante'],
    'giornalista' => ['giornale', 'atlante'],
    'politico' => ['atlante'],
][$ruolo] ?? [];

// --- 4. Voci del menu: per ruolo e per contesto, divise in gruppi ---
$voci = [
    'tecnico' => [
        'wolfpress' => [
            'Piattaforma' => [
                ['tecnico', 'infrastruttura', 'Infrastruttura', 'bi-hdd-network', url('tecnico')],
                ['tecnico', 'personale', 'Personale tecnico', 'bi-person-gear', url('tecnico/personale')],
                ['tecnico', 'account', 'Account da approvare', 'bi-person-check', url('tecnico/account')],
            ],
            'Assistenza' => [
                ['tecnico', 'ticket', 'Ticket', 'bi-ticket-perforated', url('tecnico/ticket')],
                ['tecnico', 'chat', 'Chat di assistenza', 'bi-chat-dots', url('tecnico/chat')],
            ],
        ],
        'giornale' => [
            'Giornali' => [
                ['tecnico', 'clienti', 'Clienti e testate', 'bi-building', url('tecnico/clienti')],
                ['tecnico', 'referente', 'Referente cliente', 'bi-person-lines-fill', url('tecnico/referente')],
            ],
        ],
        'atlante' => [
            'Atlante Civico' => [
                ['atlante', 'clienti', 'Clienti e fascicoli', 'bi-folder2-open', url('atlante')],
                ['atlante', 'modelli', 'Modelli documentali', 'bi-file-earmark-ruled', url('atlante/modelli')],
                ['tecnico', 'partiti', 'Partiti', 'bi-flag', url('tecnico/partiti')],
            ],
        ],
    ],
    'editore' => [
        'giornale' => [
            'Testata' => array_values(array_filter([
                ['configuratore', 'identita', 'Configuratore', 'bi-sliders', url('configuratore')],
                ['cliente', 'panoramica', 'Area cliente', 'bi-grid', url('cliente')],
                !empty($moduli['articoli']) ? ['cliente', 'articoli', 'Articoli', 'bi-file-earmark-text', url('cliente', [], 'articoli')] : null,
                !empty($moduli['lettori']) ? ['cliente', 'lettori', 'Lettori', 'bi-bar-chart', url('cliente', [], 'lettori')] : null,
            ])),
            'Redazione' => [
                ['editore', 'account', 'Account giornalisti', 'bi-people', url('editore')],
                ['editore', 'chat', 'Chat e video', 'bi-chat-left-text', url('editore', [], 'chat')],
                ['eventi', 'elenco', 'Eventi', 'bi-calendar-event', url('eventi')],
            ],
            'Assistenza' => [
                ['assistenza', 'ticket', 'I miei ticket', 'bi-ticket-perforated', url('assistenza')],
                ['assistenza', 'chat', 'Chat di assistenza', 'bi-chat-dots', url('assistenza/chat')],
            ],
        ],
        'atlante' => ['Atlante Civico' => [['atlante', 'clienti', 'Clienti e fascicoli', 'bi-folder2-open', url('atlante')]]],
    ],
    'giornalista' => [
        'giornale' => [
            'Redazione' => [
                ['giornalista', 'portfolio', 'Profilo e portfolio', 'bi-person-badge', url('giornalista')],
                ['giornalista', 'eventi', 'Agenda', 'bi-calendar-week', url('giornalista', [], 'eventi')],
                ['giornalista', 'chat', 'Chat e video', 'bi-chat-left-text', url('giornalista', [], 'chat')],
                ['giornalista', 'articoli', 'I miei articoli', 'bi-pencil-square', url('articoli')],
                ['eventi', 'elenco', 'Eventi e inviti', 'bi-envelope-paper', url('eventi')],
            ],
        ],
        'atlante' => ['Atlante Civico' => [['atlante', 'clienti', 'Clienti e fascicoli', 'bi-folder2-open', url('atlante')]]],
    ],
    'politico' => [
        'atlante' => [
            'La tua pagina' => [
                ['politico', 'candidatura', 'Candidatura', 'bi-megaphone', url('politico', ['scenario' => 'candidatura'])],
                ['politico', 'mandato', 'Mandato', 'bi-bank', url('politico', ['scenario' => 'mandato'])],
                ['politico', 'articoli', 'I miei articoli', 'bi-pencil-square', url('politico/articoli')],
            ],
            'Agenda' => [
                ['eventi', 'elenco', 'Eventi e inviti', 'bi-calendar-event', url('eventi')],
            ],
            'Sito e documenti' => [
                ['atlante', 'clienti', 'Clienti e fascicoli', 'bi-folder2-open', url('atlante')],
            ],
        ],
    ],
];
if ($ruolo === '') {
    $gruppi = ['Accesso' => [
        ['accesso', 'entra', 'Accedi', 'bi-box-arrow-in-right', url('accesso')],
        ['accesso', 'registrazione', 'Registrati', 'bi-person-plus', url('accesso/registrazione')],
        ['accesso', 'password', 'Password dimenticata', 'bi-key', url('accesso/password')],
    ]];
} elseif ($area === 'testata' && Auth::testata() === '') {
    $gruppi = ['Primo avvio' => [['testata', 'nuova', 'Crea la tua testata', 'bi-newspaper', url('testata')]]];
} else {
    $gruppi = ['Portale' => [['home', 'home', 'Le tue aree', 'bi-grid', url('home')]]] + ($voci[$ruolo][$contesto] ?? []);
}

[$nomeContesto, $sottotitolo, $siglaContesto, $coloreContesto] = $contesti[$contesto];
// Il logo caricato dal configuratore appartiene alla testata: si mostra solo nel contesto del giornale.
$logo = $contesto === 'giornale' && !$staffTecnico ? ($impostazioni['logo'] ?? '') : '';
?>
<div class="wp-griglia wp-contesto-<?= e($contesto) ?>" style="--wp-primary: <?= e($coloreContesto) ?>">

    <aside class="wp-laterale offcanvas-lg offcanvas-start" id="wp-laterale" tabindex="-1" aria-label="Menu del portale">
        <div class="wp-laterale-interno">
            <div class="d-flex align-items-center justify-content-between">
                <a class="wp-marchio" href="<?= e($utente ? url('home') : url('accesso')) ?>">
                    <?php if (is_array($logo)): ?>
                        <img class="wp-marchio-logo" src="<?= e(url('media/logo', ['v' => $logo['versione'] ?? 1])) ?>" alt="">
                    <?php else: ?>
                        <span class="wp-marchio-sigla" aria-hidden="true"><?= e($siglaContesto) ?></span>
                    <?php endif; ?>
                    <span>
                        <strong><?= e($nomeContesto) ?></strong>
                        <small><?= e($sottotitolo) ?></small>
                    </span>
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#wp-laterale" aria-label="Chiudi il menu"></button>
            </div>

            <nav class="wp-menu" aria-label="Menu di <?= e($nomeContesto) ?>">
                <?php foreach ($gruppi as $titoloGruppo => $vociGruppo): ?>
                    <p class="wp-menu-titolo"><?= e($titoloGruppo) ?></p>
                    <ul>
                        <?php foreach ($vociGruppo as [$areaVoce, $chiave, $etichetta, $icona, $indirizzo]): ?>
                            <?php $attiva = $areaVoce === $area && $chiave === $voce; ?>
                            <li>
                                <a href="<?= e($indirizzo) ?>" class="<?= $attiva ? 'attiva' : '' ?>"<?= $attiva ? ' aria-current="page"' : '' ?>>
                                    <i class="bi <?= e($icona) ?>" aria-hidden="true"></i>
                                    <span><?= e($etichetta) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </nav>

            <div class="wp-laterale-piede">
                <?php if ($utente): ?>
                    <strong><?= e($utente['nome']) ?></strong>
                    <small><?= e(Auth::etichettaRuolo()) ?></small>
                <?php else: ?>
                    <small>Accesso riservato</small>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <div class="wp-corpo">
        <header class="wp-barra">
            <?php require __DIR__ . '/parti/topbar.php'; ?>
        </header>

        <main id="wp-main" class="wp-main" tabindex="-1">
            <?php if (!empty($avviso)): ?>
                <div class="wp-avviso wp-avviso-<?= e($avviso['tipo']) ?>" role="status">
                    <i class="bi <?= $avviso['tipo'] === 'errore' ? 'bi-exclamation-triangle' : 'bi-check2-circle' ?>" aria-hidden="true"></i>
                    <span><?= e($avviso['testo']) ?></span>
                </div>
            <?php endif; ?>
