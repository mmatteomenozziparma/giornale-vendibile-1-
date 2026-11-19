# WolfPress — backend PHP + SQL

Backend unico, dentro `wolfpress-php/backend/`, per i due front end di `wolfpress-php/` (**portale** e **sito**), in PHP 8.1+ senza librerie esterne, con database MySQL 8 / MariaDB 10.4+.

`wolfpress-php` carica direttamente le classi di `src/` (stesso database, stesso `.env`): per usarlo **non** serve pubblicare `public/`. L'intera cartella `backend/` è bloccata dal sito (`.htaccess` e `router.php` di `wolfpress-php`): le API JSON di `public/` servono solo ad altri client, per esempio un'app, e in quel caso vanno pubblicate su un indirizzo a parte che punti a `backend/php/public`.

```
wolfpress-php/backend/sql/schema.sql   tabelle del database
wolfpress-php/backend/php/src/         codice (non raggiungibile dal browser)
wolfpress-php/backend/php/bin/         comandi da terminale
wolfpress-php/backend/php/tests/       prova automatica completa
wolfpress-php/backend/php/public/      API JSON (facoltative, vedi sotto)
```

## Installazione

1. Creare il database: `mysql -u root -p < wolfpress-php/backend/sql/schema.sql`
2. Creare un utente del database con i soli permessi `SELECT, INSERT, UPDATE, DELETE` su `wolfpress.*`.
3. In `wolfpress-php/backend/php`: copiare `.env.example` in `.env`, eseguire `php bin/genera-chiavi.php` e incollare le tre chiavi nel `.env`, poi compilare database, SMTP e Stripe.
4. Creare il primo tecnico: `php bin/crea-tecnico.php Nome Cognome email@esempio.it responsabile`
   Chi aveva già creato il database con la prima versione dello schema esegue invece `wolfpress-php/backend/sql/aggiornamento-wolfpress.sql`.
5. Avvio in locale: `php -S localhost:8090 -t public public/index.php`. In produzione puntare la radice del sito a `public/` e usare **solo HTTPS** (`APP_ENV=produzione`).

Servono le estensioni PHP `pdo_mysql`, `sodium`, `curl`, `mbstring`.

> **Conservare una copia delle chiavi fuori dal server.** Senza `CHIAVE_CIFRATURA` le email salvate non sono più leggibili; senza `PEPE_PASSWORD` nessuno riesce più ad accedere.

## Tabelle

| Tabella | Contenuto |
|---|---|
| `generalita` | anagrafica + credenziali; porta a `user`, `editori`, `politici`, `tecnici`, `giornalisti`, `giornali` |
| `user`, `editori`, `politici`, `tecnici`, `giornalisti` | profilo di ciascun tipo di account |
| `giornali` | testate, collegate all'editore e alla propria scheda in `generalita` |
| `articoli_giornali`, `articoli_politici` | articoli dei giornali e articoli dei politici |
| `eventi` + `eventi_organizzatori` / `eventi_invitati` / `eventi_partecipanti` | organizzatori: politico, giornale o entrambi · invitati: politici o giornalisti · partecipanti: politici, giornalisti o user |
| `iscrizioni` | uno user segue un giornale o un politico |
| `abbonamenti`, `donazioni`, `stripe_eventi` | pagamenti Stripe |
| `ticket`, `ticket_messaggi` | assistenza |
| `partiti`, `messaggi_redazione`, `letture_giornaliere` | schede dei partiti, chat di redazione e letture per giorno usate da `wolfpress-php` |
| `token_email`, `tentativi` | token monouso delle email e limite ai tentativi |

## Sicurezza

- **Email**: cifrata con libsodium (`email_cifrata`) più indice cieco HMAC (`email_hash`) per ricerca e unicità. Nel database non c'è nessun indirizzo in chiaro.
- **Password**: non è cifrata ma sottoposta ad hash **Argon2id** con «pepe» segreto, quindi non è recuperabile da nessuno. Minimo 10 caratteri.
- **Registrazione**: email automatica con link di verifica (valido 24 ore, monouso, salvato solo come hash). Gli `user` sono attivi subito dopo la verifica; editori, politici e giornalisti devono essere approvati da un tecnico. I tecnici si creano solo da terminale.
- **Accesso**: blocco dell'account per 15 minuti dopo 5 errori, limite di tentativi per IP, risposte identiche per email esistenti o no, sessione rigenerata al login, cookie `HttpOnly`/`SameSite`/`Secure`, scadenza dopo 2 ore di inattività, tutte le sessioni chiuse al cambio password.
- **Richieste**: token CSRF obbligatorio su ogni POST, CORS limitato a `CORS_ORIGINI`, solo prepared statement, validazione di ogni campo, errori interni mai mostrati al client.
- **Stripe**: si usa Stripe Checkout, quindi i dati della carta non toccano mai questo server. L'importo è validato dal server e un pagamento diventa «pagato» solo con il webhook firmato di Stripe (firma, importo e sessione verificati; eventi ripetuti ignorati).

