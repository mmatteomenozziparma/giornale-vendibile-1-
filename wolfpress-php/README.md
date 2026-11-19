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
`backend/php` (cifratura delle email, hash delle password, email automatiche, Stripe).
Le classi del backend vengono caricate direttamente da `app/bootstrap.php`; la sua posizione è
la voce `backend` di `app/config.php`.

## Avvio

Servono **PHP 8.1 o superiore** (estensioni `pdo_mysql`, `sodium`, `curl`, `mbstring`) e **MySQL 8 / MariaDB 10.4+**.

1. Preparare il backend seguendo `backend/php/README.md` (database da `backend/sql/schema.sql`, file `.env`, chiavi).
   Nel `.env` compilare anche `PORTALE_URL` e `SITO_URL`: finiscono nei link delle email e nel ritorno da Stripe.
2. Facoltativo, per avere subito contenuti di prova: `php bin/importa-demo.php` carica nel database
   testate, articoli, politici, ticket e 13 account dimostrativi, tutti con la password che viene chiesta.
   Senza importazione si parte vuoti: creare il primo tecnico con `php backend/php/bin/crea-tecnico.php`.
3. Avviare il sito.

**Con il server integrato di PHP** (Windows, Mac, Linux), dal terminale dentro `wolfpress-php/`:

```sh
php -S localhost:8080 router.php
```

- Sito pubblico: <http://localhost:8080/sito/> (login: `sito/login.php`, iscrizione: `sito/registrazione.php`)
- Portale: <http://localhost:8080/portale/> (login: `portale/login.php`, iscrizione: `portale/registrazione.php`)

Le pagine vanno aperte dal server (indirizzi `http://localhost...`), non con un doppio clic sul file:
solo così il PHP viene eseguito.

**Con XAMPP / MAMP**: copia la cartella `wolfpress-php` dentro `htdocs` e apri
`http://localhost/wolfpress-php/sito/` e `http://localhost/wolfpress-php/portale/`.

La cartella `storage/` deve essere scrivibile: lì finiscono i loghi caricati dal configuratore.

## Come si entra

**Portale** (`portale/login.php`): email e password. Editori, giornalisti e politici si registrano da
«Crea un account», confermano l'email dal link ricevuto e attendono l'approvazione di un tecnico
(pagina «Account da approvare»); l'editore può approvare da sé i giornalisti della propria testata.
Giornalisti e operatori possono anche essere creati dall'editore o da un tecnico: ricevono un'email
con il link per scegliere la password. Un editore appena approvato crea per prima cosa la propria testata.

**Eventi** (`portale/eventi.php`): politici ed editori creano eventi, anche insieme (il co-organizzatore
indicato deve confermare), invitano politici e giornalisti e possono annullarli; gli invitati accettano o
rifiutano. Gli eventi pubblici compaiono in `sito/eventi.php`, dove i lettori segnano la partecipazione.
L'agenda nella scheda di un politico è l'elenco dei suoi eventi pubblici.

**Altro nel portale**: il politico scrive i propri articoli (`portale/politico-articoli.php`) e sceglie il
partito; i tecnici gestiscono i partiti (`portale/tecnico-partiti.php`); un editore può avere più testate e
passa dall'una all'altra dal menu in alto a destra; nel configuratore imposta i prezzi degli abbonamenti.

**Sito** (`sito/login.php`, `sito/registrazione.php`, `sito/account.php`): i lettori si registrano e sono attivi appena confermano l'email. Da
«Segui o sostieni» (pagina Testate e schede dei politici) possono seguire, donare e abbonarsi; il
pagamento avviene sulla pagina di Stripe. Il webhook da indicare a Stripe è
`https://TUO-DOMINIO/sito/stripe-webhook.php`.

| Ruolo | Cosa può fare |
| --- | --- |
| Editore | Configuratore della testata, area cliente, articoli (pubblica), account giornalisti, chat di redazione, ticket e chat di assistenza |
| Giornalista | Profilo con portfolio ed eventi, chat di redazione, scrittura articoli (bozza → in revisione) |
| Politico | Pagina di candidatura e pagina di mandato: profilo, proposte o atti, agenda, contatti |
| Operatore tecnico | Infrastruttura, personale tecnico, clienti e testate, referente cliente, coda dei ticket, chat e link video |

## Struttura

