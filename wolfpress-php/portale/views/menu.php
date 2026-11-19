<?php
/**
 * Menu laterale del portale. Cambia in base all'area ($area) e
 * evidenzia la voce corrente ($voce). Per aggiungere una pagina al menu
 * basta inserire una riga nell'elenco dell'area giusta.
 *
 * Ogni voce: [chiave, etichetta, icona Bootstrap, indirizzo]
 */
$moduli = $impostazioni['moduli'] ?? [];

$menu = [
    'home' => ['Portale', 'Portale WolfPress', [
        ['home', 'Le tue aree', 'bi-grid', url('home')],
    ]],
    'accesso' => ['Accesso', 'Portale editoriale', [
        ['entra', 'Accedi', 'bi-box-arrow-in-right', url('accesso')],
        ['registrazione', 'Registrati', 'bi-person-plus', url('accesso/registrazione')],
        ['password', 'Password dimenticata', 'bi-key', url('accesso/password')],
    ]],
    'testata' => ['Le tue testate', 'Portale editoriale', [
        ['nuova', 'Nuova testata', 'bi-newspaper', url('testata')],
    ]],
    'eventi' => ['Agenda condivisa', 'Eventi', array_values(array_filter([
        ['elenco', 'Eventi', 'bi-calendar-event', url('eventi')],
        in_array($utente['ruolo'] ?? '', ['editore', 'politico'], true) ? ['nuovo', 'Nuovo evento', 'bi-calendar-plus', url('eventi', [], 'nuovo')] : null,
    ]))],
    'configuratore' => ['Impostazioni', 'Studio editoriale', [
        ['identita', 'Identità', 'bi-sliders', url('configuratore')],
        ['moduli', 'Moduli cliente', 'bi-grid', url('configuratore', [], 'moduli')],
    ]],
    'cliente' => ['Redazione', 'Area cliente', array_values(array_filter([
        ['panoramica', 'Panoramica', 'bi-grid', url('cliente')],
        !empty($moduli['articoli']) ? ['articoli', 'Articoli', 'bi-file-earmark-text', url('cliente', [], 'articoli')] : null,
        !empty($moduli['lettori']) ? ['lettori', 'Lettori', 'bi-bar-chart', url('cliente', [], 'lettori')] : null,
        !empty($moduli['redazione']) ? ['redazione', 'Redazione', 'bi-people', url('cliente', [], 'redazione')] : null,
        !empty($moduli['assistenza']) ? ['assistenza', 'Assistenza', 'bi-chat-left-text', url('cliente', [], 'assistenza')] : null,
    ]))],
    'editore' => ['Redazione', 'Area editore', [
        ['account', 'Account giornalisti', 'bi-people', url('editore')],
        ['chat', 'Chat e video', 'bi-chat-left-text', url('editore', [], 'chat')],
    ]],
    'assistenza' => ['Area assistenza', 'Portale editoriale', [
        ['ticket', 'I miei ticket', 'bi-ticket-perforated', url('assistenza')],
        ['chat', 'Chat di assistenza', 'bi-chat-dots', url('assistenza/chat')],
    ]],
    'giornalista' => ['Redazione', 'Profilo giornalista', [
        ['portfolio', 'Portfolio', 'bi-file-earmark-text', url('giornalista')],
        ['eventi', 'Agenda', 'bi-calendar-event', url('giornalista', [], 'eventi')],
        ['inviti', 'Eventi e inviti', 'bi-envelope-paper', url('eventi')],
        ['chat', 'Chat e video', 'bi-chat-left-text', url('giornalista', [], 'chat')],
        ['articoli', 'I miei articoli', 'bi-pencil-square', url('articoli')],
    ]],
    'politico' => ['La tua pagina', 'Area politica', [
        ['candidatura', 'Candidatura', 'bi-megaphone', url('politico', ['scenario' => 'candidatura'])],
        ['mandato', 'Mandato', 'bi-bank', url('politico', ['scenario' => 'mandato'])],
        ['articoli', 'I miei articoli', 'bi-pencil-square', url('politico/articoli')],
        ['eventi', 'Eventi e inviti', 'bi-calendar-event', url('eventi')],
    ]],
    'tecnico' => ['Gestione tecnica', 'Console del fornitore', [
        ['infrastruttura', 'Infrastruttura', 'bi-hdd-network', url('tecnico')],
        ['personale', 'Personale tecnico', 'bi-person-gear', url('tecnico/personale')],
        ['account', 'Account da approvare', 'bi-person-check', url('tecnico/account')],
        ['partiti', 'Partiti', 'bi-flag', url('tecnico/partiti')],
        ['clienti', 'Clienti e testate', 'bi-building', url('tecnico/clienti')],
        ['referente', 'Referente cliente', 'bi-person-lines-fill', url('tecnico/referente')],
        ['ticket', 'Ticket di assistenza', 'bi-ticket-perforated', url('tecnico/ticket')],
        ['chat', 'Chat di assistenza', 'bi-chat-dots', url('tecnico/chat')],
    ]],
];

[$gruppo, $sottotitolo, $voci] = $menu[$area] ?? $menu['home'];
$logo = $impostazioni['logo'] ?? '';
?>
<div class="wp-laterale-interno">
    <div class="d-flex align-items-center justify-content-between">
        <a class="wp-marchio" href="<?= e($utente ? url('home') : url('accesso')) ?>">
            <?php if (is_array($logo)): ?>
                <img class="wp-marchio-logo" src="<?= e(url('media/logo', ['v' => $logo['versione'] ?? 1])) ?>" alt="">
            <?php else: ?>
                <span class="wp-marchio-sigla" aria-hidden="true"><?= e(sigla($impostazioni['testata'] ?? 'WolfPress') ?: 'WP') ?></span>
            <?php endif; ?>
            <span>
                <strong><?= e($impostazioni['testata'] ?? 'WolfPress') ?></strong>
                <small><?= e($sottotitolo) ?></small>
            </span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#wp-laterale" aria-label="Chiudi il menu"></button>
    </div>

    <nav class="wp-menu" aria-label="<?= e($gruppo) ?>">
        <p class="wp-menu-titolo"><?= e($gruppo) ?></p>
        <ul>
            <?php foreach ($voci as [$chiave, $etichetta, $icona, $indirizzo]): ?>
                <li>
                    <a href="<?= e($indirizzo) ?>" class="<?= $chiave === $voce ? 'attiva' : '' ?>"<?= $chiave === $voce ? ' aria-current="page"' : '' ?>>
                        <i class="bi <?= e($icona) ?>" aria-hidden="true"></i>
                        <span><?= e($etichetta) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
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