## Stripe

Nella dashboard Stripe creare un webhook verso `https://TUO-DOMINIO/sito/index.php?r=sostieni/webhook` (oppure `https://TUO-DOMINIO/api/stripe/webhook` se si pubblicano le API) con gli eventi `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`, `charge.refunded`, e copiare il segreto `whsec_...` nel `.env`.

Tutti gli incassi arrivano sull'unico conto Stripe della piattaforma: per versare automaticamente a ogni giornale o politico serve Stripe Connect (non incluso). Le donazioni ai politici sono soggette alle norme sul finanziamento della politica: verificarle con un consulente prima di attivarle.

## API

Il browser chiama prima `GET /api/csrf` e manda il valore ricevuto nell'intestazione `X-CSRF-Token` di ogni POST (corpo JSON, `credentials: 'include'`). Dopo l'accesso il token cambia: usare quello restituito da `/api/accesso`.

| Metodo e percorso | Chi | Cosa fa |
|---|---|---|
| `POST /api/registrazione` | tutti | `tipo` (user, editore, politico, giornalista), `nome`, `cognome`, `email`, `password`, `privacy: true` |
| `GET /api/verifica-email?token=` | link dell'email | conferma l'indirizzo |
| `POST /api/accesso` · `POST /api/uscita` · `GET /api/me` | | sessione |
| `POST /api/password/richiesta` · `POST /api/password/nuova` | tutti | password dimenticata |
| `GET /api/giornali` · `GET /api/giornali/{id}/articoli` | tutti | lettura pubblica |
| `POST /api/giornali` | editore | nuova testata |
| `POST /api/giornali/{id}/articoli` | editore, giornalista | nuovo articolo (il giornalista manda in revisione) |
| `POST /api/articoli-giornali/{id}/pubblica` | editore | pubblica |
| `GET /api/politici` · `GET /api/politici/{id}/articoli` | tutti | lettura pubblica |
| `POST /api/politici/articoli` | politico | nuovo articolo |
| `GET /api/eventi` · `GET /api/eventi/miei` | tutti · account | eventi pubblici · miei inviti e presenze |
| `POST /api/eventi` | politico, editore | crea (`politici_organizzatori`, `giornali_organizzatori`) |
| `POST /api/eventi/{id}/conferma` | politico, editore | accetta di co-organizzare |
| `POST /api/eventi/{id}/inviti` | organizzatore | invita `politici` e `giornalisti` (email automatica) |
| `POST /api/eventi/{id}/risposta` | politico, giornalista | `accetta: true/false` |
| `POST /api/eventi/{id}/partecipa` | politico, giornalista, user | partecipa |
| `POST /api/eventi/{id}/annulla` | organizzatore | annulla l'evento |
| `GET/POST /api/iscrizioni` · `POST /api/iscrizioni/annulla` | user | `giornale_id` oppure `politico_id` |
| `POST /api/donazioni` | tutti | `giornale_id` o `politico_id`, `importo_centesimi` → `url_pagamento` |
| `GET/POST /api/abbonamenti` | user | `giornale_id`, `piano` → `url_pagamento` |
| `POST /api/stripe/webhook` | Stripe | conferma dei pagamenti |
| `GET/POST /api/ticket` · `GET /api/ticket/{id}` · `POST /api/ticket/{id}/messaggi` | account | assistenza |
| `POST /api/ticket/{id}/gestione` | tecnico | presa in carico e stato |
| `GET /api/tecnico/account-in-attesa` · `POST /api/tecnico/account/{id}` | tecnico | `azione`: approva / sospendi |

## Prova automatica

Con lo schema caricato in un database **vuoto di prova** (non quello usato da `wolfpress-php`: la prova usa chiavi di cifratura proprie):

```bash
DB_UTENTE=wolfpress DB_PASSWORD=... php tests/prova.php
```

Avvia il server e una finta API Stripe ed esegue 77 controlli. Non contatta la vera Stripe né un vero server SMTP: quei due collegamenti vanno provati con le proprie chiavi di test.
