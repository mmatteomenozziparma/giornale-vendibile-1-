<?php /** Creazione di una testata: la prima ($prima = true) oppure una in più. */ ?>
<section class="wp-intestazione">
    <span class="wp-occhiello">WolfPress · <?= $prima ? 'primo avvio' : 'le tue testate' ?></span>
    <h1><?= $prima ? 'Crea la tua testata' : 'Nuova testata' ?></h1>
    <p class="wp-intro"><?= $prima ? 'Il tuo account è attivo. Per iniziare serve il giornale su cui lavorerà la redazione.' : 'Aggiungi un altro giornale: potrai passare da una testata all\'altra dal menu in alto a destra.' ?></p>
</section>

<form class="wp-scheda wp-scheda-media" method="post" action="<?= e(url('testata/crea')) ?>">
    <?= Csrf::campo() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="nome">Nome della testata</label>
            <input class="form-control" id="nome" name="nome" maxlength="160" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">Email della redazione</label>
            <input class="form-control" type="email" id="email" name="email" maxlength="254" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="dominio">Dominio (facoltativo)</label>
            <input class="form-control" id="dominio" name="dominio" maxlength="190" placeholder="ilmiogiornale.it">
        </div>
        <div class="col-12">
            <label class="form-label" for="descrizione">Descrizione (facoltativa)</label>
            <textarea class="form-control" id="descrizione" name="descrizione" rows="3" maxlength="2000"></textarea>
        </div>
    </div>
    <button class="btn btn-wp mt-3" type="submit">Crea la testata</button>
</form>