**Ogni pagina è un file `.php`** apribile dal browser, con dentro HTML + PHP e gli `include` di
`header.php`, `menu.php` e `footer.php`:

```php
<?php
require_once __DIR__ . '/avvio.php';     // avvio comune del front end
extract(pagina(__FILE__));               // dati preparati dal controller
require __DIR__ . '/header.php';         // <head>, stili, apertura di <body>
require __DIR__ . '/menu.php';           // menu (e apertura di <main>)
?>
<h1>...HTML + PHP della pagina...</h1>
<?php require __DIR__ . '/footer.php'; ?>
```

```
wolfpress-php/
├── index.php              porta al sito pubblico
├── router.php             solo per "php -S": blocca le cartelle interne
├── .htaccess              stessa protezione su Apache
├── app/                   PARTE CONDIVISA
│   ├── config.php         nome del sito, debug, posizione del backend
│   ├── bootstrap.php      avvio comune, carica il backend, intestazioni di sicurezza
│   ├── core/              Pagina, Controller, View, Auth, Accesso, Csrf, helpers
│   ├── models/            MODEL (leggono e scrivono nel database): Articolo, ArticoloPolitico, Testata,
│   │                      Ticket, Giornalista, Evento, Letture, Messaggio, Operatore, Politico,
│   │                      Partito, Impostazioni
│   └── data/              dati demo usati solo da bin/importa-demo.php
├── backend/               BACKEND (non raggiungibile dal browser)
│   ├── sql/               schema.sql: tabelle del database
│   └── php/               database, cifratura, email, Stripe, account; .env; API JSON
├── bin/importa-demo.php   carica i dati demo nel database
├── tests/prova.php        prova automatica di portale e sito
├── storage/               loghi caricati (non va su GitHub)
├── assets/                Bootstrap, icone, css/base.css (stile comune)
├── sito/                  FRONT END 1 · sito pubblico
│   ├── header.php, menu.php, footer.php     inclusi da ogni pagina
│   ├── avvio.php, pagine.php                avvio comune ed elenco delle pagine
│   ├── index.php (home), notizie.php, articolo.php, testate.php, politica.php, partito.php,
│   │   politico.php, articolo-politico.php, eventi.php, ricerca.php, sostieni.php,
│   │   login.php, registrazione.php, password.php, account.php,
│   │   chi-siamo.php, privacy.php, accessibilita.php, grazie.php, annullato.php, errore.php
│   ├── stripe-webhook.php                   chiamato da Stripe per confermare i pagamenti
│   ├── controllers/       logica di ogni pagina (lettura dei dati, salvataggi, permessi)
│   ├── parti/             pezzi di pagina riutilizzati (scheda articolo, mappa, modelli)
│   └── assets/            css/sito.css, js/sito.js
└── portale/               FRONT END 2 · single page application
    ├── header.php, menu.php, footer.php     inclusi da ogni pagina
    ├── avvio.php, pagine.php                avvio comune (sessione, permessi) ed elenco delle pagine
    ├── index.php (le tue aree), login.php, registrazione.php, password.php, testata.php,
    │   configuratore.php, cliente.php, articoli.php, articolo.php, editore.php, redattore.php,
    │   giornalista.php, politico.php, politico-articoli.php, eventi.php, evento.php,
    │   assistenza.php, assistenza-chat.php, tecnico.php, tecnico-personale.php,
    │   tecnico-account.php, tecnico-partiti.php, tecnico-clienti.php, tecnico-referente.php,
    │   tecnico-ticket.php, tecnico-chat.php, errore.php, logo.php
    ├── controllers/       logica di ogni pagina
    ├── parti/             pezzi di pagina riutilizzati (barra in alto, ticket, chat, agenda)
    └── assets/            css/portale.css, js/portale.js
```

**Come gira una richiesta**: il browser apre `portale/tecnico-personale.php` → il file chiama
`pagina(__FILE__)`, che in `pagine.php` trova il controller (`TecnicoController::personale()`) → il
controller controlla i permessi, usa i **model** e restituisce i dati → il file prosegue con
`header.php`, `menu.php`, il proprio HTML e `footer.php`.

**I form** chiamano un'azione dello stesso controller con `?azione=`: per esempio il modulo di login
invia a `login.php?azione=entra`, quello di un nuovo operatore a `tecnico.php?azione=salva-operatore`.
L'azione salva e rimanda alla pagina giusta.

