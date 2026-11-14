<?php
/**
 * Elenco dei ticket con ricerca e filtro per stato.
 * Variabili: $ticket (elenco), $scelto (id selezionato), $rotta (dove porta il clic), $segnaposto.
 */
?>
<div class="row g-2" role="search">
    <div class="col-sm-6">
        <input class="form-control" type="search" placeholder="<?= e($segnaposto) ?>" aria-label="<?= e($segnaposto) ?>" data-filtro="#elenco-ticket">
    </div>
    <div class="col-sm-6">
        <select class="form-select" aria-label="Filtra per stato" data-filtro-stato="#elenco-ticket">
            <option value="">Tutti gli stati</option>
            <?php foreach (Ticket::STATI as $stato): ?>
                <option><?= e($stato) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<p class="wp-conteggio" data-conteggio="#elenco-ticket" data-singolare="risultato" data-plurale="risultati"><?= count($ticket) ?> risultati</p>

<div class="wp-elenco-ticket" id="elenco-ticket">
    <?php foreach ($ticket as $t): ?>
        <a class="wp-ticket<?= $t['id'] === $scelto ? ' attivo' : '' ?>" href="<?= e(url($rotta, ['id' => $t['id']])) ?>"
           data-voce data-stato="<?= e($t['stato']) ?>"
           data-testo="<?= e(mb_strtolower($t['oggetto'] . ' ' . $t['id'] . ' ' . Testata::nome($t['testata']) . ' ' . $t['mittente'])) ?>"
           <?= $t['id'] === $scelto ? 'aria-current="true"' : '' ?>>
            <strong><?= e($t['oggetto']) ?></strong>
            <small><?= e($t['id']) ?> · <?= e(Testata::nome($t['testata'])) ?></small>
            <?= bollino($t['stato']) ?>
        </a>
    <?php endforeach; ?>
    <?php if (!$ticket): ?>
        <p class="wp-sottotitolo mb-0">Nessun ticket.</p>
    <?php endif; ?>
</div>
