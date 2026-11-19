<?php
/**
 * Gestione tecnica: account da approvare.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<?php
/** Gestione tecnica · Account da approvare: editori, politici e giornalisti che hanno confermato l'email. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni',
    'titoloPagina' => 'Account da approvare',
    'descrizione' => 'Chi si registra come editore, politico o giornalista entra nel portale solo dopo questa approvazione.',
]);
?>
<section class="wp-scheda">
    <?php if (!$inAttesa): ?>
        <p class="mb-0">Nessun account in attesa.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table wp-tabella">
                <thead>
                    <tr><th scope="col">Nome</th><th scope="col">Tipo</th><th scope="col">Registrato il</th><th scope="col"><span class="visually-hidden">Azioni</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($inAttesa as $a): ?>
                        <tr>
                            <td class="fw-bold"><?= e(trim($a['nome'] . ' ' . $a['cognome'])) ?></td>
                            <td><?= e(Auth::RUOLI[$a['tipo']] ?? $a['tipo']) ?></td>
                            <td><?= e(data_it(ora_locale($a['creato_il'], 'Y-m-d'))) ?></td>
                            <td class="text-end">
                                <form class="d-inline-flex gap-2" method="post" action="<?= e(url('tecnico/approva')) ?>">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                    <button class="btn btn-wp btn-sm" type="submit" name="azione" value="approva">Approva</button>
                                    <button class="btn btn-wp-bordo btn-sm" type="submit" name="azione" value="sospendi">Rifiuta</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
