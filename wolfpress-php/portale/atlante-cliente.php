<?php
/**
 * Atlante Civico: scheda di un cliente con referenti, siti e funzioni, offerte, campi, fascicoli, verifiche e registro.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
$id = $cliente['id'];
$nascosto = '<input type="hidden" name="cliente" value="' . e($id) . '">';
$opzioni = static function (array $voci, $scelta = ''): string {
    $html = '';
    foreach ($voci as $valore => $etichetta) {
        $html .= '<option value="' . e($valore) . '"' . selezionato($valore, $scelta) . '>' . e($etichetta) . '</option>';
    }
    return $html;
};
$euro = static fn ($centesimi): string => '€ ' . number_format((int) $centesimi / 100, 2, ',', '.');
$giorno = static fn (?string $data): string => $data ? data_it(ora_locale($data, 'Y-m-d')) : '—';
$nomiSiti = array_column($siti, 'nome', 'id');
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Atlante Civico · <?= e(Atlante::TIPI_CLIENTE[$cliente['tipo']]) ?></span>
    <h1><?= e($cliente['denominazione']) ?></h1>
    <p class="wp-intro">
        <?= bollino(Atlante::STATI_CLIENTE[$cliente['stato']]) ?>
        <?php if (!$staff): ?>
            · il tuo ruolo: <?= e(implode(', ', array_map(static fn ($r) => Atlante::RUOLI[$r], $cliente['mie']))) ?>
        <?php endif; ?>
    </p>
    <a class="btn btn-wp-bordo" href="<?= e(url('atlante')) ?>">Tutti i clienti</a>
</section>

<div class="wp-nota mb-3">Quanto registrato qui documenta il lavoro svolto: non è una certificazione di conformità al GDPR o all'AI Act. Verifiche, approvazioni e firme valgono per le prove reali che le sostengono.</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-6">
        <section class="wp-scheda mb-3" id="anagrafica">
            <h2 class="mb-3">Anagrafica</h2>
            <?php if ($staff): ?>
                <form method="post" action="<?= e(url('atlante/salva-cliente')) ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="id" value="<?= e($id) ?>">
                    <?php View::parziale('atlante_anagrafica', ['cliente' => $cliente]); ?>
                    <button class="btn btn-wp mt-3" type="submit">Salva anagrafica</button>
                </form>
                <form class="d-flex flex-wrap align-items-end gap-2 mt-3" method="post" action="<?= e(url('atlante/stato-cliente')) ?>">
                    <?= Csrf::campo() ?><?= $nascosto ?>
                    <div>
                        <label class="form-label" for="stato-cliente">Stato</label>
                        <select class="form-select" id="stato-cliente" name="stato"><?= $opzioni(Atlante::STATI_CLIENTE, $cliente['stato']) ?></select>
                    </div>
                    <button class="btn btn-wp-bordo" type="submit">Aggiorna stato</button>
                </form>
                <?php if ($cliente['verificato_il']): ?>
                    <p class="form-text mb-0">Verificato il <?= e($giorno($cliente['verificato_il'])) ?>.</p>
                <?php endif; ?>
            <?php else: ?>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codice fiscale</dt><dd class="col-sm-8"><?= e($cliente['codice_fiscale'] ?: '—') ?></dd>
                    <dt class="col-sm-4">Partita IVA</dt><dd class="col-sm-8"><?= e($cliente['partita_iva'] ?: '—') ?></dd>
                    <dt class="col-sm-4">PEC</dt><dd class="col-sm-8"><?= e($cliente['pec'] ?: '—') ?></dd>
                    <dt class="col-sm-4">Sede</dt><dd class="col-sm-8"><?= e($cliente['sede'] ?: '—') ?></dd>
                </dl>
            <?php endif; ?>
        </section>

        <section class="wp-scheda mb-3" id="referenti">
            <h2>Referenti</h2>
            <p class="wp-sottotitolo">Persone che agiscono per conto del cliente. Un referente vede il cliente solo dopo la verifica del mandato.</p>
            <?php if (!$referenti): ?>
                <p class="wp-sottotitolo">Nessun referente.</p>
            <?php else: ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($referenti as $r): ?>
                        <li class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="flex-grow-1">
                                <strong><?= e($r['nome']) ?></strong> <span class="wp-sottotitolo">· <?= e(Atlante::RUOLI[$r['ruolo']]) ?></span>
                                <?php if ($staff && $r['mandato_evidenza_rif']): ?><span class="wp-sottotitolo d-block">Mandato: <?= e($r['mandato_evidenza_rif']) ?></span><?php endif; ?>
                            </span>
                            <?= bollino($r['revocato_il'] ? 'Revocato' : ($r['verificato_il'] ? 'Attivo' : 'Da verificare')) ?>
                            <?php if ($staff && !$r['revocato_il']): ?>
                                <form class="d-flex gap-2" method="post" action="<?= e(url('atlante/stato-referente')) ?>" data-ancora="referenti">
                                    <?= Csrf::campo() ?><?= $nascosto ?>
                                    <input type="hidden" name="referente" value="<?= e($r['id']) ?>">
                                    <?php if (!$r['verificato_il']): ?><button class="btn btn-wp btn-sm" type="submit" name="cosa" value="verifica">Verifica mandato</button><?php endif; ?>
                                    <button class="btn btn-wp-bordo btn-sm" type="submit" name="cosa" value="revoca">Revoca</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($staff): ?>
                <form class="row g-2" method="post" action="<?= e(url('atlante/referente')) ?>" data-ancora="referenti">
                    <?= Csrf::campo() ?><?= $nascosto ?>
                    <div class="col-md-5"><label class="form-label" for="ref-email">Email dell'account</label><input class="form-control" type="email" id="ref-email" name="email" maxlength="254" required></div>
                    <div class="col-md-3"><label class="form-label" for="ref-ruolo">Ruolo</label><select class="form-select" id="ref-ruolo" name="ruolo"><?= $opzioni(Atlante::RUOLI) ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="ref-evidenza">Riferimento del mandato</label><input class="form-control" id="ref-evidenza" name="evidenza" maxlength="500" placeholder="Delega del 3/11, prot. 12"></div>
                    <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Aggiungi referente</button></div>
                </form>
            <?php endif; ?>
        </section>

        <section class="wp-scheda mb-3" id="campi">
            <h2>Campi di personalizzazione</h2>
            <p class="wp-sottotitolo">Dati usati per compilare i documenti. I valori riservati sono cifrati. Qui non vanno mai dati degli elettori.</p>
            <?php if ($campi): ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($campi as $c): ?>
                        <li><strong><?= e($c['chiave']) ?></strong> <span class="wp-sottotitolo">· <?= $c['riservato'] ? 'riservato' : 'pubblicabile' ?></span><span class="d-block"><?= e($c['valore']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($puoCampi): ?>
                <form class="row g-2" method="post" action="<?= e(url('atlante/salva-campo')) ?>" data-ancora="campi">
                    <?= Csrf::campo() ?><?= $nascosto ?>
                    <div class="col-md-4"><label class="form-label" for="campo-chiave">Nome</label><input class="form-control" id="campo-chiave" name="chiave" maxlength="120" required placeholder="slogan"></div>
                    <div class="col-md-8"><label class="form-label" for="campo-valore">Valore (vuoto = elimina)</label><input class="form-control" id="campo-valore" name="valore" maxlength="5000"></div>
                    <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" id="campo-riservato" name="riservato" value="1"><label class="form-check-label" for="campo-riservato">Riservato (cifrato, non pubblicabile)</label></div>
                    <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Salva campo</button></div>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($registro !== null): ?>
            <section class="wp-scheda" id="registro">
                <h2>Registro delle operazioni</h2>
                <p class="wp-sottotitolo">Ultime 100 operazioni, comprese quelle negate. Solo metadati.</p>
                <div class="table-responsive">
                    <table class="table wp-tabella">
                        <thead><tr><th scope="col">Quando</th><th scope="col">Chi</th><th scope="col">Operazione</th><th scope="col">Esito</th></tr></thead>
                        <tbody>
                            <?php foreach ($registro as $r): ?>
                                <tr><td><?= e(ora_locale($r['creato_il'], 'd/m H:i')) ?></td><td><?= e($r['attore'] ?: '—') ?></td><td><?= e($r['oggetto'] . ' · ' . $r['azione']) ?></td><td><?= bollino(ucfirst($r['esito'])) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="col-xl-6">
        <section class="wp-scheda mb-3" id="siti">
            <h2>Siti e funzioni</h2>
            <p class="wp-sottotitolo">Newsletter, donazioni, pubblicità, targeting e IA sono spenti finché non vengono richiesti, verificati e autorizzati.</p>
            <?php foreach ($siti as $s): ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <strong class="flex-grow-1"><?= e($s['nome']) ?></strong>
                        <?= bollino(Atlante::STATI_SITO[$s['stato']]) ?> <span class="wp-sottotitolo"><?= e($s['ambiente']) ?></span>
                    </div>
                    <p class="wp-sottotitolo mb-2">
                        <?= e(Atlante::TIPI_SITO[$s['tipo_sito']]) ?><?= $s['dominio'] ? ' · ' . e($s['dominio']) : '' ?><?= $s['politico'] ? ' · dedicato a ' . e($s['politico']) : '' ?><?= $s['beneficiario'] ? ' · beneficiario: ' . e($s['beneficiario']) : '' ?>
                    </p>
                    <ul class="list-unstyled mb-2">
                        <?php foreach ($s['funzioni'] as $codice => $stato): ?>
                            <?php if ($stato !== 'non_attiva' || $staff || $puoChiedere): ?>
                                <li class="d-flex flex-wrap align-items-center gap-2 py-1">
                                    <span class="flex-grow-1"><?= e(Atlante::FUNZIONI[$codice]) ?></span>
                                    <?= bollino(Atlante::STATI_FUNZIONE[$stato]) ?>
                                    <?php if ($staff): ?>
                                        <form class="d-flex gap-1" method="post" action="<?= e(url('atlante/stato-funzione')) ?>" data-ancora="siti">
                                            <?= Csrf::campo() ?><?= $nascosto ?>
                                            <input type="hidden" name="sito" value="<?= e($s['id']) ?>"><input type="hidden" name="codice" value="<?= e($codice) ?>">
                                            <select class="form-select form-select-sm" name="stato" aria-label="Stato di <?= e(Atlante::FUNZIONI[$codice]) ?>"><?= $opzioni(Atlante::STATI_FUNZIONE, $stato) ?></select>
                                            <button class="btn btn-wp-bordo btn-sm" type="submit">Salva</button>
                                        </form>
                                    <?php elseif ($puoChiedere && in_array($stato, ['non_attiva', 'sospesa'], true)): ?>
                                        <form method="post" action="<?= e(url('atlante/richiedi-funzione')) ?>" data-ancora="siti">
                                            <?= Csrf::campo() ?><?= $nascosto ?>
                                            <input type="hidden" name="sito" value="<?= e($s['id']) ?>"><input type="hidden" name="codice" value="<?= e($codice) ?>">
                                            <button class="btn btn-wp-bordo btn-sm" type="submit">Richiedi</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($staff): ?>
                        <form class="d-flex flex-wrap align-items-end gap-2" method="post" action="<?= e(url('atlante/stato-sito')) ?>" data-ancora="siti">
                            <?= Csrf::campo() ?><?= $nascosto ?>
                            <input type="hidden" name="sito" value="<?= e($s['id']) ?>">
                            <select class="form-select form-select-sm w-auto" name="stato" aria-label="Stato del sito"><?= $opzioni(Atlante::STATI_SITO, $s['stato']) ?></select>
                            <button class="btn btn-wp-bordo btn-sm" type="submit">Aggiorna stato del sito</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$siti): ?><p class="wp-sottotitolo">Nessun sito.</p><?php endif; ?>
            <?php if ($staff): ?>
                <details class="wp-dettagli">
                    <summary>Aggiungi un sito</summary>
                    <form class="row g-2 mt-1" method="post" action="<?= e(url('atlante/sito')) ?>" data-ancora="siti">
                        <?= Csrf::campo() ?><?= $nascosto ?>
                        <input type="hidden" name="sito" value="0">
                        <div class="col-md-6"><label class="form-label" for="sito-nome">Nome</label><input class="form-control" id="sito-nome" name="nome" maxlength="220" required></div>
                        <div class="col-md-6"><label class="form-label" for="sito-dominio">Dominio</label><input class="form-control" id="sito-dominio" name="dominio" maxlength="190" placeholder="mariorossi.it"></div>
                        <div class="col-md-4"><label class="form-label" for="sito-tipo">Tipo</label><select class="form-select" id="sito-tipo" name="tipo_sito"><?= $opzioni(Atlante::TIPI_SITO) ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="sito-politico">Dedicato a</label><select class="form-select" id="sito-politico" name="politico_id"><option value="">Nessuno</option><?= $opzioni($politici) ?></select></div>
                        <div class="col-md-4"><label class="form-label" for="sito-beneficiario">Beneficiario</label><select class="form-select" id="sito-beneficiario" name="beneficiario_cliente_id"><option value="">Il cliente stesso</option><?= $opzioni($altriClienti) ?></select></div>
                        <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Crea sito</button></div>
                    </form>
                </details>
            <?php endif; ?>
        </section>

        <?php if ($offerte !== null): ?>
            <section class="wp-scheda mb-3" id="offerte">
                <h2>Offerte</h2>
                <p class="wp-sottotitolo">Prezzi e periodicità concordati. Non sostituiscono il contratto.</p>
                <?php if (!$offerte): ?><p class="wp-sottotitolo">Nessuna offerta.</p><?php endif; ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($offerte as $o): ?>
                        <li class="d-flex flex-wrap align-items-center gap-2">
                            <span class="flex-grow-1">
                                <strong><?= e($o['codice_offerta']) ?></strong><?= $o['sito'] ? ' <span class="wp-sottotitolo">· ' . e($o['sito']) . '</span>' : '' ?>
                                <span class="wp-sottotitolo d-block">Attivazione <?= e($euro($o['attivazione_centesimi'])) ?> · canone <?= e($euro($o['canone_centesimi'])) ?> (<?= e(mb_strtolower(Atlante::PERIODICITA[$o['periodicita']])) ?>)</span>
                            </span>
                            <?= bollino(Atlante::STATI_OFFERTA[$o['stato']]) ?>
                            <form class="d-flex gap-1" method="post" action="<?= e(url('atlante/stato-offerta')) ?>" data-ancora="offerte">
                                <?= Csrf::campo() ?><?= $nascosto ?>
                                <input type="hidden" name="offerta" value="<?= e($o['id']) ?>">
                                <?php if ($staff && $o['stato'] === 'bozza'): ?><button class="btn btn-wp btn-sm" type="submit" name="stato" value="inviata">Invia al cliente</button><?php endif; ?>
                                <?php if ($staff && $o['stato'] === 'inviata'): ?><button class="btn btn-wp-bordo btn-sm" type="submit" name="stato" value="scaduta">Segna scaduta</button><?php endif; ?>
                                <?php if ($firmatario && $o['stato'] === 'inviata'): ?>
                                    <button class="btn btn-wp btn-sm" type="submit" name="stato" value="accettata">Accetta</button>
                                    <button class="btn btn-wp-bordo btn-sm" type="submit" name="stato" value="rifiutata">Rifiuta</button>
                                <?php endif; ?>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($staff): ?>
                    <details class="wp-dettagli">
                        <summary>Nuova offerta</summary>
                        <form class="row g-2 mt-1" method="post" action="<?= e(url('atlante/offerta')) ?>" data-ancora="offerte">
                            <?= Csrf::campo() ?><?= $nascosto ?>
                            <div class="col-md-4"><label class="form-label" for="off-codice">Codice</label><input class="form-control" id="off-codice" name="codice_offerta" maxlength="80" required placeholder="2026-01"></div>
                            <div class="col-md-4"><label class="form-label" for="off-sito">Sito</label><select class="form-select" id="off-sito" name="sito_id"><option value="">Tutto il cliente</option><?= $opzioni($nomiSiti) ?></select></div>
                            <div class="col-md-4"><label class="form-label" for="off-periodicita">Periodicità</label><select class="form-select" id="off-periodicita" name="periodicita"><?= $opzioni(Atlante::PERIODICITA, 'mensile') ?></select></div>
                            <div class="col-md-3"><label class="form-label" for="off-attivazione">Attivazione €</label><input class="form-control" id="off-attivazione" name="attivazione" inputmode="decimal" maxlength="12"></div>
                            <div class="col-md-3"><label class="form-label" for="off-canone">Canone €</label><input class="form-control" id="off-canone" name="canone" inputmode="decimal" maxlength="12"></div>
                            <div class="col-md-3"><label class="form-label" for="off-decorrenza">Decorrenza</label><input class="form-control" type="date" id="off-decorrenza" name="decorrenza"></div>
                            <div class="col-md-3"><label class="form-label" for="off-termine">Termine</label><input class="form-control" type="date" id="off-termine" name="termine"></div>
                            <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Crea offerta</button></div>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($fascicoli !== null): ?>
            <section class="wp-scheda mb-3" id="fascicoli">
                <h2>Fascicoli documentali</h2>
                <?php if (!$fascicoli): ?><p class="wp-sottotitolo">Nessun fascicolo.</p><?php endif; ?>
                <ul class="wp-righe mb-3">
                    <?php foreach ($fascicoli as $f): ?>
                        <li class="d-flex flex-wrap align-items-center gap-2">
                            <span class="flex-grow-1">
                                <a class="fw-bold" href="<?= e(url('atlante/fascicolo', ['id' => $f['id']])) ?>"><?= e($f['titolo']) ?></a>
                                <span class="wp-sottotitolo d-block"><?= e($f['codice']) ?> · <?= (int) $f['documenti'] ?> <?= (int) $f['documenti'] === 1 ? 'documento' : 'documenti' ?><?= $f['sito'] ? ' · ' . e($f['sito']) : '' ?></span>
                            </span>
                            <?= bollino(Atlante::STATI_FASCICOLO[$f['stato']]) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($staff): ?>
                    <form class="row g-2" method="post" action="<?= e(url('atlante/crea-fascicolo')) ?>">
                        <?= Csrf::campo() ?><?= $nascosto ?>
                        <div class="col-md-3"><label class="form-label" for="fas-codice">Codice</label><input class="form-control" id="fas-codice" name="codice" maxlength="100" required placeholder="F-2026-01"></div>
                        <div class="col-md-5"><label class="form-label" for="fas-titolo">Titolo</label><input class="form-control" id="fas-titolo" name="titolo" maxlength="240" required></div>
                        <div class="col-md-4"><label class="form-label" for="fas-sito">Sito</label><select class="form-select" id="fas-sito" name="sito_id"><option value="">Nessuno</option><?= $opzioni($nomiSiti) ?></select></div>
                        <div class="col-12"><button class="btn btn-wp-bordo" type="submit">Apri fascicolo</button></div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($verifiche !== null): ?>
            <section class="wp-scheda" id="verifiche">
                <h2>Verifiche</h2>
                <p class="wp-sottotitolo">GDPR, DPIA, cookie, sicurezza, AI Act, pubblicità politica. Una verifica si chiude solo con una motivazione.</p>
                <?php if (!$verifiche): ?><p class="wp-sottotitolo">Nessuna verifica aperta.</p><?php endif; ?>
                <?php foreach ($verifiche as $v): ?>
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong class="flex-grow-1"><?= e(Atlante::OGGETTI_VERIFICA[$v['oggetto']]) ?><?= $v['sito'] ? ' <span class="wp-sottotitolo">· ' . e($v['sito']) . '</span>' : '' ?></strong>
                            <?= bollino(Atlante::ESITI[$v['esito']]) ?>
                        </div>
                        <?php if ($v['motivazione']): ?><p class="mb-1 mt-2"><?= e($v['motivazione']) ?></p><?php endif; ?>
                        <?php if ($v['validata_il']): ?><p class="wp-sottotitolo mb-0">Validata da <?= e($v['validatore']) ?> il <?= e($giorno($v['validata_il'])) ?><?= $v['evidenza_rif'] ? ' · prova: ' . e($v['evidenza_rif']) : '' ?></p><?php endif; ?>
                        <?php if ($staff): ?>
                            <form class="row g-2 mt-1" method="post" action="<?= e(url('atlante/esito-verifica')) ?>" data-ancora="verifiche">
                                <?= Csrf::campo() ?><?= $nascosto ?>
                                <input type="hidden" name="verifica" value="<?= e($v['id']) ?>">
                                <div class="col-md-4"><select class="form-select form-select-sm" name="esito" aria-label="Esito"><?= $opzioni(Atlante::ESITI, $v['esito']) ?></select></div>
                                <div class="col-md-8"><input class="form-control form-control-sm" name="evidenza" maxlength="500" placeholder="Riferimento della prova" value="<?= e($v['evidenza_rif']) ?>" aria-label="Riferimento della prova"></div>
                                <div class="col-12"><textarea class="form-control form-control-sm" name="motivazione" rows="2" maxlength="5000" placeholder="Motivazione (obbligatoria per chiudere)" aria-label="Motivazione"><?= e($v['motivazione']) ?></textarea></div>
                                <div class="col-12"><button class="btn btn-wp-bordo btn-sm" type="submit">Aggiorna verifica</button></div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($staff): ?>
                    <form class="d-flex flex-wrap align-items-end gap-2 mt-2" method="post" action="<?= e(url('atlante/verifica')) ?>" data-ancora="verifiche">
                        <?= Csrf::campo() ?><?= $nascosto ?>
                        <div><label class="form-label" for="ver-oggetto">Nuova verifica</label><select class="form-select" id="ver-oggetto" name="oggetto"><?= $opzioni(Atlante::OGGETTI_VERIFICA) ?></select></div>
                        <div><label class="form-label" for="ver-sito">Sito</label><select class="form-select" id="ver-sito" name="sito_id"><option value="">Tutto il cliente</option><?= $opzioni($nomiSiti) ?></select></div>
                        <button class="btn btn-wp-bordo" type="submit">Apri verifica</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
