<?php
/** Scheda di un evento: organizzatori, invitati, partecipanti e azioni disponibili per chi è collegato. */
$statiInvito = ['in_attesa' => 'In attesa', 'accettato' => 'Accettato', 'rifiutato' => 'Rifiutato'];
$azione = static fn (string $rotta, string $etichetta, string $classe = 'btn-wp', string $extra = ''): string =>
    '<form method="post" action="' . e(url($rotta)) . '">' . Csrf::campo() . '<input type="hidden" name="id" value="' . e($evento['id']) . '">' . $extra
    . '<button class="btn ' . $classe . '" type="submit">' . e($etichetta) . '</button></form>';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Evento <?= $evento['pubblico'] ? 'pubblico' : 'riservato' ?></span>
    <h1><?= e($evento['titolo']) ?></h1>
    <p class="wp-intro">
        <?= e(data_it($evento['data'])) ?><?= $evento['ora'] !== '' ? ' · ore ' . e($evento['ora']) : '' ?><?= $evento['luogo'] !== '' ? ' · ' . e($evento['luogo']) : '' ?>
    </p>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($evento['da_confermare']): ?>
            <?= $azione('eventi/conferma', 'Accetta di co-organizzare') ?>
        <?php endif; ?>
        <?php if ($evento['mio_invito'] === 'in_attesa'): ?>
            <?= $azione('eventi/rispondi', 'Accetta l\'invito', 'btn-wp', '<input type="hidden" name="risposta" value="accetta">') ?>
            <?= $azione('eventi/rispondi', 'Rifiuta', 'btn-wp-bordo', '<input type="hidden" name="risposta" value="rifiuta">') ?>
        <?php elseif ($puoPartecipare && !$evento['partecipo']): ?>
            <?= $azione('eventi/partecipa', 'Partecipa') ?>
        <?php endif; ?>
        <?php if ($evento['partecipo']): ?>
            <span class="wp-stato wp-stato-ok">Partecipi</span>
        <?php endif; ?>
        <?php if ($evento['organizzo'] || $evento['creato_da'] === Auth::id()): ?>
            <?= $azione('eventi/annulla', 'Annulla evento', 'btn-wp-bordo') ?>
        <?php endif; ?>
        <a class="btn btn-wp-bordo" href="<?= e(url('eventi')) ?>">Torna agli eventi</a>
    </div>
</section>

<div class="row g-3 align-items-start">
    <div class="col-xl-6">
        <?php if ($evento['descrizione'] !== ''): ?>
            <section class="wp-scheda mb-3">
                <h2>Descrizione</h2>
                <?= paragrafi($evento['descrizione']) ?>
            </section>
        <?php endif; ?>

        <section class="wp-scheda mb-3" id="organizzatori">
            <h2>Organizzatori</h2>
            <ul class="wp-righe">
                <?php foreach ($evento['organizzatori'] as $o): ?>
                    <li class="d-flex align-items-center gap-3">
                        <span class="flex-grow-1"><strong><?= e($o['nome']) ?></strong> <span class="wp-sottotitolo">· <?= e($o['tipo']) ?></span></span>
                        <?= (int) $o['confermato'] === 1 ? '' : '<span class="wp-stato wp-stato-attenzione">Da confermare</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="wp-scheda" id="partecipanti">
            <h2>Partecipanti (<?= count($evento['partecipanti']) ?>)</h2>
            <?php if (!$evento['partecipanti']): ?>
                <p class="wp-sottotitolo mb-0">Ancora nessun partecipante.</p>
            <?php else: ?>
                <ul class="wp-righe">
                    <?php foreach ($evento['partecipanti'] as $p): ?>
                        <li><strong><?= e($p['nome']) ?></strong> <span class="wp-sottotitolo">· <?= e($p['tipo']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-xl-6">
        <section class="wp-scheda" id="invitati">
            <h2>Invitati</h2>
            <?php if (!$evento['invitati']): ?>
                <p class="wp-sottotitolo">Nessun invito spedito.</p>
            <?php else: ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($evento['invitati'] as $i): ?>
                        <li class="d-flex align-items-center gap-3">
                            <span class="flex-grow-1"><strong><?= e($i['nome']) ?></strong> <span class="wp-sottotitolo">· <?= e($i['tipo']) ?></span></span>
                            <?= bollino($statiInvito[$i['stato']] ?? $i['stato']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($evento['organizzo']): ?>
                <h3 class="wp-titolo-campi">Invita</h3>
                <form method="post" action="<?= e(url('eventi/invita')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= e($evento['id']) ?>">
                    <p class="form-text mt-0">Ogni invitato riceve un'email. Tieni premuto Ctrl (o Cmd) per sceglierne più di uno.</p>
                    <div class="mb-3">
                        <label class="form-label" for="inv-politici">Politici</label>
                        <select class="form-select" id="inv-politici" name="politici[]" multiple size="5">
                            <?php foreach ($politici as $id => $nome): ?>
                                <option value="<?= e($id) ?>"><?= e($nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="inv-giornalisti">Giornalisti</label>
                        <select class="form-select" id="inv-giornalisti" name="giornalisti[]" multiple size="5">
                            <?php foreach ($giornalisti as $id => $nome): ?>
                                <option value="<?= e($id) ?>"><?= e($nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-wp" type="submit">Spedisci gli inviti</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
