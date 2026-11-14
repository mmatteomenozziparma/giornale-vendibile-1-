<?php
/** Gestione tecnica · Personale: operatori dell'azienda fornitrice, ruoli e turni. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Personale tecnico',
    'descrizione' => 'Gestisci gli operatori interni dell\'azienda fornitrice, i loro ruoli e i turni.',
]);
$nuovo = $operatore['id'] === '';
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2>Team del fornitore</h2>
            <p class="wp-sottotitolo">Operatori interni disponibili per infrastruttura e assistenza clienti.</p>
            <div class="row g-2" role="search">
                <div class="col-sm-6">
                    <input class="form-control" type="search" placeholder="Cerca nome, ruolo o turno" aria-label="Cerca nome, ruolo o turno" data-filtro="#elenco-operatori">
                </div>
                <div class="col-sm-6">
                    <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#elenco-operatori">
                        <option value="">Tutti gli stati</option>
                        <?php foreach (Operatore::STATI as $stato): ?>
                            <option><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="wp-conteggio" data-conteggio="#elenco-operatori" data-singolare="risultato" data-plurale="risultati"><?= count($operatori) ?> risultati</p>

            <div class="table-responsive">
                <table class="table wp-tabella">
                    <thead>
                        <tr><th scope="col">Nome</th><th scope="col">Ruolo</th><th scope="col">Turno</th><th scope="col">Stato</th></tr>
                    </thead>
                    <tbody id="elenco-operatori">
                        <?php foreach ($operatori as $o): ?>
                            <tr data-voce data-stato="<?= e($o['stato']) ?>" data-testo="<?= e(mb_strtolower($o['nome'] . ' ' . $o['ruolo'] . ' ' . $o['turno'])) ?>"
                                class="<?= $o['id'] === $operatore['id'] ? 'wp-riga-attiva' : '' ?>">
                                <td><a class="fw-bold" href="<?= e(url('tecnico/personale', ['id' => $o['id']])) ?>"><?= e($o['nome']) ?></a></td>
                                <td><?= e($o['ruolo']) ?></td>
                                <td><?= e($o['turno']) ?></td>
                                <td><?= bollino($o['stato']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="wp-scheda">
            <h2 class="mb-3"><?= $nuovo ? 'Nuovo operatore' : 'Modifica operatore' ?></h2>
            <form method="post" action="<?= e(url('tecnico/salva-operatore')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= e($operatore['id']) ?>">
                <div class="mb-3">
                    <label class="form-label" for="nome">Nome e cognome</label>
                    <input class="form-control" id="nome" name="nome" maxlength="80" required value="<?= e($operatore['nome']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ruolo">Ruolo tecnico</label>
                    <select class="form-select" id="ruolo" name="ruolo">
                        <?php foreach (Operatore::RUOLI as $ruolo): ?>
                            <option<?= selezionato($ruolo, $operatore['ruolo']) ?>><?= e($ruolo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="turno">Turno o reperibilità</label>
                    <input class="form-control" id="turno" name="turno" maxlength="40" placeholder="09:00–17:00" value="<?= e($operatore['turno']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="stato">Stato</label>
                    <select class="form-select" id="stato" name="stato">
                        <?php foreach (Operatore::STATI as $stato): ?>
                            <option<?= selezionato($stato, $operatore['stato']) ?>><?= e($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-wp" type="submit">Salva operatore</button>
                    <?php if (!$nuovo): ?>
                        <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/personale')) ?>">Nuovo operatore</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
</div>
