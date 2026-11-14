<?php
/** Pagina iniziale del portale: le aree disponibili per il profilo collegato. */
?>
<section class="wp-copertina">
    <span class="wp-occhiello">WolfPress · <?= e(Auth::etichettaRuolo()) ?></span>
    <h1>Ciao, <?= e($utente['nome']) ?></h1>
    <p>Scegli l'area in cui lavorare. Le pagine si aprono senza ricaricare il portale.</p>
</section>

<div class="row g-3">
    <?php foreach ($aree as [$nome, $descrizione, $icona, $indirizzo]): ?>
        <div class="col-md-6 col-xl-4">
            <a class="wp-scheda wp-scheda-link h-100" href="<?= e($indirizzo) ?>">
                <i class="bi <?= e($icona) ?>" aria-hidden="true"></i>
                <h2><?= e($nome) ?></h2>
                <p class="wp-sottotitolo mb-0"><?= e($descrizione) ?></p>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="wp-nota mt-4">
    Stai usando un profilo dimostrativo. Per provare un altro ruolo apri
    <a href="<?= e(url('accesso')) ?>">Accessi demo</a>; per vedere il risultato dal lato dei lettori apri il
    <a href="<?= e(url_sito()) ?>" target="_blank" rel="noopener">sito pubblico</a>.
</div>
