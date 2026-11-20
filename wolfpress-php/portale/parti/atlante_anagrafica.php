<?php /** Campi dell'anagrafica di un cliente di Atlante Civico. Variabile: $cliente. */ ?>
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="denominazione">Denominazione</label>
        <input class="form-control" id="denominazione" name="denominazione" maxlength="220" required value="<?= e($cliente['denominazione']) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tipo">Tipo</label>
        <select class="form-select" id="tipo" name="tipo">
            <?php foreach (Atlante::TIPI_CLIENTE as $chiave => $etichetta): ?>
                <option value="<?= e($chiave) ?>"<?= selezionato($chiave, $cliente['tipo']) ?>><?= e($etichetta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="codice_fiscale">Codice fiscale</label>
        <input class="form-control" id="codice_fiscale" name="codice_fiscale" maxlength="16" autocomplete="off" value="<?= e($cliente['codice_fiscale']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="partita_iva">Partita IVA</label>
        <input class="form-control" id="partita_iva" name="partita_iva" maxlength="11" inputmode="numeric" autocomplete="off" value="<?= e($cliente['partita_iva']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="pec">PEC</label>
        <input class="form-control" type="email" id="pec" name="pec" maxlength="254" autocomplete="off" value="<?= e($cliente['pec']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="sede">Sede</label>
        <input class="form-control" id="sede" name="sede" maxlength="500" autocomplete="off" value="<?= e($cliente['sede']) ?>">
    </div>
    <p class="form-text mb-0">Codice fiscale, partita IVA, PEC e sede sono salvati cifrati.</p>
</div>
