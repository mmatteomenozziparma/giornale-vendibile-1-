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
