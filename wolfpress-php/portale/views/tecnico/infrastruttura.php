<?php
/** Gestione tecnica · Infrastruttura: testate ospitate, nodi, backup e incidenti. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Infrastruttura',
    'descrizione' => 'Controlla testate ospitate, domini, backup e incidenti.',
]);
?>
<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="wp-numero"><span>Testate ospitate</span><strong><?= count($testate) ?></strong></div></div>
    <div class="col-sm-4"><div class="wp-numero"><span>Servizi operativi</span><strong><?= (int) $operative ?></strong></div></div>
    <div class="col-sm-4"><div class="wp-numero"><span>Ticket non risolti</span><strong><?= (int) $nonRisolti ?></strong></div></div>
</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2 class="mb-3">Testate ospitate</h2>
            <div class="row g-2" role="search">
                <div class="col-sm-6">
                    <input class="form-control" type="search" placeholder="Cerca testata, cliente o dominio" aria-label="Cerca testata, cliente o dominio" data-filtro="#elenco-testate">
                </div>
                <div class="col-sm-6">
                    <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#elenco-testate">
                        <option value="">Tutti gli stati</option>
                        <?php foreach (Testata::STATI as $stato): ?>
                            <option><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="wp-conteggio" data-conteggio="#elenco-testate" data-singolare="risultato" data-plurale="risultati"><?= count($testate) ?> risultati</p>

            <div class="table-responsive">
                <table class="table wp-tabella">
                    <thead>
                        <tr><th scope="col">Testata</th><th scope="col">Nodo</th><th scope="col">Stato</th><th scope="col">Ticket aperti</th></tr>
                    </thead>
                    <tbody id="elenco-testate">
                        <?php foreach ($testate as $testata): ?>
                            <tr data-voce data-stato="<?= e($testata['stato']) ?>" data-testo="<?= e(mb_strtolower($testata['nome'] . ' ' . $testata['cliente'] . ' ' . $testata['dominio'])) ?>"
                                class="<?= $testata['id'] === $scelta['id'] ? 'wp-riga-attiva' : '' ?>">
                                <td>
                                    <a class="fw-bold" href="<?= e(url('tecnico', ['id' => $testata['id']])) ?>"><?= e($testata['nome']) ?></a>
                                    <small><?= e($testata['dominio']) ?></small>
                                </td>
                                <td><?= e($testata['nodo']) ?></td>
                                <td><?= bollino($testata['stato']) ?></td>
                                <td><?= Ticket::nonRisolti($testata['id']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <h2><?= e($scelta['nome']) ?></h2>
            <p class="wp-sottotitolo">Scheda di servizio · telemetria simulata</p>

            <dl class="wp-dati">
                <dt>Dominio</dt>
                <dd><?= e($scelta['dominio']) ?></dd>
                <dt>Ultimo backup demo</dt>
                <dd><?= e($scelta['backup']) ?></dd>
            </dl>

            <form method="post" action="<?= e(url('tecnico/salva-stato')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= e($scelta['id']) ?>">
                <div class="mb-3">
                    <label class="form-label" for="stato">Stato del servizio</label>
                    <select class="form-select" id="stato" name="stato">
                        <?php foreach (Testata::STATI as $stato): ?>
                            <option<?= selezionato($stato, $scelta['stato']) ?>><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="incidente">Incidente o manutenzione</label>
                    <input class="form-control" id="incidente" name="incidente" maxlength="160" placeholder="Nessun incidente aperto" value="<?= e($scelta['incidente']) ?>">
                </div>
                <button class="btn btn-wp" type="submit">Salva stato demo</button>
            </form>
        </section>
    </div>
</div>
