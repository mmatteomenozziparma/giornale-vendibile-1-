<?php
/**
 * Eventi: organizzati, inviti ricevuti, partecipazioni.
 */
require_once __DIR__ . '/avvio.php';
extract(pagina(__FILE__));               // dati preparati dal controller (cartella controllers/)
require __DIR__ . '/header.php';
require __DIR__ . '/menu.php';
?>
<?php
/** Eventi: organizzati da me, inviti ricevuti, partecipazioni e modulo per crearne uno. */
$riga = static function (array $e, string $extra = ''): string {
    [$giorno, $mese] = giorno_mese($e['data']);
    return '<span class="wp-data"><b>' . e($giorno) . '</b><small>' . e($mese) . '</small></span>'
        . '<span class="flex-grow-1"><a class="fw-bold d-block" href="' . e(url('eventi/scheda', ['id' => $e['id']])) . '">' . e($e['titolo']) . '</a>'
        . '<span class="wp-sottotitolo">' . e(data_it($e['data'])) . ($e['ora'] !== '' ? ' · ' . e($e['ora']) : '') . ($e['luogo'] !== '' ? ' · ' . e($e['luogo']) : '')
        . ' · ' . ($e['pubblico'] ? 'pubblico' : 'riservato') . '</span></span>' . $extra;
};
?>
<section class="wp-intestazione">
    <span class="wp-occhiello">Agenda condivisa</span>
    <h1>Eventi</h1>
    <p class="wp-intro">Un evento può essere organizzato da un politico, da una testata o da entrambi insieme. Gli organizzatori invitano politici e giornalisti; agli eventi pubblici possono partecipare anche i lettori dal sito.</p>
</section>

<div class="row g-3 align-items-start">
    <div class="col-xl-7">
        <?php if ($inviti): ?>
            <section class="wp-scheda mb-3" id="inviti">
                <h2>Inviti ricevuti</h2>
                <ul class="wp-righe">
                    <?php foreach ($inviti as $e): ?>
                        <li class="d-flex align-items-center gap-3 flex-wrap">
                            <?= $riga($e) ?>
                            <?php if ($e['invito'] === 'in_attesa'): ?>
                                <form class="d-flex gap-2" method="post" action="<?= e(url('eventi/rispondi')) ?>">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="id" value="<?= e($e['id']) ?>">
                                    <button class="btn btn-wp btn-sm" type="submit" name="risposta" value="accetta">Accetta</button>
                                    <button class="btn btn-wp-bordo btn-sm" type="submit" name="risposta" value="rifiuta">Rifiuta</button>
                                </form>
                            <?php else: ?>
                                <?= bollino($e['invito'] === 'accettato' ? 'Accettato' : 'Rifiutato') ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($puoCreare): ?>
            <section class="wp-scheda mb-3" id="organizzati">
                <h2>Organizzati da <?= Auth::ruolo() === 'editore' ? 'questa testata' : 'te' ?></h2>
                <?php if (!$organizzati): ?>
                    <p class="wp-sottotitolo mb-0">Nessun evento in programma.</p>
                <?php else: ?>
                    <ul class="wp-righe">
                        <?php foreach ($organizzati as $e): ?>
                            <li class="d-flex align-items-center gap-3 flex-wrap">
                                <?= $riga($e, (int) $e['confermato'] === 1 ? '' : '<span class="wp-stato wp-stato-attenzione">Da confermare</span>') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="wp-scheda" id="partecipazioni">
            <h2>A cui partecipi</h2>
            <?php if (!$partecipazioni): ?>
                <p class="wp-sottotitolo mb-0">Non partecipi ancora a nessun evento.</p>
            <?php else: ?>
                <ul class="wp-righe">
                    <?php foreach ($partecipazioni as $e): ?>
                        <li class="d-flex align-items-center gap-3 flex-wrap"><?= $riga($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($puoCreare): ?>
        <div class="col-xl-5">
            <form class="wp-scheda" id="nuovo" method="post" action="<?= e(url('eventi/crea')) ?>">
                <?= Csrf::campo() ?>
                <h2 class="mb-3">Nuovo evento</h2>
                <div class="mb-3">
                    <label class="form-label" for="ev-titolo">Titolo</label>
                    <input class="form-control" id="ev-titolo" name="titolo" maxlength="220" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-7">
                        <label class="form-label" for="ev-data">Data</label>
                        <input class="form-control" type="date" id="ev-data" name="data" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="ev-ora">Ora</label>
                        <input class="form-control" type="time" id="ev-ora" name="ora">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ev-luogo">Luogo</label>
                    <input class="form-control" id="ev-luogo" name="luogo" maxlength="220">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ev-descrizione">Descrizione</label>
                    <textarea class="form-control" id="ev-descrizione" name="descrizione" rows="3" maxlength="5000"></textarea>
                </div>

                <h3 class="wp-titolo-campi">Co-organizzatori (facoltativi)</h3>
                <p class="form-text mt-0">Chi indichi qui riceve la richiesta e diventa organizzatore solo dopo averla confermata. Tieni premuto Ctrl (o Cmd) per sceglierne più di uno.</p>
                <div class="mb-3">
                    <label class="form-label" for="ev-politici">Politici</label>
                    <select class="form-select" id="ev-politici" name="politici[]" multiple size="4">
                        <?php foreach ($politici as $id => $nome): ?>
                            <option value="<?= e($id) ?>"><?= e($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ev-testate">Testate</label>
                    <select class="form-select" id="ev-testate" name="testate[]" multiple size="4">
                        <?php foreach ($testate as $id => $nome): ?>
                            <?php if ((int) $id !== $miaTestata): ?>
                                <option value="<?= e($id) ?>"><?= e($nome) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ev-pubblico" name="pubblico" value="1" checked>
                    <label class="form-check-label" for="ev-pubblico">Evento pubblico: compare sul sito e i lettori possono partecipare</label>
                </div>
                <button class="btn btn-wp" type="submit">Crea evento</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
