<?php
/**
 * Barra in alto: nome dell'area, schede delle aree disponibili per il ruolo, menu dell'utente.
 */
$etichette = [
    'home' => 'Portale',
    'accesso' => 'Area editoriale',
    'testata' => 'Le tue testate',
    'eventi' => 'Eventi',
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
        ['eventi', 'Eventi', url('eventi')],
    ],
    'giornalista' => [
        ['giornalista', 'Giornalista', url('giornalista')],
        ['eventi', 'Eventi', url('eventi')],
    ],
    'politico' => [
        ['politico', 'La mia pagina', url('politico')],
        ['eventi', 'Eventi', url('eventi')],
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
                <?php if ($ruolo === 'editore'): ?>
                    <div class="dropdown-divider"></div>
                    <p class="dropdown-header">Le tue testate</p>
                    <?php foreach (Auth::testate() as $slugTestata => $nomeTestata): ?>
                        <form method="post" action="<?= e(url('testata/scegli')) ?>">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="testata" value="<?= e($slugTestata) ?>">
                            <button class="dropdown-item" type="submit"<?= $slugTestata === Auth::testata() ? ' aria-current="true"' : '' ?>>
                                <i class="bi <?= $slugTestata === Auth::testata() ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i> <?= e($nomeTestata) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                    <a class="dropdown-item" href="<?= e(url('testata')) ?>"><i class="bi bi-plus-circle" aria-hidden="true"></i> Nuova testata</a>
                    <div class="dropdown-divider"></div>
                <?php endif; ?>
                <a class="dropdown-item" href="<?= e(url_sito()) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Apri il sito pubblico</a>
                <div class="dropdown-divider"></div>
                <form method="post" action="<?= e(url('accesso/esci')) ?>">
                    <?= Csrf::campo() ?>
                    <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Esci</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <a class="btn btn-wp-bordo" href="<?= e(url('accesso')) ?>">Accedi</a>
        <a class="btn btn-wp-bordo" href="<?= e(url_sito()) ?>" data-spa="off">Sito pubblico</a>
    <?php endif; ?>
</div>