**Aggiungere una pagina**: 1) copiare un file di pagina esistente e cambiarne l'HTML, 2) aggiungere una
riga in `pagine.php`, 3) scrivere nel controller il metodo che prepara i dati, 4) una voce in `menu.php`.

### Perché il portale è una single page application

`portale/assets/js/portale.js` intercetta link e form, chiede al server solo la parte interna
della pagina (`header.php` e `footer.php` in quel caso non scrivono `<head>` e script) e la sostituisce
senza ricaricare il browser; indirizzi e tasto
«indietro» continuano a funzionare. Il rendering resta tutto in PHP. Senza JavaScript il portale
funziona comunque, con i normali caricamenti di pagina.

## Corrispondenza con le schermate del PDF

| Pagine PDF | Schermata | File |
| --- | --- | --- |
| 02–03 | Configuratore della testata | `portale/configuratore.php` |
| 04–05 | Dashboard cliente | `portale/cliente.php` |
| 06, 13, 16 | Accesso (ora unico, con email e password) | `portale/login.php` |
| 07–08 | Area editore | `portale/editore.php` |
| 09–11 | Assistenza editore e apertura ticket | `portale/assistenza.php` |
| 12 | Chat dell'editore | `portale/assistenza-chat.php` |
| 14–15 | Profilo giornalista | `portale/giornalista.php` |
| 17 | Infrastruttura | `portale/tecnico.php` |
| 18 | Personale tecnico | `portale/tecnico-personale.php` |
| 19–20 | Clienti e testate | `portale/tecnico-clienti.php` |
| 21 | Referente cliente e storico | `portale/tecnico-referente.php` |
| 22–23 | Coda tecnica dei ticket | `portale/tecnico-ticket.php` |
| 24–25 | Chat tecnica e link video | `portale/tecnico-chat.php` |
| 26 | Sezione regionale del partito | `sito/partito.php` |
| 27 | Indice dei modelli | `sito/politica.php` |
| 28–47 | Candidatura e mandato (5 livelli) | `sito/politico.php?livello=…&tipo=…` |

**Aggiunte rispetto al PDF**: area politico nel portale, scrittura degli articoli con flusso
bozza → revisione → pubblicazione, e nel sito home, notizie, testate e ricerca unica.

## Limiti da conoscere

- **Dove sono i dati**: account, testate, articoli (dei giornali e dei politici), ticket, eventi con
  organizzatori, invitati e partecipanti, chat di redazione, letture, iscrizioni, donazioni e abbonamenti
  sono in tabelle dedicate. Restano in colonne JSON le parti «a scheda libera»: proposte e atti dei
  politici (`politici.dati`), circoli e contatti dei partiti (`partiti.dati`), dati di servizio e
  impostazioni delle testate (`giornali.dati`, `giornali.impostazioni`).
- **Diciture «demo»**: con `'demo' => true` in `app/config.php` le pagine mostrano le note «demo»,
  «fittizio», «dimostrativo» (giuste per i dati di `bin/importa-demo.php`). Con dati veri mettere `false`.
- **Letture**: ogni apertura di un articolo sul sito conta una lettura, senza distinguere visitatori
  ripetuti o robot.
- **Partiti**: i tecnici modificano nome, sezione, descrizione, circoli e contatti. Le notizie e gli
  appuntamenti scritti a mano nei dati demo restano com'erano; a questi si aggiungono da soli gli
  articoli e gli eventi pubblici dei politici del partito.
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
cartelle `app/`, `storage/`, `bin/`, `tests/`, `backend/` e le cartelle `controllers/`, `core/`, `parti/` dei due front end non raggiungibili dal browser, nessun cookie sulle
pagine di lettura del sito.

## Prova automatica

`php tests/prova.php` (istruzioni in cima al file, su un database **di prova**) avvia il server e una
finta API Stripe ed esegue 158 controlli su portale e sito: accessi, permessi per ruolo, articoli, ticket,
approvazioni, eventi e inviti, partiti, più testate, registrazione dei lettori, donazione, abbonamento e webhook.

## Licenze di terze parti

Bootstrap 5.3 e Bootstrap Icons 1.13 (licenza MIT), copiati in `assets/vendor/`.
