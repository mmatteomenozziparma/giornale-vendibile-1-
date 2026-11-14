<?php
/** Home del sito pubblico. */
?>
<?php if ($primoPiano): ?>
    <section class="wp-apertura">
        <div class="wp-apertura-testo">
            <span class="wp-occhiello">In primo piano · <?= e($primoPiano['sezione']) ?></span>
            <h1><a href="<?= e(url('notizie/articolo', ['slug' => $primoPiano['slug']])) ?>"><?= e($primoPiano['titolo']) ?></a></h1>
            <p><?= e($primoPiano['sommario']) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-wp-chiaro" href="<?= e(url('notizie/articolo', ['slug' => $primoPiano['slug']])) ?>">Leggi l'articolo <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a class="btn btn-wp-vetro" href="<?= e(url('notizie')) ?>">Tutte le notizie</a>
            </div>
            <small><?= e(Testata::nome($primoPiano['testata'])) ?> · <?= e($primoPiano['autore']) ?> · <?= e(data_it($primoPiano['data'])) ?></small>
        </div>

        <aside class="wp-apertura-lato" aria-labelledby="titolo-piu-letti">
            <span class="wp-occhiello" id="titolo-piu-letti">I più letti</span>
            <ol>
                <?php foreach ($piuLetti as $articolo): ?>
                    <li>
                        <a href="<?= e(url('notizie/articolo', ['slug' => $articolo['slug']])) ?>"><?= e($articolo['titolo']) ?></a>
                        <small><?= e($articolo['sezione']) ?> · <?= e(numero((int) $articolo['letture'])) ?> letture</small>
                    </li>
                <?php endforeach; ?>
            </ol>
        </aside>
    </section>
<?php endif; ?>

<section class="wp-cerca-grande" aria-labelledby="titolo-cerca">
    <h2 id="titolo-cerca">Cosa stai cercando?</h2>
    <form method="get" action="<?= e(url()) ?>" role="search">
        <input type="hidden" name="r" value="ricerca">
        <label class="visually-hidden" for="cerca-home">Parole da cercare</label>
        <input class="form-control" type="search" id="cerca-home" name="q" maxlength="80" required placeholder="Un argomento, un politico, un partito, un Comune...">
        <label class="visually-hidden" for="cerca-dove">Dove cercare</label>
        <select class="form-select" id="cerca-dove" name="dove">
            <option value="tutto">In tutto il sito</option>
            <option value="notizie">Solo notizie</option>
            <option value="politici">Solo politici</option>
            <option value="partiti">Solo partiti</option>
        </select>
        <button class="btn btn-wp" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Cerca</button>
    </form>
</section>

<section aria-labelledby="titolo-ultime">
    <div class="wp-titolo-sezione">
        <h2 id="titolo-ultime">Ultime notizie</h2>
        <a href="<?= e(url('notizie')) ?>">Vedi tutte</a>
    </div>
    <div class="row g-3">
        <?php foreach ($ultime as $articolo): ?>
            <div class="col-md-6 col-lg-4">
                <?php View::parziale('scheda_articolo', ['articolo' => $articolo]); ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section aria-labelledby="titolo-politica">
    <div class="wp-titolo-sezione">
        <h2 id="titolo-politica">Politica: partiti, candidati ed eletti</h2>
        <a href="<?= e(url('politica')) ?>">Apri la sezione</a>
    </div>
    <div class="row g-3">
        <?php foreach ($partiti as $partito): ?>
            <div class="col-lg-4">
                <a class="wp-partito h-100" href="<?= e(url('politica/partito', ['id' => $partito['id']])) ?>">
                    <span class="wp-occhiello">Partito dimostrativo · <?= e($partito['sezione']) ?></span>
                    <strong><?= e($partito['nome']) ?></strong>
                    <span>Notizie, circoli e appuntamenti <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
        <?php endforeach; ?>
        <div class="col-lg-8">
            <div class="wp-scheda h-100">
                <span class="wp-occhiello">Dal Comune al Parlamento europeo</span>
                <ul class="wp-persone">
                    <?php foreach ($politici as $politico): ?>
                        <li>
                            <span class="wp-avatar" aria-hidden="true"><?= e(iniziali($politico['nome'])) ?></span>
                            <span class="flex-grow-1">
                                <strong><?= e($politico['nome']) ?></strong>
                                <small><?= e(Politico::LIVELLI[$politico['id']]) ?></small>
                            </span>
                            <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'candidatura'])) ?>">Candidatura</a>
                            <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'mandato'])) ?>">Mandato</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<section aria-labelledby="titolo-testate">
    <div class="wp-titolo-sezione">
        <h2 id="titolo-testate">Le testate</h2>
        <a href="<?= e(url('testate')) ?>">Vedi tutte</a>
    </div>
    <div class="row g-3">
        <?php foreach ($testate as $testata): ?>
            <div class="col-sm-6 col-lg-3">
                <a class="wp-scheda wp-testata-link h-100" href="<?= e(url('notizie', ['testata' => $testata['id']])) ?>">
                    <strong><?= e($testata['nome']) ?></strong>
                    <span><?= e($testata['descrizione']) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
