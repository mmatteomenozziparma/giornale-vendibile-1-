<?php
/** Pagina informativa con testo fisso (chi siamo, privacy, accessibilità). */
?>
<nav class="wp-briciole" aria-label="Percorso">
    <a href="<?= e(url()) ?>">Home</a> / <?= e($intestazione) ?>
</nav>

<article class="wp-articolo">
    <header>
        <span class="wp-occhiello"><?= e(config('nome')) ?></span>
        <h1><?= e($intestazione) ?></h1>
    </header>
    <div class="wp-corpo-articolo">
        <?php foreach ($paragrafi as $paragrafo): ?>
            <p><?= e($paragrafo) ?></p>
        <?php endforeach; ?>
    </div>
</article>
