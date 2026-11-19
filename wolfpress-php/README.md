# WolfPress · due front end in PHP (MVC) + Bootstrap

Ricostruzione in **PHP + Bootstrap 5 + CSS**, con architettura **MVC**, delle 46 schermate del PDF
«Schermate dei portali · Il Punto / Atlante Civico» (6 novembre 2026), con il nome definitivo **WolfPress**.

| Front end | Cartella | Per chi | Dal PDF |
| --- | --- | --- | --- |
| **Portale** (single page application) | `portale/` | editori, giornalisti, politici, tecnici dell'azienda | «Il Punto», pagine 02–25 |
| **Sito pubblico** | `sito/` | lettori che cercano notizie, politici, partiti | «Atlante Civico», pagine 26–47 |

I due front end condividono modelli e dati (`app/`): un articolo pubblicato dal portale compare subito sul sito,
e la scheda che un politico aggiorna nel portale è la stessa che i lettori vedono nella sezione Politica.

**I dati sono nel database MySQL/MariaDB** e gli account sono veri: tutto passa dal backend in
`../backend/php` (cifratura delle email, hash delle password, email automatiche, Stripe).
Le classi del backend vengono caricate direttamente da `app/bootstrap.php`; la sua posizione è
la voce `backend` di `app/config.php`.

## Avvio

Servono **PHP 8.1 o superiore** (estensioni `pdo_mysql`, `sodium`, `curl`, `mbstring`) e **MySQL 8 / MariaDB 10.4+**.

1. Preparare il backend seguendo `../backend/php/README.md` (database da `backend/sql/schema.sql`, file `.env`, chiavi).
   Nel `.env` compilare anche `PORTALE_URL` e `SITO_URL`: finiscono nei link delle email e nel ritorno da Stripe.
2. Facoltativo, per avere subito contenuti di prova: `php bin/importa-demo.php` carica nel database
   testate, articoli, politici, ticket e 13 account dimostrativi, tutti con la password che viene chiesta.
   Senza importazione si parte vuoti: creare il primo tecnico con `php ../backend/php/bin/crea-tecnico.php`.
3. Avviare il sito.

**Con il server integrato di PHP** (Windows, Mac, Linux), dal terminale dentro `wolfpress-php/`:

```sh
php -S localhost:8080 router.php
```

- Sito pubblico: <http://localhost:8080/sito/>
- Portale: <http://localhost:8080/portale/>

**Con XAMPP / MAMP**: copia la cartella `wolfpress-php` dentro `htdocs` e apri
`http://localhost/wolfpress-php/sito/` e `http://localhost/wolfpress-php/portale/`.

La cartella `storage/` deve essere scrivibile: lì finiscono i loghi caricati dal configuratore.

## Come si entra

**Portale** (`portale/?r=accesso`): email e password. Editori, giornalisti e politici si registrano da
«Crea un account», confermano l'email dal link ricevuto e attendono l'approvazione di un tecnico
(pagina «Account da approvare»); l'editore può approvare da sé i giornalisti della propria testata.
Giornalisti e operatori possono anche essere creati dall'editore o da un tecnico: ricevono un'email
con il link per scegliere la password. Un editore appena approvato crea per prima cosa la propria testata.

**Sito** (`sito/?r=account`): i lettori si registrano e sono attivi appena confermano l'email. Da
«Segui o sostieni» (pagina Testate e schede dei politici) possono seguire, donare e abbonarsi; il
pagamento avviene sulla pagina di Stripe. Il webhook da indicare a Stripe è
`https://TUO-DOMINIO/sito/index.php?r=sostieni/webhook`.

| Ruolo | Cosa può fare |
| --- | --- |
| Editore | Configuratore della testata, area cliente, articoli (pubblica), account giornalisti, chat di redazione, ticket e chat di assistenza |
| Giornalista | Profilo con portfolio ed eventi, chat di redazione, scrittura articoli (bozza → in revisione) |
| Politico | Pagina di candidatura e pagina di mandato: profilo, proposte o atti, agenda, contatti |
| Operatore tecnico | Infrastruttura, personale tecnico, clienti e testate, referente cliente, coda dei ticket, chat e link video |

## Struttura (MVC)

