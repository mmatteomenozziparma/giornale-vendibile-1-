/*
 * WolfPress · sito pubblico
 * Unico comportamento: i due filtri della tabella "Scegli un modello".
 * Senza JavaScript la tabella resta completa e tutti i link funzionano.
 */
(() => {
  'use strict';

  const tabella = document.querySelector('[data-modelli]');
  const livello = document.querySelector('[data-modelli-livello]');
  const tipo = document.querySelector('[data-modelli-tipo]');
  const conteggio = document.querySelector('[data-modelli-conteggio]');
  if (!tabella || !livello || !tipo) return;

  function aggiorna() {
    let righe = 0;
    tabella.querySelectorAll('tbody tr').forEach((riga) => {
      const visibile = livello.value === '' || riga.dataset.livello === livello.value;
      riga.hidden = !visibile;
      if (visibile) righe++;
    });

    // Mostra o nasconde le colonne "Candidatura" e "Mandato".
    tabella.querySelectorAll('[data-colonna]').forEach((cella) => {
      cella.hidden = tipo.value !== '' && cella.dataset.colonna !== tipo.value;
    });

    if (conteggio) {
      const modelli = righe * (tipo.value === '' ? 2 : 1);
      conteggio.textContent = `${modelli} ${modelli === 1 ? 'modello visibile' : 'modelli visibili'}`;
    }
  }

  livello.addEventListener('change', aggiorna);
  tipo.addEventListener('change', aggiorna);
})();
