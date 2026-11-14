<?php
/**
 * Scheda di un politico. $tipo vale 'candidatura' (copertina terracotta, proposte)
 * oppure 'mandato' (copertina verde, atti e iniziative).
 */
$mandato = $tipo === 'mandato';
$altro = $mandato ? 'candidatura' : 'mandato';
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / <a href="<?= e(url('politica')) ?>">Politica</a> / <?= e($livello) ?>
</nav>

<section class="wp-copertina-persona<?= $mandato ? ' wp-copertina-mandato' : '' ?>">
    <div>
        <span class="wp-occhiello"><?= $mandato ? 'Mandato' : 'Candidatura' ?> · scenario demo</span>
        <h1><?= e($politico['nome']) ?></h1>
        <p class="wp-ruolo"><?= e($dati['ruolo']) ?></p>
        <p><?= e($dati['luogo']) ?></p>
        <?php if ($mandato && !empty($dati['incarico'])): ?>
            <p class="wp-incarico"><?= e($dati['incarico']) ?> · incarico demo</p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-wp-chiaro" href="#voci"><?= $mandato ? 'Consulta gli atti' : 'Leggi le proposte' ?></a>
            <a class="btn btn-wp-vetro" href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => $altro])) ?>">
                <?= $mandato ? 'Vedi la candidatura' : 'Vedi il mandato' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
    <span class="wp-cerchio" aria-hidden="true"><?= e(iniziali($politico['nome'])) ?></span>
</section>

<nav class="wp-ancore" aria-label="Sezioni della pagina">
    <a href="#profilo">Profilo</a>
    <a href="#voci"><?= $mandato ? 'Atti e iniziative' : 'Proposte' ?></a>
    <a href="#agenda">Agenda</a>
    <a href="#contatti">Contatti</a>
</nav>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="profilo">
            <span class="wp-occhiello"><?= $mandato ? 'Attività del mandato' : 'Presentazione' ?></span>
            <h2><?= $mandato ? 'Profilo e incarichi' : 'Profilo' ?></h2>
            <p class="wp-testo-breve"><?= e($politico['bio']) ?></p>
            <?php if ($mandato && !empty($dati['incarico'])): ?>
                <p class="fw-bold mb-0"><?= e($dati['incarico']) ?>.</p>
            <?php endif; ?>
            <p class="wp-sottotitolo mt-3 mb-0">
                <a href="<?= e(url('politica/partito', ['id' => $politico['partito']])) ?>"><?= e($politico['partito_nome']) ?></a> · partito dimostrativo
            </p>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="voci">
            <span class="wp-occhiello"><?= $mandato ? 'Rendiconto dimostrativo' : 'Programma dimostrativo' ?></span>
            <h2><?= $mandato ? 'Atti e iniziative' : 'Proposte' ?></h2>
            <?php if (!$dati['voci']): ?>
                <p class="wp-sottotitolo mb-0"><?= $mandato ? 'Nessun atto pubblicato.' : 'Nessuna proposta pubblicata.' ?></p>
            <?php else: ?>
                <div class="wp-fisarmonica">
                    <?php foreach ($dati['voci'] as $indice => $voceElenco): ?>
                        <details>
                            <summary>
                                <span>
                                    <strong><?= e($voceElenco['titolo']) ?></strong>
                                    <small><?= $mandato ? e(($voceElenco['tipo'] ?? 'Atto') . ' · ' . ($voceElenco['stato'] ?? '')) : 'Proposta ' . sprintf('%02d', $indice + 1) ?></small>
                                </span>
                            </summary>
                            <p><?= e($voceElenco['testo'] !== '' ? $voceElenco['testo'] : 'Descrizione non disponibile.') ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
                <?php if ($mandato): ?>
                    <p class="wp-sottotitolo mt-3 mb-0">Gli stati mostrano come rendicontare l'iter di un atto.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="agenda">
            <span class="wp-occhiello"><?= $mandato ? 'Calendario del mandato' : 'Calendario della candidatura' ?></span>
            <h2>Agenda</h2>
            <?php if (!$dati['agenda']): ?>
                <p class="wp-sottotitolo mb-0">Nessun appuntamento in agenda.</p>
            <?php else: ?>
                <ul class="wp-righe">
                    <?php foreach ($dati['agenda'] as $appuntamento): ?>
                        <?php [$giorno, $mese] = giorno_mese($appuntamento['data']); ?>
                        <li class="d-flex align-items-center gap-3">
                            <span class="wp-data"><b><?= e($giorno) ?></b><small><?= e($mese) ?></small></span>
                            <span>
                                <strong class="d-block"><?= e($appuntamento['titolo']) ?></strong>
                                <span class="wp-sottotitolo"><?= e($appuntamento['dettaglio']) ?> · evento fittizio</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="wp-scheda h-100" id="contatti">
            <span class="wp-occhiello">Recapito fittizio</span>
            <h2>Contatti</h2>
            <p class="wp-testo-breve"><?= e($dati['contatto']) ?></p>
            <p class="wp-recapito mb-0"><?= e($dati['email']) ?></p>
        </section>
    </div>
</div>

<?php if ($mandato && !empty($dati['link_url']) && url_https_valido($dati['link_url'])): ?>
    <p class="wp-sottotitolo mt-3">
        Per consultare un esempio istituzionale:
        <a class="fw-bold" href="<?= e($dati['link_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($dati['link_etichetta']) ?></a>.
    </p>
<?php endif; ?>
