<?php
/**
 * Barra in alto: nome dell'area, schede delle aree disponibili per il ruolo, menu dell'utente.
 */
$etichette = [
    'home' => 'Portale',
    'accesso' => 'Area editoriale',
    'configuratore' => 'Configuratore',
    'cliente' => 'Area cliente',
    'editore' => 'Area editore',
    'assistenza' => 'Area editoriale',
    'giornalista' => 'Profilo giornalista',
    'politico' => 'Area politica',
    'tecnico' => 'Fornitore del servizio',
];

// Schede visibili per ogni ruolo: [area, etichetta, indirizzo]
$schede = [
    'editore' => [
        ['configuratore', 'Configuratore', url('configuratore')],
        ['cliente', 'Area cliente', url('cliente')],
        ['editore', 'Editore', url('editore')],
        ['assistenza', 'Assistenza', url('assistenza')],
    ],
    'giornalista' => [
        ['giornalista', 'Giornalista', url('giornalista')],
    ],
    'politico' => [
        ['politico', 'La mia pagina', url('politico')],
    ],
    'tecnico' => [
        ['tecnico', 'Gestione tecnica', url('tecnico')],
    ],
];
$ruolo = $utente['ruolo'] ?? '';
?>
<button class="btn btn-wp-bordo btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#wp-laterale" aria-controls="wp-laterale" aria-label="Apri il menu">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>

<span class="wp-barra-titolo"><?= e($etichette[$area] ?? 'Portale') ?></span>

<div class="wp-barra-azioni">
    <span class="wp-pillola d-none d-md-inline-flex">Modalità demo</span>

    <?php if ($utente): ?>
        <nav class="wp-schede" aria-label="Aree del portale">
            <?php foreach ($schede[$ruolo] ?? [] as [$chiave, $etichetta, $indirizzo]): ?>
                <a class="btn btn-wp-bordo<?= $chiave === $area ? ' attiva' : '' ?>" href="<?= e($indirizzo) ?>"<?= $chiave === $area ? ' aria-current="true"' : '' ?>><?= e($etichetta) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="dropdown">
            <button class="btn btn-wp-bordo dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="wp-avatar wp-avatar-mini" aria-hidden="true"><?= e(iniziali($utente['nome'])) ?></span>
                <span class="d-none d-xl-inline"><?= e($utente['nome']) ?></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <p class="dropdown-header">
                    <strong><?= e($utente['nome']) ?></strong><br>
                    <?= e(Auth::etichettaRuolo()) ?>
                </p>
                <a class="dropdown-item" href="<?= e(url('home')) ?>"><i class="bi bi-grid" aria-hidden="true"></i> Le tue aree</a>
                <a class="dropdown-item" href="<?= e(url_sito()) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Apri il sito pubblico</a>
                <a class="dropdown-item" href="<?= e(url('accesso')) ?>"><i class="bi bi-people" aria-hidden="true"></i> Accessi demo</a>
                <div class="dropdown-divider"></div>
                <form method="post" action="<?= e(url('demo/ripristina')) ?>">
                    <?= Csrf::campo() ?>
                    <button class="dropdown-item" type="submit"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Ripristina dati demo</button>
                </form>
                <form method="post" action="<?= e(url('accesso/esci')) ?>">
                    <?= Csrf::campo() ?>
                    <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Esci dalla demo</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <a class="btn btn-wp-bordo" href="<?= e(url('accesso')) ?>">Accessi demo</a>
        <a class="btn btn-wp-bordo" href="<?= e(url_sito()) ?>" data-spa="off">Sito pubblico</a>
    <?php endif; ?>
</div>
