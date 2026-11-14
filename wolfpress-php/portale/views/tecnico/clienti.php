<?php
/** Gestione tecnica · Clienti e testate: referenti, piano e canali social di ogni testata. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni · demo',
    'titoloPagina' => 'Clienti e testate',
    'descrizione' => 'Gestisci referenti, piano e canali social di ciascuna testata.',
]);
?>
<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="wp-numero"><span>Testate ospitate</span><strong><?= count($testate) ?></strong></div></div>
    <div class="col-sm-4"><div class="wp-numero"><span>Clienti</span><strong><?= count(array_unique(array_column($testate, 'cliente'))) ?></strong></div></div>
    <div class="col-sm-4"><div class="wp-numero"><span>Ticket non risolti</span><strong><?= (int) $nonRisolti ?></strong></div></div>
</div>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <section class="wp-scheda">
            <h2 class="mb-3">Registro clienti</h2>
            <input class="form-control" type="search" placeholder="Cerca testata, cliente o dominio" aria-label="Cerca testata, cliente o dominio" data-filtro="#elenco-clienti">
            <p class="wp-conteggio" data-conteggio="#elenco-clienti" data-singolare="risultato" data-plurale="risultati"><?= count($testate) ?> risultati</p>

            <div class="table-responsive">
                <table class="table wp-tabella">
                    <thead>
                        <tr><th scope="col">Testata</th><th scope="col">Cliente</th><th scope="col">Piano</th><th scope="col">Ticket aperti</th></tr>
                    </thead>
                    <tbody id="elenco-clienti">
                        <?php foreach ($testate as $testata): ?>
                            <tr data-voce data-testo="<?= e(mb_strtolower($testata['nome'] . ' ' . $testata['cliente'] . ' ' . $testata['dominio'])) ?>"
                                class="<?= $testata['id'] === $scelta['id'] ? 'wp-riga-attiva' : '' ?>">
                                <td>
                                    <a class="fw-bold" href="<?= e(url('tecnico/clienti', ['id' => $testata['id']])) ?>"><?= e($testata['nome']) ?></a>
                                    <small><?= e($testata['dominio']) ?></small>
                                </td>
                                <td><?= e($testata['cliente']) ?></td>
                                <td><?= e($testata['piano']) ?></td>
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
            <p class="wp-sottotitolo">Dati del cliente e collegamenti pubblici</p>

            <form method="post" action="<?= e(url('tecnico/salva-cliente')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="<?= e($scelta['id']) ?>">
                <div class="mb-3">
                    <label class="form-label" for="cliente">Cliente</label>
                    <input class="form-control" id="cliente" name="cliente" maxlength="80" required value="<?= e($scelta['cliente']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email referente</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="120" value="<?= e($scelta['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="piano">Piano</label>
                    <select class="form-select" id="piano" name="piano">
                        <?php foreach (Testata::PIANI as $piano): ?>
                            <option<?= selezionato($piano, $scelta['piano']) ?>><?= e($piano) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h3 class="wp-titolo-campi">Canali social</h3>
                <?php foreach (Testata::SOCIAL as $chiave => $etichetta): ?>
                    <div class="mb-3">
                        <label class="form-label" for="social-<?= e($chiave) ?>"><?= e($etichetta) ?></label>
                        <input class="form-control" type="url" id="social-<?= e($chiave) ?>" name="<?= e($chiave) ?>" maxlength="300" placeholder="https://" value="<?= e($scelta['social'][$chiave] ?? '') ?>">
                    </div>
                <?php endforeach; ?>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-wp" type="submit">Salva cliente e social</button>
                    <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/referente', ['id' => $scelta['id']])) ?>">Apri storico cliente</a>
                </div>
            </form>
        </section>
    </div>
</div>
