<?php
/**
 * Atlante Civico: catalogo dei modelli documentali MASTER e delle loro versioni (solo staff).
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Atlante Civico</span>
    <h1>Modelli documentali</h1>
    <p class="wp-intro">Il catalogo MASTER vale per tutti i clienti. Ogni versione è autonoma: una versione già usata non si sovrascrive, se ne carica una nuova. Solo le versioni approvate generano documenti.</p>
</section>

<div class="row g-3 align-items-start">
    <div class="col-xl-8">
        <?php if (!$modelli): ?>
            <section class="wp-scheda"><p class="wp-sottotitolo mb-0">Nessun modello in catalogo.</p></section>
        <?php endif; ?>
        <?php foreach ($modelli as $m): ?>
            <section class="wp-scheda mb-3" id="modello-<?= e($m['id']) ?>">
                <h2 class="mb-1"><?= e($m['titolo']) ?></h2>
                <p class="wp-sottotitolo">
                    <?= e($m['codice']) ?> · <?= e(Atlante::CATEGORIE[$m['categoria']]) ?> · per: <?= e(mb_strtolower(Atlante::DESTINATARI[$m['destinatario']])) ?>
                    <?= $m['condizione_utilizzo'] ? ' · ' . e($m['condizione_utilizzo']) : '' ?>
                </p>
                <?php if ($m['versioni']): ?>
                    <ul class="wp-righe mb-3">
                        <?php foreach ($m['versioni'] as $v): ?>
                            <li class="d-flex flex-wrap align-items-center gap-2">
                                <span class="flex-grow-1"><strong>v<?= e($v['versione']) ?></strong> <span class="wp-sottotitolo">· SHA-256 <code><?= e(substr($v['sha256'], 0, 16)) ?>…</code></span></span>
                                <?= bollino(Atlante::STATI_VERSIONE[$v['stato']]) ?>
                                <a class="btn btn-wp-bordo btn-sm" href="<?= e(url('atlante/scarica', ['cosa' => 'versione', 'id' => $v['id']])) ?>" data-spa="off">Scarica</a>
                                <?php if ($v['stato'] !== 'ritirata'): ?>
                                    <form class="d-flex gap-1" method="post" action="<?= e(url('atlante/stato-versione')) ?>">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="versione" value="<?= e($v['id']) ?>">
                                        <?php if ($v['stato'] === 'bozza'): ?><button class="btn btn-wp-bordo btn-sm" type="submit" name="stato" value="da_verificare">Manda in verifica</button><?php endif; ?>
                                        <?php if ($v['stato'] === 'da_verificare'): ?><button class="btn btn-wp btn-sm" type="submit" name="stato" value="approvata">Approva</button><?php endif; ?>
                                        <button class="btn btn-wp-bordo btn-sm" type="submit" name="stato" value="ritirata" data-conferma="Ritirare questa versione? Non potrà più generare documenti.">Ritira</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form class="d-flex flex-wrap align-items-end gap-2" method="post" enctype="multipart/form-data" action="<?= e(url('atlante/versione')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="modello" value="<?= e($m['id']) ?>">
                    <div><label class="form-label" for="ver-<?= e($m['id']) ?>">Nuova versione</label><input class="form-control" id="ver-<?= e($m['id']) ?>" name="versione" maxlength="30" required placeholder="1.0"></div>
                    <div class="flex-grow-1"><label class="form-label" for="file-<?= e($m['id']) ?>">File (PDF, DOCX, ODT · max 10 MB)</label><input class="form-control" type="file" id="file-<?= e($m['id']) ?>" name="file" required accept=".pdf,.docx,.odt"></div>
                    <button class="btn btn-wp-bordo" type="submit">Carica</button>
                </form>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="col-xl-4">
        <form class="wp-scheda" method="post" action="<?= e(url('atlante/modello')) ?>">
            <?= Csrf::campo() ?>
            <h2 class="mb-3">Nuovo modello</h2>
            <div class="mb-3"><label class="form-label" for="mod-codice">Codice</label><input class="form-control" id="mod-codice" name="codice" maxlength="80" required placeholder="CONTRATTO-SITO"></div>
            <div class="mb-3"><label class="form-label" for="mod-titolo">Titolo</label><input class="form-control" id="mod-titolo" name="titolo" maxlength="240" required></div>
            <div class="mb-3"><label class="form-label" for="mod-categoria">Categoria</label>
                <select class="form-select" id="mod-categoria" name="categoria">
                    <?php foreach (Atlante::CATEGORIE as $chiave => $etichetta): ?><option value="<?= e($chiave) ?>"><?= e($etichetta) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label" for="mod-destinatario">Destinatario</label>
                <select class="form-select" id="mod-destinatario" name="destinatario">
                    <?php foreach (Atlante::DESTINATARI as $chiave => $etichetta): ?><option value="<?= e($chiave) ?>"><?= e($etichetta) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label" for="mod-condizione">Quando si usa (facoltativo)</label><input class="form-control" id="mod-condizione" name="condizione_utilizzo" maxlength="220" placeholder="Solo se è attiva la newsletter"></div>
            <button class="btn btn-wp" type="submit">Aggiungi al catalogo</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
