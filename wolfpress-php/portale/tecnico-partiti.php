<?php
/**
 * Gestione tecnica: partiti.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<?php
/** Gestione tecnica · Partiti: schede mostrate nella sezione Politica del sito. */
View::parziale('intestazione', [
    'occhiello' => 'Operazioni',
    'titoloPagina' => 'Partiti',
    'descrizione' => 'Crea e aggiorna le schede dei partiti. I politici scelgono il proprio partito dalla loro area; notizie e appuntamenti si compongono dai loro articoli ed eventi pubblici.',
]);
$nuovo = $partito['id'] === '';
$righeRete = implode("\n", array_map(static fn (array $c): string => $c['nome'] . ' | ' . $c['descrizione'], $partito['rete']));
?>
<div class="row g-3 align-items-start">
    <div class="col-xl-5">
        <section class="wp-scheda">
            <h2>Partiti</h2>
            <?php if (!$partiti): ?>
                <p class="wp-sottotitolo mb-0">Nessun partito inserito.</p>
            <?php else: ?>
                <ul class="wp-righe">
                    <?php foreach ($partiti as $slug => $nome): ?>
                        <li class="d-flex align-items-center gap-3">
                            <a class="fw-bold flex-grow-1" href="<?= e(url('tecnico/partiti', ['id' => $slug])) ?>"><?= e($nome) ?></a>
                            <a href="<?= e(url_sito('politica/partito', ['id' => $slug])) ?>" target="_blank" rel="noopener">Pagina pubblica</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-xl-7">
        <form class="wp-scheda" method="post" action="<?= e(url('tecnico/salva-partito')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="id" value="<?= e($partito['id']) ?>">
            <h2 class="mb-3"><?= $nuovo ? 'Nuovo partito' : 'Modifica partito' ?></h2>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="nome">Nome</label>
                    <input class="form-control" id="nome" name="nome" maxlength="160" required value="<?= e($partito['nome']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="sezione">Sezione</label>
                    <input class="form-control" id="sezione" name="sezione" maxlength="120" placeholder="Sezione regionale" value="<?= e($partito['sezione']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="descrizione">Descrizione</label>
                    <textarea class="form-control" id="descrizione" name="descrizione" rows="3" maxlength="600"><?= e($partito['descrizione']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="rete_intro">Rete territoriale: introduzione</label>
                    <input class="form-control" id="rete_intro" name="rete_intro" maxlength="400" value="<?= e($partito['rete_intro']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="rete">Circoli</label>
                    <textarea class="form-control" id="rete" name="rete" rows="4" maxlength="4000" placeholder="Valleverde | Circolo cittadino"><?= e($righeRete) ?></textarea>
                    <div class="form-text">Un circolo per riga: nome, barra verticale, descrizione.</div>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="orari">Orari della segreteria</label>
                    <input class="form-control" id="orari" name="orari" maxlength="200" value="<?= e($partito['orari']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="120" value="<?= e($partito['email']) ?>">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-wp" type="submit">Salva partito</button>
                <?php if (!$nuovo): ?>
                    <a class="btn btn-wp-bordo" href="<?= e(url('tecnico/partiti')) ?>">Nuovo partito</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
