<?php
/**
 * Atlante Civico: elenco dei clienti (candidati, partiti, liste civiche, comitati).
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Atlante Civico</span>
    <h1>Clienti</h1>
    <p class="wp-intro">
        <?= $staff
            ? 'Candidati, partiti, liste e comitati seguiti dalla piattaforma: per ognuno siti, offerte, verifiche e fascicoli documentali.'
            : 'I clienti per cui hai un mandato verificato. Vedi solo ciò che riguarda il tuo ruolo.' ?>
    </p>
</section>

<div class="row g-3 align-items-start">
    <div class="<?= $staff ? 'col-xl-7' : 'col-12' ?>">
        <section class="wp-scheda">
            <h2>Elenco</h2>
            <?php if (!$clienti): ?>
                <p class="wp-sottotitolo mb-0"><?= $staff ? 'Nessun cliente inserito.' : 'Non hai mandati attivi. Se rappresenti un candidato o un partito, chiedi al fornitore di collegare il tuo account.' ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table wp-tabella">
                        <thead><tr><th scope="col">Denominazione</th><th scope="col">Tipo</th><th scope="col">Stato</th></tr></thead>
                        <tbody>
                            <?php foreach ($clienti as $c): ?>
                                <tr>
                                    <td><a class="fw-bold" href="<?= e(url('atlante/cliente', ['id' => $c['id']])) ?>"><?= e($c['denominazione']) ?></a></td>
                                    <td><?= e(Atlante::TIPI_CLIENTE[$c['tipo']]) ?></td>
                                    <td><?= bollino(Atlante::STATI_CLIENTE[$c['stato']]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($staff): ?>
        <div class="col-xl-5">
            <form class="wp-scheda" method="post" action="<?= e(url('atlante/salva-cliente')) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id" value="0">
                <h2 class="mb-3">Nuovo cliente</h2>
                <?php View::parziale('atlante_anagrafica', ['cliente' => ['tipo' => 'candidato', 'denominazione' => '', 'codice_fiscale' => '', 'partita_iva' => '', 'pec' => '', 'sede' => '']]); ?>
                <button class="btn btn-wp mt-3" type="submit">Crea cliente</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
