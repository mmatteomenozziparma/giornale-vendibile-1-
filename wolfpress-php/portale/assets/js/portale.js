/*
 * WolfPress · portale (single page application)
 *
 * Il portale funziona come una normale applicazione PHP, ma questo script
 * intercetta i clic sui link e l'invio dei form: chiede al server solo il
 * contenuto interno della pagina (views/shell.php) e lo sostituisce dentro
 * #wp-shell, senza ricaricare il browser. Senza JavaScript tutto continua
 * a funzionare con i normali caricamenti di pagina.
 */
(() => {
  'use strict';

  const INTESTAZIONE_SPA = 'wolfpress-spa';
  const cartella = location.pathname.replace(/[^/]*$/, '');
  const shell = () => document.getElementById('wp-shell');
  let richiesta = 0;

  /* ---------------------------------------------------------------------
   * Navigazione senza ricaricare la pagina
   * ------------------------------------------------------------------- */

  /** true se l'indirizzo appartiene al portale (stesso sito, stessa cartella). */
  function interno(url) {
    if (url.origin !== location.origin) return false;
    const file = url.pathname.slice(cartella.length);
    return url.pathname.startsWith(cartella) && (file === '' || file === 'index.php');
  }

  /** Indirizzo senza ancora e senza "index.php", per confrontare due pagine. */
  const chiave = (url) => url.pathname.replace(/index\.php$/, '') + url.search;

  async function carica(indirizzo, opzioni = {}) {
    const numero = ++richiesta;
    const prima = new URL(location.href);
    const scorrimento = window.scrollY;
    document.documentElement.classList.add('wp-caricamento');

    try {
      const risposta = await fetch(indirizzo, {
        method: opzioni.metodo || 'GET',
        body: opzioni.corpo,
        headers: { 'X-Requested-With': INTESTAZIONE_SPA },
        credentials: 'same-origin',
      });
      const html = await risposta.text();
      if (numero !== richiesta) return; // nel frattempo è partita un'altra richiesta

      const finale = new URL(risposta.url);
      const tipo = risposta.headers.get('Content-Type') || '';
      // Se il server non ha risposto con il frammento atteso si ricarica la pagina intera.
      if (!tipo.includes('text/html') || /^\s*<!doctype/i.test(html) || !interno(finale)) {
        location.href = risposta.url;
        return;
      }

      const ancora = opzioni.ancora || new URL(indirizzo, location.href).hash.slice(1);
      if (ancora) finale.hash = ancora;

      sostituisci(html);

      if (opzioni.storia !== false) {
        const stessaPagina = chiave(finale) === chiave(prima);
        history[stessaPagina ? 'replaceState' : 'pushState']({}, '', finale.href);
      }

      // Dove posizionare la pagina dopo il cambio.
      const bersaglio = ancora && document.getElementById(ancora);
      const stessaRotta = finale.searchParams.get('r') === prima.searchParams.get('r');
      if (bersaglio) {
        bersaglio.scrollIntoView({ block: 'start' });
      } else if (opzioni.metodo === 'POST' && stessaRotta) {
        window.scrollTo(0, scorrimento); // dopo un salvataggio si resta dove si era
      } else {
        window.scrollTo(0, 0);
      }
      if (document.querySelector('.wp-avviso')) {
        const avviso = document.querySelector('.wp-avviso');
        if (avviso.getBoundingClientRect().top < 0) window.scrollTo(0, 0);
        document.getElementById('wp-annuncio').textContent = avviso.textContent.trim();
      } else {
        document.getElementById('wp-annuncio').textContent = document.title;
      }
    } catch (errore) {
      // Rete assente o errore imprevisto: si torna alla navigazione normale.
      if (opzioni.metodo !== 'POST') location.href = indirizzo;
      else document.getElementById('wp-annuncio').textContent = 'Invio non riuscito: controlla la connessione e riprova.';
    } finally {
      if (numero === richiesta) document.documentElement.classList.remove('wp-caricamento');
    }
  }

  /** Mette il nuovo contenuto dentro #wp-shell e aggiorna il titolo. */
  function sostituisci(html) {
    const documento = new DOMParser().parseFromString(html, 'text/html');
    const titolo = documento.querySelector('title');
    if (titolo) document.title = titolo.textContent;

    chiudiMenu(true);
    const contenitore = shell();
    contenitore.replaceChildren(...Array.from(documento.body.childNodes).map((nodo) => document.importNode(nodo, true)));
    prepara();
    document.getElementById('wp-main')?.focus({ preventScroll: true });
  }

  /** Chiude il menu laterale a scomparsa (telefono e tablet). */
  function chiudiMenu(sostituzione = false) {
    const menu = document.getElementById('wp-laterale');
    const istanza = menu && window.bootstrap?.Offcanvas.getInstance(menu);
    if (!istanza) return;
    if (!sostituzione) {
      istanza.hide();
      return;
    }
    // Il menu sta per essere sostituito: si elimina anche il velo scuro dietro.
    istanza.dispose();
    document.querySelectorAll('.offcanvas-backdrop').forEach((velo) => velo.remove());
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }

  document.addEventListener('click', (evento) => {
    if (evento.defaultPrevented || evento.button !== 0 || evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) return;
    const link = evento.target.closest('a[href]');
    if (!link || link.target || link.hasAttribute('download') || link.dataset.spa === 'off') return;

    const url = new URL(link.href, location.href);
    if (!interno(url)) return;

    // Stessa pagina, cambia solo l'ancora (#articoli): scorre il browser.
    if (chiave(url) === chiave(new URL(location.href)) && url.hash) {
      segnaVoce(link);
      chiudiMenu();
      return;
    }
    evento.preventDefault();
    carica(url.href);
  });

  document.addEventListener('submit', (evento) => {
    const modulo = evento.target;
    if (!(modulo instanceof HTMLFormElement) || modulo.dataset.spa === 'off') return;

    const pulsante = evento.submitter;
    const azione = new URL(pulsante?.getAttribute('formaction') || modulo.getAttribute('action') || location.href, location.href);
    if (!interno(azione)) return;

    const conferma = pulsante?.dataset.conferma || modulo.dataset.conferma;
    if (conferma && !window.confirm(conferma)) {
      evento.preventDefault();
      return;
    }
    evento.preventDefault();

    const dati = new FormData(modulo);
    if (pulsante?.name) dati.append(pulsante.name, pulsante.value);

    const metodo = (modulo.getAttribute('method') || 'get').toUpperCase();
    if (metodo === 'GET') {
      azione.search = '';
      for (const [nome, valore] of dati) {
        if (typeof valore === 'string') azione.searchParams.set(nome, valore);
      }
      carica(azione.href, { ancora: modulo.dataset.ancora });
      return;
    }

    if (pulsante) pulsante.disabled = true;
    carica(azione.href, { metodo: 'POST', corpo: dati, ancora: modulo.dataset.ancora })
      .finally(() => { if (pulsante?.isConnected) pulsante.disabled = false; });
  });

  // Pulsanti avanti e indietro del browser.
  window.addEventListener('popstate', () => carica(location.href, { storia: false }));

  /* ---------------------------------------------------------------------
   * Piccoli comportamenti delle pagine
   * ------------------------------------------------------------------- */

  /** Evidenzia la voce di menu cliccata quando porta a una sezione della stessa pagina. */
  function segnaVoce(link) {
    const menu = link.closest('.wp-menu');
    if (!menu) return;
    menu.querySelectorAll('a').forEach((voce) => {
      voce.classList.toggle('attiva', voce === link);
      voce.toggleAttribute('aria-current', voce === link);
    });
  }

  /** Filtra un elenco mentre si scrive o si sceglie uno stato. */
  function filtra(selettore) {
    const elenco = document.querySelector(selettore);
    if (!elenco) return;
    const testo = (document.querySelector(`[data-filtro="${selettore}"]`)?.value || '').trim().toLowerCase();
    const stato = document.querySelector(`[data-filtro-stato="${selettore}"]`)?.value || '';
    let visibili = 0;

    elenco.querySelectorAll('[data-voce]').forEach((voce) => {
      const corrisponde = (voce.dataset.testo || voce.textContent.toLowerCase()).includes(testo)
        && (stato === '' || voce.dataset.stato === stato);
      voce.classList.toggle('d-none', !corrisponde);
      if (corrisponde) visibili++;
    });

    const conteggio = document.querySelector(`[data-conteggio="${selettore}"]`);
    if (conteggio) {
      conteggio.textContent = `${visibili} ${visibili === 1 ? conteggio.dataset.singolare : conteggio.dataset.plurale}`;
    }
  }

  document.addEventListener('input', (evento) => {
    const campo = evento.target;
    if (campo.dataset.filtro) filtra(campo.dataset.filtro);
    if (campo.dataset.anteprima !== undefined || campo.dataset.anteprimaColore !== undefined) anteprima();
  });

  document.addEventListener('change', (evento) => {
    const campo = evento.target;
    if (campo.dataset.filtroStato) filtra(campo.dataset.filtroStato);
    if (campo.dataset.anteprimaModulo) anteprima();
    if (campo.dataset.anteprimaLogo !== undefined) anteprimaLogo(campo);
    // Le select "a invio automatico" (canale della chat, cliente) aprono subito la scelta.
    if (campo.dataset.invioAutomatico !== undefined) campo.form?.requestSubmit();
  });

  /** Configuratore: aggiorna l'anteprima con i valori scritti nel modulo. */
  function anteprima() {
    const finestra = document.querySelector('[data-anteprima-finestra]');
    if (!finestra) return;

    document.querySelectorAll('[data-anteprima]').forEach((campo) => {
      document.querySelectorAll(`[data-anteprima-testo="${campo.dataset.anteprima}"]`).forEach((nodo) => {
        nodo.textContent = campo.value;
      });
    });

    const testata = document.querySelector('[data-anteprima="testata"]')?.value || '';
    const sigla = finestra.querySelector('[data-anteprima-sigla]');
    if (sigla) {
      const maiuscole = testata.match(/\p{Lu}/gu) || [];
      const iniziali = testata.trim().split(/\s+/).map((parola) => parola.charAt(0).toUpperCase()).join('');
      sigla.textContent = (maiuscole.length >= 2 ? maiuscole.slice(0, 2).join('') : iniziali.length >= 2 ? iniziali.slice(0, 2) : testata.trim().slice(0, 2).toUpperCase()) || 'WP';
    }

    const colore = document.querySelector('[data-anteprima-colore]');
    if (colore) {
      finestra.style.setProperty('--wp-primary', colore.value);
      const codice = document.querySelector('[data-anteprima-codice]');
      if (codice) codice.textContent = colore.value;
    }

    document.querySelectorAll('[data-anteprima-modulo]').forEach((spunta) => {
      const voce = finestra.querySelector(`[data-anteprima-voce="${spunta.dataset.anteprimaModulo}"]`);
      if (voce) voce.hidden = !spunta.checked;
    });
  }

  /** Configuratore: mostra subito il logo scelto, prima del salvataggio. */
  function anteprimaLogo(campo) {
    const file = campo.files?.[0];
    const immagine = document.querySelector('[data-anteprima-immagine]');
    const sigla = document.querySelector('[data-anteprima-sigla]');
    if (!file || !immagine) return;

    if (file.size > 1048576 || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
      campo.value = '';
      campo.setCustomValidity('Scegli un\'immagine PNG, JPEG o WebP di massimo 1 MB.');
      campo.reportValidity();
      campo.setCustomValidity('');
      return;
    }
    const lettore = new FileReader();
    lettore.addEventListener('load', () => {
      immagine.src = lettore.result;
      immagine.hidden = false;
      if (sigla) sigla.hidden = true;
    });
    lettore.readAsDataURL(file);
  }

  /** Da eseguire dopo ogni cambio di contenuto. */
  function prepara() {
    // Le chat partono dall'ultimo messaggio.
    document.querySelectorAll('[data-chat]').forEach((chat) => { chat.scrollTop = chat.scrollHeight; });
  }

  prepara();
})();
