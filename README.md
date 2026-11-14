# WolfPress — giornale digitale vendibile (prototipo)

**WolfPress** è il nome definitivo della piattaforma. Sostituisce il nome provvisorio «Il Punto». Questo repository contiene una **ricostruzione dimostrativa** dei requisiti delle conversazioni fino al **6 novembre 2026**, non una copia integrale dei sorgenti storici, che non erano recuperabili.

## Cartelle
- `frontend/`: HTML, CSS e JavaScript responsive, homepage, articoli, sezioni, ricerca, politica e anteprima pannelli.
- `backend/`: server locale Node.js, API articoli e ticket in memoria, test automatici.
- `docs/INVENTARIO-E-GAP.md`: elenco delle funzioni richieste, stato reale e asset non recuperati.

- `wolfpress-php/`: **versione PHP (MVC) + Bootstrap** costruita sul PDF delle schermate del 6 novembre 2026. Contiene due front end: `portale/` (single page application per editori, giornalisti, politici e tecnici) e `sito/` (sito pubblico per i lettori). Istruzioni in `wolfpress-php/README.md`.

## Avvio della versione PHP
Installare **PHP >= 8.0**, aprire un terminale nella cartella `wolfpress-php` ed eseguire `php -S localhost:8080 router.php`. Poi aprire **http://localhost:8080/sito/** e **http://localhost:8080/portale/**.

## Avvio del prototipo Node.js (Windows o Mac)
Installare **Node.js >= 20** e aprire un terminale nella cartella `backend`.

```sh
npm test
npm start
```

Poi aprire **http://127.0.0.1:3000**. Non servono pacchetti npm esterni. Il server si collega esclusivamente a localhost e **non va esposto pubblicamente**.

## Cosa è funzionante
Homepage, sei articoli demo, sezioni, ricerca, schede politiche di esempio, struttura responsive, pannelli visuali per editore/giornalista/tecnico/cliente, ticket demo con API in memoria. Non sono implementate autenticazione, ruoli server-side, database permanente, workflow CMS reale, chat, videochat, sistemi multi-tenant e fatturazione.

## Sicurezza e pubblicazione
I pannelli non sono protetti da login: il selettore cambia soltanto la vista. Non inserire dati reali, credenziali o informazioni riservate. Prima della produzione occorrono sicurezza per tenant, autenticazione, database, autorizzazioni, backup, privacy, verifiche accessibilità e integrazione di un CMS.

## Materiale storico mancante
Il codice WordPress di **Prova3055**, la cartella originale `dist/` della demo «Il Punto» e le immagini originali non erano disponibili come file sorgente. Il PDF delle schermate del 6 novembre 2026 è stato fornito il 14 novembre 2026 ed è la base di `wolfpress-php/`: le schermate sono state ricostruite a partire dal PDF, non importate dai sorgenti originali.

Vedere `docs/INVENTARIO-E-GAP.md` per la matrice dettagliata.