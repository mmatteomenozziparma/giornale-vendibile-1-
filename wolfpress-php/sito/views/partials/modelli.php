<?php
/**
 * Tabella "Scegli un modello": per ogni livello istituzionale apre la pagina
 * di candidatura o di mandato. I due filtri funzionano con assets/js/sito.js.
 * Variabile: $politici.
 */
?>
<section class="wp-scheda" id="modelli">
    <span class="wp-occhiello"><?= count($politici) * 2 ?> pagine dimostrative</span>
    <h2>Scegli un modello</h2>
    <p class="wp-testo-breve">Apri la pagina di candidatura o consulta come la stessa figura renderebbe conto del proprio mandato. I due scenari sono indipendenti.</p>

    <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-lg-3">
            <label class="form-label" for="filtro-livello">Livello istituzionale</label>
            <select class="form-select" id="filtro-livello" data-modelli-livello>
                <option value="">Tutti i livelli</option>
                <?php foreach ($politici as $politico): ?>
                    <option value="<?= e($politico['id']) ?>"><?= e(Politico::LIVELLI[$politico['id']]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4 col-lg-3">
            <label class="form-label" for="filtro-tipo">Tipo di pagina</label>
            <select class="form-select" id="filtro-tipo" data-modelli-tipo>
                <option value="">Candidatura e mandato</option>
                <option value="candidatura">Solo candidatura</option>
                <option value="mandato">Solo mandato</option>
            </select>
        </div>
        <div class="col-sm-4">
            <p class="wp-conteggio mb-2" data-modelli-conteggio aria-live="polite"><?= count($politici) * 2 ?> modelli visibili</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table wp-tabella" data-modelli>
            <thead>
                <tr>
                    <th scope="col">Livello</th>
                    <th scope="col">Persona</th>
                    <th scope="col" data-colonna="candidatura">Candidatura</th>
                    <th scope="col" data-colonna="mandato">Mandato</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($politici as $politico): ?>
                    <tr data-livello="<?= e($politico['id']) ?>">
                        <th scope="row"><?= e(Politico::LIVELLI[$politico['id']]) ?></th>
                        <td><?= e($politico['nome']) ?></td>
                        <td data-colonna="candidatura">
                            <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'candidatura'])) ?>">Apri candidatura <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        </td>
                        <td data-colonna="mandato">
                            <a href="<?= e(url('politica/profilo', ['livello' => $politico['id'], 'tipo' => 'mandato'])) ?>">Apri mandato <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