```
wolfpress-php/
├── index.php              porta al sito pubblico
├── router.php             solo per "php -S": blocca app/, storage/, bin/ e tests/
├── .htaccess              stessa protezione su Apache
├── app/                   PARTE CONDIVISA
│   ├── config.php         nome del sito, debug, posizione del backend
│   ├── bootstrap.php      avvio comune, carica il backend, intestazioni di sicurezza
│   ├── core/              Router, Controller, View, Auth, Accesso, Csrf, helpers
│   ├── models/            MODEL (leggono e scrivono nel database): Articolo, Testata, Ticket,
│   │                      Giornalista, Evento, Messaggio, Operatore, Politico, Partito, Impostazioni
│   └── data/              dati demo usati solo da bin/importa-demo.php
├── bin/importa-demo.php   carica i dati demo nel database
├── tests/prova.php        prova automatica di portale e sito
├── storage/               loghi caricati (non va su GitHub)
├── assets/                Bootstrap, icone, css/base.css (stile comune)
├── portale/               FRONT END 1 · single page application
│   ├── index.php          unico ingresso (front controller)
│   ├── controllers/       CONTROLLER: Accesso, Home, Configuratore, Cliente, Articoli,
│   │                      Editore, Giornalista, Assistenza, Politico, Tecnico, Testata…
│   ├── views/             VIEW: layout.php, shell.php, menu.php, topbar.php, footer.php,
│   │                      home.php, accesso.php, configuratore.php, cliente.php,
│   │                      editore.php, giornalista.php, politico.php,
│   │                      assistenza/*.php, tecnico/*.php, partials/*.php
│   └── assets/            css/portale.css, js/portale.js
└── sito/                  FRONT END 2 · sito pubblico
    ├── index.php          unico ingresso (front controller)
    ├── controllers/       CONTROLLER: Home, Notizie, Testate, Ricerca, Politica, Pagine,
    │                      Account (lettori), Sostieni (segui, dona, abbonati, webhook Stripe)
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
| 06, 13, 16 | Accesso (ora unico, con email e password) | `portale/?r=accesso` | `accesso.php` |
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

- **Dove sono i dati**: account, testate, articoli, ticket, agenda dei giornalisti, chat di redazione,
  iscrizioni, donazioni e abbonamenti sono in tabelle dedicate. Restano in colonne JSON le parti
  ancora «a scheda libera»: proposte, atti e agenda dei politici (`politici.dati`), schede dei partiti
  (`partiti.dati`), dati di servizio e impostazioni delle testate (`giornali.dati`, `giornali.impostazioni`).
- **Eventi**: nel portale c'è solo l'agenda personale del giornalista. Eventi co-organizzati, inviti e
  partecipazioni esistono nel database e nelle API (`backend/php`) ma non hanno ancora una schermata.
- **Articoli dei politici** (`articoli_politici`): tabella e API pronte, nessuna schermata.
- **Un editore, una testata**: il portale lavora sulla prima testata dell'editore collegato.
- **Testi «demo»**: alcune viste contengono ancora diciture come «scenario demo» o «dati dimostrativi»,
  e le letture settimanali dell'area cliente restano numeri di esempio. I partiti si caricano solo
  con l'importazione dei dati demo: non c'è una schermata per crearli.
- La foto del referente del PDF è sostituita da un riquadro con le iniziali.
- Chat e videochiamate non sono in tempo reale: i messaggi compaiono al caricamento della pagina e
  la videochiamata è un link https a un servizio esterno.
- Prima di pubblicare servono HTTPS, backup del database e delle chiavi, un'informativa privacy vera e
  la verifica di accessibilità.

## Sicurezza già presente

Account veri gestiti dal backend (email cifrate, password Argon2id, verifica dell'email, blocco dopo
troppi errori, sessione rigenerata all'accesso e chiusa al cambio password), ogni redazione vede solo
i dati della propria testata, pagamenti solo su Stripe con conferma via webhook firmato.
Testo sempre protetto in uscita (`e()`), codice anti-CSRF su ogni form, permessi per ruolo lato
server, controllo dei dati inviati, logo verificato sul contenuto reale del file (PNG, JPEG, WebP,
massimo 1 MB), link esterni solo `https://`, Content-Security-Policy senza script esterni,
cartelle `app/`, `storage/`, `bin/` e `tests/` non raggiungibili dal browser, nessun cookie sulle
pagine di lettura del sito.

## Prova automatica

`php tests/prova.php` (istruzioni in cima al file, su un database **di prova**) avvia il server e una
finta API Stripe ed esegue oltre cento controlli su portale e sito: accessi, permessi per ruolo,
articoli, ticket, approvazioni, registrazione dei lettori, donazione e webhook.

## Licenze di terze parti

Bootstrap 5.3 e Bootstrap Icons 1.13 (licenza MIT), copiati in `assets/vendor/`.
