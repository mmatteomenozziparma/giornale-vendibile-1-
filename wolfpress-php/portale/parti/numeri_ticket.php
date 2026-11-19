<?php
/** Tre riquadri con il conteggio dei ticket per stato. Variabile: $conteggio. */
?>
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="wp-numero"><span>Da prendere in carico</span><strong><?= (int) $conteggio['Aperto'] ?></strong></div>
    </div>
    <div class="col-sm-4">
        <div class="wp-numero"><span>In lavorazione</span><strong><?= (int) $conteggio['In lavorazione'] ?></strong></div>
    </div>
    <div class="col-sm-4">
        <div class="wp-numero"><span>Risolti</span><strong><?= (int) $conteggio['Risolto'] ?></strong></div>
    </div>
</div>
