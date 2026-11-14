# Inventario WolfPress — perimetro fino al 6 novembre 2026

## Requisiti emersi dalle conversazioni

| Modulo | Stato in questo repository | Lavoro ancora necessario |
| --- | --- | --- |
| Homepage e notizie | Demo navigabile | Importare contenuti e grafica originali |
| Sezioni, articoli e ricerca | Demo navigabile | CMS, URL SEO, ricerca server-side |
| Video e Chi siamo | Pagine segnaposto | Video originali, trascrizioni e diritti |
| Pagina politica partiti regionali | Scheda dimostrativa | Entità reali e autorizzazioni |
| Scheda candidati (comunale, regionale, Camera, Senato, PE) | Demo | Modello dati e redazione |
| Scheda eletti | Demo | Profilo e storico articoli |
| Login editore | Pannello visuale senza login | Autenticazione e autorizzazioni |
| Login giornalista | Pannello visuale senza login | CMS e workflow articoli |
| Tecnici della società fornitrice | Pannello visuale | Gestione reale multisito |
| Portale cliente | Demo ticket | Account, ticket persistenti |
| Scheda referente cliente con foto, anagrafica e ticket | Non implementata | Privacy e CRM |
| Chat ticket e chat editoriale | Non implementate | WebSocket, conservazione e permessi |
| Videochat | Non implementata | Servizio e policy di trattamento |
| Tre articoli più letti | Area prevista | Analytics rispettosi della privacy |
| Eventi | Area prevista | CRUD, calendario, permessi |
| Hosting white-label per più testate | Architettura obiettivo | Tenant isolation, domini, provisioning |
| PDF delle schermate richiesto nella vecchia conversazione | Non recuperato | Importare PDF e asset per riscontro visivo |
| Prova3055 WordPress | Non recuperato | Esportare tema, media e contenuti |
| Precedente prototipo «Il Punto» | Relazione descrittiva recuperata | Recuperare dist/ e archivio sorgenti originali |

## Scelte di ingegneria

- Separazione esatta `frontend/` e `backend/`.
- Frontend senza framework per contenere la complessità del prototipo.
- Escape del contenuto dinamico in HTML.
- API demo locale: mai usare il selettore ruoli come controllo di accesso.
- I ticket demo non vengono salvati in database: nessuna falsa promessa di persistenza.
- Nome del prodotto: **WolfPress**, distinto dalle singole testate white-label.
- Indicizzazione disabilitata nella demo e dati editoriali dichiarati fittizi.

## Provenienza e limiti

La relazione tecnica del 26 ottobre 2026 descriveva `dist/index.html`, `dist/style.css`, `dist/articles.js`, `dist/app.js`, sei illustrazioni e un video, ma non forniva il codice sorgente recuperabile. I nuovi file in questo repository sono pertanto una **ricostruzione**, non un'ottimizzazione byte-per-byte dei vecchi file.

Il codice dell'app multi-pannello delle conversazioni 5–6 novembre e le immagini del PDF non erano disponibili come sorgenti verificati. Le funzioni non realizzate sono contrassegnate esplicitamente.

## Aggiornamento del 14 novembre 2026 — versione PHP (`wolfpress-php/`)

Il PDF «Schermate dei portali · Il Punto / Atlante Civico» (47 pagine) è stato fornito e usato per ricostruire le schermate in PHP (MVC) + Bootstrap. La tabella sopra descrive il prototipo Node.js in `frontend/` e `backend/`; nella versione PHP lo stato è questo:

| Modulo | Stato in `wolfpress-php/` | Lavoro ancora necessario |
| --- | --- | --- |
| Sito pubblico: home, notizie, testate, ricerca | Funzionante su dati demo | Contenuti reali, URL SEO, ricerca su database |
| Politica: partito regionale, candidature e mandati (5 livelli) | Funzionante su dati demo | Entità reali e autorizzazioni |
| Accesso editore, giornalista, politico, tecnico | Scelta del ruolo senza password, permessi lato server | Login reale e gestione utenti |
| Configuratore della testata e area cliente | Funzionanti | — |
| Account giornalisti, portfolio (tre più letti), eventi | Funzionanti | Analytics reali rispettosi della privacy |
| Articoli: bozza → revisione → pubblicazione | Funzionante | Storico delle modifiche, immagini |
| Area politico (gestione della propria pagina) | Funzionante | Autorizzazioni per partito |
| Console tecnica: infrastruttura, personale, clienti, referente, ticket | Funzionante su dati demo | Telemetria reale, multi-tenant |
| Scheda referente cliente con foto | Presente, con iniziali al posto della foto | Foto e privacy |
| Chat di redazione e chat dei ticket | Messaggi salvati, senza tempo reale | WebSocket, conservazione |
| Videochat | Link https a un servizio esterno | Servizio integrato e policy |
| Salvataggio dei dati | File JSON in `storage/` | Database, backup |
| PDF delle schermate | Fornito e usato come riferimento | — |
