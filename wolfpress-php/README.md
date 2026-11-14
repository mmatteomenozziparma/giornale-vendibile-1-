# WolfPress · due front end in PHP (MVC) + Bootstrap

Ricostruzione in **PHP + Bootstrap 5 + CSS**, con architettura **MVC**, delle 46 schermate del PDF
«Schermate dei portali · Il Punto / Atlante Civico» (6 novembre 2026), con il nome definitivo **WolfPress**.

| Front end | Cartella | Per chi | Dal PDF |
| --- | --- | --- | --- |
| **Portale** (single page application) | `portale/` | editori, giornalisti, politici, tecnici dell'azienda | «Il Punto», pagine 02–25 |
| **Sito pubblico** | `sito/` | lettori che cercano notizie, politici, partiti | «Atlante Civico», pagine 26–47 |

I due front end condividono modelli e dati (`app/`): un articolo pubblicato dal portale compare subito sul sito,
e la scheda che un politico aggiorna nel portale è la stessa che i lettori vedono nella sezione Politica.

## Avvio

Serve soltanto **PHP 8.0 o superiore**. Nessun database, nessuna installazione: Bootstrap è già incluso in `assets/vendor/`.

**Con il server integrato di PHP** (Windows, Mac, Linux), dal terminale dentro `wolfpress-php/`:

```sh
php -S localhost:8080 router.php
```

- Sito pubblico: <http://localhost:8080/sito/>
- Portale: <http://localhost:8080/portale/>

**Con XAMPP / MAMP**: copia la cartella `wolfpress-php` dentro `htdocs` e apri
`http://localhost/wolfpress-php/sito/` e `http://localhost/wolfpress-php/portale/`.

La cartella `storage/` deve essere scrivibile: lì finiscono le modifiche fatte nel portale.

## Come si entra nel portale

L'accesso è **dimostrativo**: si sceglie un ruolo e un profilo di esempio, senza password.

| Ruolo | Cosa può fare |
| --- | --- |
| Editore | Configuratore della testata, area cliente, articoli (pubblica), account giornalisti, chat di redazione, ticket e chat di assistenza |
| Giornalista | Profilo con portfolio ed eventi, chat di redazione, scrittura articoli (bozza → in revisione) |
| Politico | Pagina di candidatura e pagina di mandato: profilo, proposte o atti, agenda, contatti |
| Operatore tecnico | Infrastruttura, personale tecnico, clienti e testate, referente cliente, coda dei ticket, chat e link video |

Dal menu in alto a destra: «Ripristina dati demo» annulla tutte le modifiche.

## Struttura (MVC)

```
wolfpress-php/
├── index.php              porta al sito pubblico
├── router.php             solo per "php -S": blocca app/ e storage/
├── .htaccess              stessa protezione su Apache
├── app/                   PARTE CONDIVISA
│   ├── config.php         nome del sito, debug
│   ├── bootstrap.php      avvio comune, intestazioni di sicurezza
│   ├── core/              Router, Controller, View, Model, Store, Auth, Csrf, helpers
│   ├── models/            MODEL: Articolo, Testata, Ticket, Giornalista, Evento,
│   │                      Messaggio, Operatore, Politico, Partito, Impostazioni
│   └── data/              dati demo di partenza (JSON, mai modificati)
├── storage/               modifiche salvate dal portale (non va su GitHub)
├── assets/                Bootstrap, icone, css/base.css (stile comune)
├── portale/               FRONT END 1 · single page application
│   ├── index.php          unico ingresso (front controller)
│   ├── controllers/       CONTROLLER: Accesso, Home, Configuratore, Cliente, Articoli,
│   │                      Editore, Giornalista, Assistenza, Politico, Tecnico, Demo…
│   ├── views/             VIEW: layout.php, shell.php, menu.php, topbar.php, footer.php,
│   │                      home.php, accesso.php, configuratore.php, cliente.php,
│   │                      editore.php, giornalista.php, politico.php,
│   │                      assistenza/*.php, tecnico/*.php, partials/*.php
│   └── assets/            css/portale.css, js/portale.js
└── sito/                  FRONT END 2 · sito pubblico
    ├── index.php          unico ingresso (front controller)
    ├── controllers/       CONTROLLER: Home, Notizie, Testate, Ricerca, Politica, Pagine
    ├── views/             VIEW: layout.php, menu.php, footer.php, home.php, ricerca.php,
    │                      testate.php, notizie/*.php, politica/*.php, partials/*.php
    └── assets/            css/sito.css, js/sito.js
```

