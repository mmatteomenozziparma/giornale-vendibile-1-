<?php
/**
 * Atlante Civico: fascicolo di un cliente con documenti, firme e stato.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
$fid = $fascicolo['id'];
$nascosto = '<input type="hidden" name="fascicolo" value="' . e($fid) . '">';
$giorno = static fn (?string $data): string => $data ? data_it(ora_locale($data, 'Y-m-d')) : '—';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Atlante Civico · fascicolo <?= e($fascicolo['codice']) ?></span>
    <h1><?= e($fascicolo['titolo']) ?></h1>
    <p class="wp-intro"><?= e($fascicolo['cliente']) ?> · <?= bollino(Atlante::STATI_FASCICOLO[$fascicolo['stato']]) ?></p>
    <div class="d-flex flex-wrap align-items-end gap-2">
        <a class="btn btn-wp-bordo" href="<?= e(url('atlante/cliente', ['id' => $fascicolo['cliente_id']], 'fascicoli')) ?>">Torna al cliente</a>
        <?php if ($staff): ?>
            <form class="d-flex gap-2" method="post" action="<?= e(url('atlante/stato-fascicolo')) ?>">
                <?= Csrf::campo() ?><?= $nascosto ?>
                <select class="form-select w-auto" name="stato" aria-label="Stato del fascicolo">
                    <?php foreach (Atlante::STATI_FASCICOLO as $chiave => $etichetta): ?>
                        <option value="<?= e($chiave) ?>"<?= selezionato($chiave, $fascicolo['stato']) ?>><?= e($etichetta) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-wp-bordo" type="submit">Aggiorna stato</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<div class="wp-nota mb-3">I file sono conservati cifrati in un archivio privato: si scaricano solo da questa pagina, dopo i controlli sul tuo ruolo. Ogni scaricamento viene registrato.</div>

<?php if (!$fascicolo['documenti']): ?>
    <section class="wp-scheda mb-3"><p class="wp-sottotitolo mb-0">Il fascicolo non contiene ancora documenti.</p></section>
<?php endif; ?>

<?php foreach ($fascicolo['documenti'] as $d): ?>
    <section class="wp-scheda mb-3" id="documento-<?= e($d['id']) ?>">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h2 class="mb-0 flex-grow-1"><?= e($d['titolo']) ?></h2>
            <?= bollino(Atlante::STATI_DOCUMENTO[$d['stato']]) ?>
        </div>
        <p class="wp-sottotitolo">
            Modello <?= e($d['codice']) ?> · versione <?= e($d['versione']) ?> · revisione <?= (int) $d['revisione_cliente'] ?>
            <?= $d['verificato_il'] ? ' · verificato il ' . e($giorno($d['verificato_il'])) : '' ?><?= $d['approvato_il'] ? ' · approvato il ' . e($giorno($d['approvato_il'])) : '' ?>
        </p>
        <?php if ($d['ha_file']): ?>
            <p class="mb-2">
                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('atlante/scarica', ['cosa' => 'documento', 'id' => $d['id']])) ?>" data-spa="off"><i class="bi bi-download" aria-hidden="true"></i> Scarica il documento</a>
                <span class="wp-sottotitolo ms-2">SHA-256 <code><?= e(substr((string) $d['sha256'], 0, 16)) ?>…</code></span>
            </p>
        <?php endif; ?>

        <?php if ($staff): ?>
            <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                <?php if (in_array($d['stato'], ['bozza', 'da_verificare'], true)): ?>
                    <form class="d-flex flex-wrap align-items-end gap-2" method="post" enctype="multipart/form-data" action="<?= e(url('atlante/carica-documento')) ?>">
                        <?= Csrf::campo() ?><?= $nascosto ?>
                        <input type="hidden" name="documento" value="<?= e($d['id']) ?>">
                        <div><label class="form-label" for="file-<?= e($d['id']) ?>">File compilato (PDF, DOCX, ODT · max 10 MB)</label><input class="form-control" type="file" id="file-<?= e($d['id']) ?>" name="file" required accept=".pdf,.docx,.odt"></div>
                        <button class="btn btn-wp-bordo" type="submit"><?= $d['ha_file'] ? 'Sostituisci il file' : 'Carica il file' ?></button>
                    </form>
                <?php endif; ?>
                <form class="d-flex flex-wrap gap-2" method="post" action="<?= e(url('atlante/avanza-documento')) ?>">
                    <?= Csrf::campo() ?><?= $nascosto ?>
                    <input type="hidden" name="documento" value="<?= e($d['id']) ?>">
                    <?php if ($d['stato'] === 'da_verificare' && !$d['verificato_il']): ?><button class="btn btn-wp" type="submit" name="cosa" value="verifica">Segna come verificato</button><?php endif; ?>
                    <?php if ($d['stato'] === 'da_verificare' && $d['verificato_il']): ?><button class="btn btn-wp" type="submit" name="cosa" value="approva">Approva: pronto per la firma</button><?php endif; ?>
                    <?php if ($d['stato'] === 'pronto_firma'): ?><button class="btn btn-wp" type="submit" name="cosa" value="firmato">Segna come firmato</button><?php endif; ?>
                    <?php if (!in_array($d['stato'], ['firmato', 'annullato'], true)): ?><button class="btn btn-wp-bordo" type="submit" name="cosa" value="annulla" data-conferma="Annullare questo documento?">Annulla</button><?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <h3 class="wp-titolo-campi">Firme</h3>
        <?php if (!$d['firme']): ?>
            <p class="wp-sottotitolo">Nessuna firma registrata.</p>
        <?php else: ?>
            <ul class="wp-righe mb-3">
                <?php foreach ($d['firme'] as $f): ?>
                    <li class="d-flex flex-wrap align-items-center gap-2">
                        <span class="flex-grow-1">
                            <strong><?= e(Atlante::PARTI[$f['parte']]) ?></strong><?= $f['firmatario'] ? ' · ' . e($f['firmatario']) : '' ?>
                            <span class="wp-sottotitolo">· firmato il <?= e($giorno($f['firmato_il'])) ?></span>
                        </span>
                        <?= bollino($f['verificata_il'] ? 'Verificata' : 'Da verificare') ?>
                        <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('atlante/scarica', ['cosa' => 'firma', 'id' => $f['id']])) ?>" data-spa="off">Prova</a>
                        <?php if ($staff && !$f['verificata_il']): ?>
                            <form method="post" action="<?= e(url('atlante/verifica-firma')) ?>">
                                <?= Csrf::campo() ?><?= $nascosto ?>
                                <input type="hidden" name="firma" value="<?= e($f['id']) ?>">
                                <button class="btn btn-wp btn-sm" type="submit">Verifica firma</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($staff && $d['stato'] === 'pronto_firma'): ?>
            <form class="row g-2" method="post" enctype="multipart/form-data" action="<?= e(url('atlante/firma')) ?>">
                <?= Csrf::campo() ?><?= $nascosto ?>
                <input type="hidden" name="documento" value="<?= e($d['id']) ?>">
                <div class="col-md-2"><label class="form-label" for="parte-<?= e($d['id']) ?>">Parte</label>
                    <select class="form-select" id="parte-<?= e($d['id']) ?>" name="parte">
                        <?php foreach (Atlante::PARTI as $chiave => $etichetta): ?><option value="<?= e($chiave) ?>"><?= e($etichetta) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label" for="data-<?= e($d['id']) ?>">Firmato il</label><input class="form-control" type="date" id="data-<?= e($d['id']) ?>" name="firmato_il" required></div>
                <div class="col-md-3"><label class="form-label" for="email-<?= e($d['id']) ?>">Email del firmatario</label><input class="form-control" type="email" id="email-<?= e($d['id']) ?>" name="email" maxlength="254"></div>
                <div class="col-md-4"><label class="form-label" for="prova-<?= e($d['id']) ?>">Prova della firma</label><input class="form-control" type="file" id="prova-<?= e($d['id']) ?>" name="prova" required accept=".pdf,.docx,.odt,.png,.jpg,.jpeg"></div>
                <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Registra la firma</button></div>
            </form>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if ($staff): ?>
    <form class="wp-scheda" method="post" action="<?= e(url('atlante/documento')) ?>">
        <?= Csrf::campo() ?><?= $nascosto ?>
        <h2>Nuovo documento</h2>
        <p class="wp-sottotitolo">Si parte da una versione approvata di un modello MASTER adatto a questo tipo di cliente.</p>
        <?php if (!$versioni): ?>
            <p class="mb-0">Nessuna versione approvata disponibile: caricala e approvala in <a href="<?= e(url('atlante/modelli')) ?>">Modelli documentali</a>.</p>
        <?php else: ?>
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div class="flex-grow-1"><label class="form-label" for="versione">Modello</label>
                    <select class="form-select" id="versione" name="versione">
                        <?php foreach ($versioni as $vid => $nome): ?><option value="<?= e($vid) ?>"><?= e($nome) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-wp" type="submit">Crea documento</button>
            </div>
        <?php endif; ?>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