**Come gira una richiesta**: `index.php?r=tecnico/salva-stato` → il `Router` apre
`TecnicoController` e chiama `salvaStato()` → il controller usa i **model** per leggere o salvare →
sceglie una **view** → la `View` la inserisce nel layout.

**Aggiungere una pagina**: 1) un metodo pubblico nel controller, 2) un file in `views/`,
3) una riga in `views/menu.php`.

### Perché il portale è una single page application

`portale/assets/js/portale.js` intercetta link e form, chiede al server solo la parte interna
della pagina (`views/shell.php`) e la sostituisce senza ricaricare il browser; indirizzi e tasto
«indietro» continuano a funzionare. Il rendering resta tutto in PHP. Senza JavaScript il portale
funziona comunque, con i normali caricamenti di pagina.

## Corrispondenza con le schermate del PDF

| Pagine PDF | Schermata | Indirizzo | Vista |
| --- | --- | --- | --- |
| 02–03 | Configuratore della testata | `portale/?r=configuratore` | `configuratore.php` |
| 04–05 | Dashboard cliente | `portale/?r=cliente` | `cliente.php` |
| 06, 13, 16 | Accesso editore, giornalista, tecnico | `portale/?r=accesso&ruolo=…` | `accesso.php` |
| 07–08 | Area editore | `portale/?r=editore` | `editore.php` |
| 09–11 | Assistenza editore e apertura ticket | `portale/?r=assistenza` | `assistenza/ticket.php` |
| 12 | Chat dell'editore | `portale/?r=assistenza/chat` | `assistenza/chat.php` |
| 14–15 | Profilo giornalista | `portale/?r=giornalista` | `giornalista.php` |
| 17 | Infrastruttura | `portale/?r=tecnico` | `tecnico/infrastruttura.php` |
| 18 | Personale tecnico | `portale/?r=tecnico/personale` | `tecnico/personale.php` |
| 19–20 | Clienti e testate | `portale/?r=tecnico/clienti` | `tecnico/clienti.php` |
| 21 | Referente cliente e storico | `portale/?r=tecnico/referente` | `tecnico/referente.php` |
| 22–23 | Coda tecnica dei ticket | `portale/?r=tecnico/ticket` | `tecnico/ticket.php` |
| 24–25 | Chat tecnica e link video | `portale/?r=tecnico/chat` | `tecnico/chat.php` |
| 26 | Sezione regionale del partito | `sito/?r=politica/partito` | `politica/partito.php` |
| 27 | Indice dei modelli | `sito/?r=politica` | `politica/index.php` |
| 28–47 | Candidatura e mandato (5 livelli) | `sito/?r=politica/profilo&livello=…&tipo=…` | `politica/profilo.php` |

**Aggiunte rispetto al PDF**: area politico nel portale, scrittura degli articoli con flusso
bozza → revisione → pubblicazione, e nel sito home, notizie, testate e ricerca unica.

## Limiti da conoscere

- **Accesso senza password**: va bene per la demo, non per un sito in rete. I permessi sono già
  controllati sul server per ruolo: per la produzione va sostituito `Auth::entra()` con un login vero.
- **Dati su file JSON** (`storage/`), non su database: adatti a una demo su un solo computer.
  Per passare a MySQL si riscrivono solo le classi in `app/models/`.
- **Contenuti fittizi**: testate, partito, persone, luoghi, date e atti sono inventati. La foto del
  referente del PDF è sostituita da un riquadro con le iniziali.
- Chat e videochiamate non sono in tempo reale: i messaggi compaiono al caricamento della pagina e
  la videochiamata è un link https a un servizio esterno.
- Non pubblicare la demo così com'è: mancano login, backup, informativa privacy e verifica di accessibilità.

## Sicurezza già presente

Testo sempre protetto in uscita (`e()`), codice anti-CSRF su ogni form, permessi per ruolo lato
server, controllo dei dati inviati, logo verificato sul contenuto reale del file (PNG, JPEG, WebP,
massimo 1 MB), link esterni solo `https://`, Content-Security-Policy senza script esterni,
cartelle `app/` e `storage/` non raggiungibili dal browser, nessun cookie sul sito pubblico.

## Licenze di terze parti

Bootstrap 5.3 e Bootstrap Icons 1.13 (licenza MIT), copiati in `assets/vendor/`.
