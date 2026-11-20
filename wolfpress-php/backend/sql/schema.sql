-- =====================================================================
-- WolfPress — schema del database (MySQL 8.0.16+ / MariaDB 10.4+)
-- Serve i due front end: portale (editori, giornalisti, politici,
-- tecnici) e sito pubblico (lettori = user).
--
-- Uso:  mysql -u root -p < wolfpress-php/backend/sql/schema.sql
--
-- Regole di sicurezza applicate nello schema:
--  * l'email NON è mai salvata in chiaro: `email_cifrata` (cifratura
--    autenticata, chiave fuori dal database) + `email_hash` (indice
--    cieco HMAC, serve per cercare e garantire l'unicità);
--  * la password è salvata solo come hash Argon2id (`password_hash`);
--  * i token inviati per email sono salvati solo come hash SHA-256;
--  * dei pagamenti si salvano solo gli identificativi Stripe:
--    nessun dato di carta passa dal server o dal database.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS wolfpress
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wolfpress;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- GENERALITÀ: anagrafica e credenziali. È la tabella centrale: ogni
-- riga porta a una (e una sola) tra user, editori, politici, tecnici,
-- giornalisti, giornali. Per i giornali è la scheda di contatto della
-- testata (nessuna password: un giornale non fa login).
-- ---------------------------------------------------------------------
CREATE TABLE generalita (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo                 ENUM('user','editore','politico','tecnico','giornalista','giornale') NOT NULL,
  nome                 VARCHAR(120) NOT NULL,
  cognome              VARCHAR(120) NULL,
  email_cifrata        VARCHAR(600) NOT NULL,
  email_hash           CHAR(64)     NOT NULL,
  password_hash        VARCHAR(255) NULL,
  telefono_cifrato     VARCHAR(255) NULL,
  stato                ENUM('da_verificare','in_approvazione','attivo','sospeso') NOT NULL DEFAULT 'da_verificare',
  email_verificata_il  DATETIME NULL,
  consenso_privacy_il  DATETIME NULL,
  accessi_falliti      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloccato_fino        DATETIME NULL,
  ultimo_accesso_il    DATETIME NULL,
  creato_il            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_generalita_email (email_hash),
  KEY ix_generalita_tipo_stato (tipo, stato)
) ENGINE=InnoDB;

-- Lettori registrati del sito pubblico.
CREATE TABLE `user` (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id  BIGINT UNSIGNED NOT NULL,
  nickname       VARCHAR(60)  NULL,
  citta          VARCHAR(120) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_generalita (generalita_id),
  CONSTRAINT fk_user_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE editori (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id    BIGINT UNSIGNED NOT NULL,
  ragione_sociale  VARCHAR(200) NOT NULL,
  partita_iva      VARCHAR(20)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_editori_generalita (generalita_id),
  CONSTRAINT fk_editori_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE politici (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id  BIGINT UNSIGNED NOT NULL,
  slug           VARCHAR(160) NOT NULL,
  partito        VARCHAR(160) NULL,
  carica         VARCHAR(160) NULL,
  territorio     VARCHAR(160) NULL,
  biografia      TEXT NULL,
  livello        ENUM('comune','regione','camera','senato','europa') NOT NULL DEFAULT 'comune',
  -- JSON: schede "candidatura" e "mandato" mostrate dal sito (proposte, atti, agenda, contatti)
  dati           LONGTEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_politici_generalita (generalita_id),
  UNIQUE KEY uq_politici_slug (slug),
  CONSTRAINT fk_politici_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tecnici (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id  BIGINT UNSIGNED NOT NULL,
  qualifica      VARCHAR(120) NOT NULL DEFAULT 'Supporto tecnico',
  livello        ENUM('operatore','responsabile') NOT NULL DEFAULT 'operatore',
  turno          VARCHAR(60) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tecnici_generalita (generalita_id),
  CONSTRAINT fk_tecnici_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE giornali (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id           BIGINT UNSIGNED NOT NULL,
  editore_id              BIGINT UNSIGNED NOT NULL,
  nome                    VARCHAR(160) NOT NULL,
  slug                    VARCHAR(160) NOT NULL,
  descrizione             TEXT NULL,
  dominio                 VARCHAR(190) NULL,
  stato                   ENUM('bozza','attivo','sospeso') NOT NULL DEFAULT 'attivo',
  prezzo_mensile_cent     INT UNSIGNED NULL,
  prezzo_annuale_cent     INT UNSIGNED NULL,
  -- JSON: dati di servizio gestiti dai tecnici (stato, nodo, backup, piano, referente, social)
  dati                    LONGTEXT NULL,
  -- JSON: scelte del configuratore dell'editore (colore, logo, moduli, canali della chat)
  impostazioni            LONGTEXT NULL,
  creato_il               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_giornali_generalita (generalita_id),
  UNIQUE KEY uq_giornali_slug (slug),
  KEY ix_giornali_editore (editore_id),
  CONSTRAINT fk_giornali_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id),
  CONSTRAINT fk_giornali_editore    FOREIGN KEY (editore_id)    REFERENCES editori (id)
) ENGINE=InnoDB;

-- Giornalisti: servono per firmare gli articoli e per inviti/presenze agli eventi.
CREATE TABLE giornalisti (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id  BIGINT UNSIGNED NOT NULL,
  giornale_id    BIGINT UNSIGNED NOT NULL,
  qualifica      VARCHAR(120) NOT NULL DEFAULT 'Giornalista',
  sezione        VARCHAR(120) NULL,
  bio            VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_giornalisti_generalita (generalita_id),
  KEY ix_giornalisti_giornale (giornale_id),
  CONSTRAINT fk_giornalisti_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE,
  CONSTRAINT fk_giornalisti_giornale   FOREIGN KEY (giornale_id)   REFERENCES giornali (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ISCRIZIONI: seguire non equivale a consentire newsletter o propaganda.
-- Un consenso valido va acquisito e dimostrato per finalita e titolare.
-- ISCRIZIONI: uno user segue (gratis) un giornale oppure un politico.
-- ---------------------------------------------------------------------
CREATE TABLE iscrizioni (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  giornale_id  BIGINT UNSIGNED NULL,
  politico_id  BIGINT UNSIGNED NULL,
  newsletter   TINYINT(1) NOT NULL DEFAULT 0,
  creato_il    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_iscrizioni_giornale (user_id, giornale_id),
  UNIQUE KEY uq_iscrizioni_politico (user_id, politico_id),
  CONSTRAINT fk_iscrizioni_user     FOREIGN KEY (user_id)     REFERENCES `user` (id) ON DELETE CASCADE,
  CONSTRAINT fk_iscrizioni_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id),
  CONSTRAINT fk_iscrizioni_politico FOREIGN KEY (politico_id) REFERENCES politici (id),
  CONSTRAINT ck_iscrizioni_destinatario CHECK ((giornale_id IS NOT NULL) + (politico_id IS NOT NULL) = 1)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ABBONAMENTI: uno user paga un giornale (Stripe Checkout, ricorrente).
-- Lo stato cambia SOLO tramite webhook firmato di Stripe.
-- ---------------------------------------------------------------------
CREATE TABLE abbonamenti (
  id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id                     BIGINT UNSIGNED NOT NULL,
  giornale_id                 BIGINT UNSIGNED NOT NULL,
  piano                       ENUM('mensile','annuale') NOT NULL,
  importo_centesimi           INT UNSIGNED NOT NULL,
  valuta                      CHAR(3) NOT NULL DEFAULT 'eur',
  stato                       ENUM('in_attesa','attivo','insoluto','annullato','scaduto') NOT NULL DEFAULT 'in_attesa',
  stripe_checkout_session_id  VARCHAR(255) NULL,
  stripe_customer_id          VARCHAR(255) NULL,
  stripe_subscription_id      VARCHAR(255) NULL,
  inizio_il                   DATETIME NULL,
  scade_il                    DATETIME NULL,
  creato_il                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_abbonamenti_sessione (stripe_checkout_session_id),
  UNIQUE KEY uq_abbonamenti_subscription (stripe_subscription_id),
  KEY ix_abbonamenti_user (user_id, giornale_id, stato),
  CONSTRAINT fk_abbonamenti_user     FOREIGN KEY (user_id)     REFERENCES `user` (id),
  CONSTRAINT fk_abbonamenti_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DONAZIONI: una tantum, a un giornale oppure a un politico.
-- user_id è NULL per le donazioni senza account.
-- ---------------------------------------------------------------------
CREATE TABLE donazioni (
  id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id                     BIGINT UNSIGNED NULL,
  giornale_id                 BIGINT UNSIGNED NULL,
  politico_id                 BIGINT UNSIGNED NULL,
  importo_centesimi           INT UNSIGNED NOT NULL,
  valuta                      CHAR(3) NOT NULL DEFAULT 'eur',
  messaggio                   VARCHAR(500) NULL,
  stato                       ENUM('in_attesa','pagata','scaduta','fallita','rimborsata') NOT NULL DEFAULT 'in_attesa',
  stripe_checkout_session_id  VARCHAR(255) NULL,
  stripe_payment_intent_id    VARCHAR(255) NULL,
  pagata_il                   DATETIME NULL,
  creato_il                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_donazioni_sessione (stripe_checkout_session_id),
  KEY ix_donazioni_intent (stripe_payment_intent_id),
  KEY ix_donazioni_user (user_id),
  CONSTRAINT fk_donazioni_user     FOREIGN KEY (user_id)     REFERENCES `user` (id),
  CONSTRAINT fk_donazioni_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id),
  CONSTRAINT fk_donazioni_politico FOREIGN KEY (politico_id) REFERENCES politici (id),
  CONSTRAINT ck_donazioni_destinatario CHECK ((giornale_id IS NOT NULL) + (politico_id IS NOT NULL) = 1),
  CONSTRAINT ck_donazioni_importo CHECK (importo_centesimi >= 100)
) ENGINE=InnoDB;

-- Eventi Stripe già elaborati: evita di applicare due volte lo stesso webhook.
CREATE TABLE stripe_eventi (
  id           VARCHAR(255) NOT NULL,
  tipo         VARCHAR(120) NOT NULL,
  ricevuto_il  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TICKET di assistenza (aperti da chiunque abbia un account, gestiti dai tecnici).
-- ---------------------------------------------------------------------
CREATE TABLE ticket (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice       VARCHAR(20) NOT NULL,
  aperto_da    BIGINT UNSIGNED NOT NULL,
  giornale_id  BIGINT UNSIGNED NULL,
  tecnico_id   BIGINT UNSIGNED NULL,
  oggetto      VARCHAR(200) NOT NULL,
  descrizione  TEXT NOT NULL,
  mittente     VARCHAR(120) NULL,
  video        VARCHAR(300) NULL,
  priorita     ENUM('bassa','normale','alta','urgente') NOT NULL DEFAULT 'normale',
  stato        ENUM('aperto','in_lavorazione','in_attesa_cliente','risolto','chiuso') NOT NULL DEFAULT 'aperto',
  creato_il    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  chiuso_il    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ticket_codice (codice),
  KEY ix_ticket_stato (stato, priorita),
  KEY ix_ticket_aperto_da (aperto_da),
  CONSTRAINT fk_ticket_aperto_da FOREIGN KEY (aperto_da)   REFERENCES generalita (id),
  CONSTRAINT fk_ticket_giornale  FOREIGN KEY (giornale_id) REFERENCES giornali (id) ON DELETE SET NULL,
  CONSTRAINT fk_ticket_tecnico   FOREIGN KEY (tecnico_id)  REFERENCES tecnici (id)  ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket_messaggi (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id  BIGINT UNSIGNED NOT NULL,
  autore_id  BIGINT UNSIGNED NOT NULL,
  testo      TEXT NOT NULL,
  creato_il  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_ticket_messaggi_ticket (ticket_id, creato_il),
  CONSTRAINT fk_ticket_messaggi_ticket FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_messaggi_autore FOREIGN KEY (autore_id) REFERENCES generalita (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ARTICOLI dei giornali e ARTICOLI dei politici (due tabelle distinte).
-- ---------------------------------------------------------------------
CREATE TABLE articoli_giornali (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  giornale_id     BIGINT UNSIGNED NOT NULL,
  giornalista_id  BIGINT UNSIGNED NULL,
  -- nome mostrato come autore quando l'articolo non è di un giornalista (es. scritto dall'editore)
  firma           VARCHAR(160) NULL,
  letture         INT UNSIGNED NOT NULL DEFAULT 0,
  titolo          VARCHAR(220) NOT NULL,
  slug            VARCHAR(240) NOT NULL,
  sezione         VARCHAR(120) NULL,
  sommario        VARCHAR(500) NULL,
  corpo           MEDIUMTEXT NOT NULL,
  stato           ENUM('bozza','in_revisione','pubblicato','archiviato') NOT NULL DEFAULT 'bozza',
  pubblicato_il   DATETIME NULL,
  creato_il       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_articoli_giornali_slug (giornale_id, slug),
  KEY ix_articoli_giornali_pubblici (giornale_id, stato, pubblicato_il),
  CONSTRAINT fk_articoli_giornali_giornale    FOREIGN KEY (giornale_id)    REFERENCES giornali (id),
  CONSTRAINT fk_articoli_giornali_giornalista FOREIGN KEY (giornalista_id) REFERENCES giornalisti (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE articoli_politici (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  politico_id    BIGINT UNSIGNED NOT NULL,
  titolo         VARCHAR(220) NOT NULL,
  slug           VARCHAR(240) NOT NULL,
  categoria      VARCHAR(120) NULL,
  sommario       VARCHAR(500) NULL,
  corpo          MEDIUMTEXT NOT NULL,
  stato          ENUM('bozza','pubblicato','archiviato') NOT NULL DEFAULT 'bozza',
  pubblicato_il  DATETIME NULL,
  creato_il      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_articoli_politici_slug (politico_id, slug),
  KEY ix_articoli_politici_pubblici (politico_id, stato, pubblicato_il),
  CONSTRAINT fk_articoli_politici_politico FOREIGN KEY (politico_id) REFERENCES politici (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENTI
--  * organizzatori: un politico, un giornale, oppure entrambi insieme
--    (una riga per ciascun organizzatore);
--  * invitati: politici o giornalisti;
--  * partecipanti: politici, giornalisti o user.
-- ---------------------------------------------------------------------
CREATE TABLE eventi (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  titolo       VARCHAR(220) NOT NULL,
  descrizione  TEXT NULL,
  luogo        VARCHAR(220) NULL,
  inizio       DATETIME NOT NULL,
  fine         DATETIME NULL,
  pubblico     TINYINT(1) NOT NULL DEFAULT 1,
  stato        ENUM('programmato','annullato') NOT NULL DEFAULT 'programmato',
  creato_da    BIGINT UNSIGNED NOT NULL,
  creato_il    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_eventi_inizio (inizio),
  CONSTRAINT fk_eventi_creato_da FOREIGN KEY (creato_da) REFERENCES generalita (id)
) ENGINE=InnoDB;

CREATE TABLE eventi_organizzatori (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id    BIGINT UNSIGNED NOT NULL,
  politico_id  BIGINT UNSIGNED NULL,
  giornale_id  BIGINT UNSIGNED NULL,
  -- 0 = co-organizzatore aggiunto da un altro, in attesa della sua conferma
  confermato   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eventi_org_politico (evento_id, politico_id),
  UNIQUE KEY uq_eventi_org_giornale (evento_id, giornale_id),
  CONSTRAINT fk_eventi_org_evento   FOREIGN KEY (evento_id)   REFERENCES eventi (id) ON DELETE CASCADE,
  CONSTRAINT fk_eventi_org_politico FOREIGN KEY (politico_id) REFERENCES politici (id),
  CONSTRAINT fk_eventi_org_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id),
  CONSTRAINT ck_eventi_org_uno CHECK ((politico_id IS NOT NULL) + (giornale_id IS NOT NULL) = 1)
) ENGINE=InnoDB;

CREATE TABLE eventi_invitati (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id       BIGINT UNSIGNED NOT NULL,
  politico_id     BIGINT UNSIGNED NULL,
  giornalista_id  BIGINT UNSIGNED NULL,
  stato           ENUM('in_attesa','accettato','rifiutato') NOT NULL DEFAULT 'in_attesa',
  invitato_il     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  risposto_il     DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eventi_inv_politico (evento_id, politico_id),
  UNIQUE KEY uq_eventi_inv_giornalista (evento_id, giornalista_id),
  CONSTRAINT fk_eventi_inv_evento      FOREIGN KEY (evento_id)      REFERENCES eventi (id) ON DELETE CASCADE,
  CONSTRAINT fk_eventi_inv_politico    FOREIGN KEY (politico_id)    REFERENCES politici (id),
  CONSTRAINT fk_eventi_inv_giornalista FOREIGN KEY (giornalista_id) REFERENCES giornalisti (id),
  CONSTRAINT ck_eventi_inv_uno CHECK ((politico_id IS NOT NULL) + (giornalista_id IS NOT NULL) = 1)
) ENGINE=InnoDB;

CREATE TABLE eventi_partecipanti (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  evento_id       BIGINT UNSIGNED NOT NULL,
  politico_id     BIGINT UNSIGNED NULL,
  giornalista_id  BIGINT UNSIGNED NULL,
  user_id         BIGINT UNSIGNED NULL,
  creato_il       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eventi_part_politico (evento_id, politico_id),
  UNIQUE KEY uq_eventi_part_giornalista (evento_id, giornalista_id),
  UNIQUE KEY uq_eventi_part_user (evento_id, user_id),
  CONSTRAINT fk_eventi_part_evento      FOREIGN KEY (evento_id)      REFERENCES eventi (id) ON DELETE CASCADE,
  CONSTRAINT fk_eventi_part_politico    FOREIGN KEY (politico_id)    REFERENCES politici (id),
  CONSTRAINT fk_eventi_part_giornalista FOREIGN KEY (giornalista_id) REFERENCES giornalisti (id),
  CONSTRAINT fk_eventi_part_user        FOREIGN KEY (user_id)        REFERENCES `user` (id),
  CONSTRAINT ck_eventi_part_uno CHECK ((politico_id IS NOT NULL) + (giornalista_id IS NOT NULL) + (user_id IS NOT NULL) = 1)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabelle usate dai front end di wolfpress-php
-- ---------------------------------------------------------------------
-- Partiti mostrati nella sezione Politica del sito (scheda completa in JSON).
CREATE TABLE partiti (
  slug   VARCHAR(160) NOT NULL,
  nome   VARCHAR(160) NOT NULL,
  dati   LONGTEXT NULL,
  PRIMARY KEY (slug)
) ENGINE=InnoDB;

-- Chat di redazione di ogni giornale, divisa per canale.
CREATE TABLE messaggi_redazione (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  giornale_id  BIGINT UNSIGNED NOT NULL,
  canale       VARCHAR(80) NOT NULL,
  autore_id    BIGINT UNSIGNED NULL,
  autore       VARCHAR(160) NOT NULL,
  testo        TEXT NOT NULL,
  creato_il    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_messaggi_redazione (giornale_id, canale, creato_il),
  CONSTRAINT fk_messaggi_redazione_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id) ON DELETE CASCADE,
  CONSTRAINT fk_messaggi_redazione_autore   FOREIGN KEY (autore_id)   REFERENCES generalita (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Letture degli articoli, giorno per giorno, per il grafico dell'area cliente.
CREATE TABLE letture_giornaliere (
  giornale_id  BIGINT UNSIGNED NOT NULL,
  giorno       DATE NOT NULL,
  letture      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (giornale_id, giorno),
  CONSTRAINT fk_letture_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabelle di servizio per la sicurezza
-- ---------------------------------------------------------------------
-- Token monouso inviati per email (verifica indirizzo, reimposta password).
CREATE TABLE token_email (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  generalita_id  BIGINT UNSIGNED NOT NULL,
  tipo           ENUM('verifica','reset') NOT NULL,
  token_hash     CHAR(64) NOT NULL,
  scade_il       DATETIME NOT NULL,
  usato_il       DATETIME NULL,
  creato_il      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_token_email_hash (token_hash),
  KEY ix_token_email_generalita (generalita_id, tipo),
  CONSTRAINT fk_token_email_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Limite ai tentativi (accesso, registrazione, reset) per indirizzo IP.
-- `chiave` è un HMAC di azione + IP: l'IP in chiaro non viene salvato.
CREATE TABLE tentativi (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  chiave     CHAR(64) NOT NULL,
  creato_il  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_tentativi_chiave (chiave, creato_il)
) ENGINE=InnoDB;

-- =====================================================================
-- ATLANTE CIVICO: clienti, siti autonomi e fascicoli documentali
-- =====================================================================
-- Modelli MASTER riutilizzabili per candidato, partito, lista civica,
-- comitato e altri committenti. Un documento generato è un'ISTANZA
-- distinta del modello, con versione, controlli, firme ed evidenze.
-- NON costituisce una certificazione di conformità GDPR / AI Act.
--
-- REGOLE APPLICATIVE OBBLIGATORIE (non garantite dal solo DDL):
-- * autorizzazioni tenant-aware: ogni SELECT/UPDATE/DELETE verifica
--   cliente_id, sito_id, ruolo e mandato dell'utente autenticato;
-- * il rapporto candidato/partito/committente non dà accesso automatico
--   ai dati personali degli altri soggetti;
-- * approvazione, firma, verifica legale e DPIA richiedono prove reali;
-- * cifrare i valori riservati nell'applicazione, con chiavi fuori dal DB;
--   NON salvare liste di elettori, documenti firmati o firme in chiaro;
-- * i percorsi storage_rif sono identificativi PRIVATI, non URL pubblici;
-- * non usare dati di navigazione/iscrizione per profilazione politica
--   senza le pertinenti verifiche e condizioni di liceità;
-- * prevedere procedure reali di retention, export, cancellazione,
--   backup, segregazione ambienti e audit accessi ai fascicoli.
--
-- NOTA: le colonne usate in un CHECK (autorizzato_da, approvato_da,
-- verificata_da, validata_da) non possono avere una chiave esterna con
-- ON DELETE SET NULL: MySQL 8 e MariaDB rifiutano la tabella. Per questo
-- quelle quattro chiavi non hanno azione: chi ha autorizzato, approvato o
-- validato qualcosa non si può cancellare finché resta quella prova.
-- =====================================================================

-- Anagrafica del COMMITTENTE (non è automaticamente un account di login).
-- Dati personali riservati cifrati lato applicazione; hash = HMAC con
-- chiave distinta per ricerca senza esporre codice fiscale / P. IVA.
CREATE TABLE clienti_atlante (
  id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo                      ENUM('candidato','partito','lista_civica','comitato','agenzia','altro') NOT NULL,
  denominazione             VARCHAR(220) NOT NULL,
  codice_fiscale_cifrato    VARCHAR(600) NULL,
  codice_fiscale_hash       CHAR(64) NULL,
  partita_iva_cifrata       VARCHAR(600) NULL,
  partita_iva_hash          CHAR(64) NULL,
  pec_cifrata               VARCHAR(600) NULL,
  sede_cifrata              TEXT NULL,
  rappresentante_id         BIGINT UNSIGNED NULL,
  stato                     ENUM('bozza','da_verificare','verificato','sospeso','cessato') NOT NULL DEFAULT 'bozza',
  verificato_da             BIGINT UNSIGNED NULL,
  verificato_il             DATETIME NULL,
  creato_il                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_atlante_cliente_cf (codice_fiscale_hash),
  UNIQUE KEY uq_atlante_cliente_piva (partita_iva_hash),
  KEY ix_atlante_clienti_tipo (tipo, stato),
  CONSTRAINT fk_atlante_cliente_rapp FOREIGN KEY (rappresentante_id) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT fk_atlante_cliente_verificato FOREIGN KEY (verificato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT ck_atlante_cliente_verificato CHECK (stato <> 'verificato' OR verificato_il IS NOT NULL)
) ENGINE=InnoDB;

-- Un incaricato può essere referente di più clienti, con ruoli diversi.
-- generalita identifica la persona; non attribuisce di per sé permessi.
CREATE TABLE referenti_clienti_atlante (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id             BIGINT UNSIGNED NOT NULL,
  generalita_id          BIGINT UNSIGNED NOT NULL,
  ruolo                  ENUM('firmatario','comunicazione','privacy','ia','tecnico','amministrativo','altro') NOT NULL,
  mandato_evidenza_rif   VARCHAR(500) NULL,
  verificato_il          DATETIME NULL,
  revocato_il            DATETIME NULL,
  creato_il              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_referente_atlante (cliente_id, generalita_id, ruolo),
  KEY ix_referenti_atlante_persona (generalita_id),
  CONSTRAINT fk_referente_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_referente_atlante_persona FOREIGN KEY (generalita_id) REFERENCES generalita (id),
  CONSTRAINT ck_referente_atlante_date CHECK (revocato_il IS NULL OR revocato_il >= creato_il)
) ENGINE=InnoDB;

-- Il cliente che FIRMA può essere diverso dal candidato cui è dedicato
-- il sito. Eventuali mandati devono essere verificati e conservati.
CREATE TABLE siti_atlante (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id              BIGINT UNSIGNED NOT NULL,
  beneficiario_cliente_id BIGINT UNSIGNED NULL,
  politico_id             BIGINT UNSIGNED NULL,
  nome                    VARCHAR(220) NOT NULL,
  slug                    VARCHAR(160) NOT NULL,
  dominio                 VARCHAR(190) NULL,
  tipo_sito               ENUM('comunicazione_politica','testata','altro') NOT NULL DEFAULT 'comunicazione_politica',
  ambiente                ENUM('test','produzione') NOT NULL DEFAULT 'test',
  stato                   ENUM('bozza','collaudo','attivo','sospeso','cessato') NOT NULL DEFAULT 'bozza',
  attivato_il             DATETIME NULL,
  creato_il               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_siti_atlante_cliente_slug (cliente_id, slug),
  UNIQUE KEY uq_siti_atlante_dominio (dominio),
  UNIQUE KEY uq_siti_atlante_id_cliente (id, cliente_id),
  KEY ix_siti_atlante_beneficiario (beneficiario_cliente_id),
  CONSTRAINT fk_sito_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_sito_atlante_beneficiario FOREIGN KEY (beneficiario_cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_sito_atlante_politico FOREIGN KEY (politico_id) REFERENCES politici (id),
  CONSTRAINT ck_sito_atlante_attivo CHECK (stato <> 'attivo' OR (ambiente = 'produzione' AND attivato_il IS NOT NULL))
) ENGINE=InnoDB;

-- Funzioni facoltative: assenza della riga = NON ATTIVA.
-- Nessuna newsletter, donazione, pubblicità, targeting o IA per default.
CREATE TABLE funzionalita_siti_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sito_id               BIGINT UNSIGNED NOT NULL,
  codice                ENUM('cms','moduli','newsletter','donazioni','tesseramento','analytics','pubblicita_politica','targeting','ia','altro') NOT NULL,
  stato                 ENUM('non_attiva','richiesta','da_verificare','autorizzata','attiva','sospesa') NOT NULL DEFAULT 'non_attiva',
  verifica_evidenza_rif VARCHAR(500) NULL,
  autorizzato_da        BIGINT UNSIGNED NULL,
  attivato_il           DATETIME NULL,
  aggiornato_il         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_funzione_sito_atlante (sito_id, codice),
  CONSTRAINT fk_funzione_sito_atlante FOREIGN KEY (sito_id) REFERENCES siti_atlante (id),
  CONSTRAINT fk_funzione_autor_atlante FOREIGN KEY (autorizzato_da) REFERENCES generalita (id),
  CONSTRAINT ck_funzione_attiva_atlante CHECK (stato <> 'attiva' OR (autorizzato_da IS NOT NULL AND attivato_il IS NOT NULL))
) ENGINE=InnoDB;

-- Prezzi/periodicità dell'offerta concordata. Non sostituisce il contratto.
CREATE TABLE offerte_atlante (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id              BIGINT UNSIGNED NOT NULL,
  sito_id                 BIGINT UNSIGNED NULL,
  codice_offerta          VARCHAR(80) NOT NULL,
  attivazione_centesimi   INT UNSIGNED NOT NULL DEFAULT 0,
  canone_centesimi        INT UNSIGNED NOT NULL DEFAULT 0,
  periodicita             ENUM('una_tantum','mensile','annuale') NOT NULL,
  valuta                  CHAR(3) NOT NULL DEFAULT 'EUR',
  decorrenza              DATE NULL,
  termine                 DATE NULL,
  stato                   ENUM('bozza','inviata','accettata','rifiutata','scaduta') NOT NULL DEFAULT 'bozza',
  approvata_da            BIGINT UNSIGNED NULL,
  creato_il               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_offerta_atlante (cliente_id, codice_offerta),
  KEY ix_offerta_atlante_sito_cliente (sito_id, cliente_id),
  CONSTRAINT fk_offerta_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_offerta_atlante_sito_cliente FOREIGN KEY (sito_id, cliente_id) REFERENCES siti_atlante (id, cliente_id),
  CONSTRAINT fk_offerta_atlante_approvatore FOREIGN KEY (approvata_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT ck_offerta_atlante_date CHECK (termine IS NULL OR decorrenza IS NULL OR termine >= decorrenza)
) ENGINE=InnoDB;

-- Catalogo MASTER valido per tutti i clienti (nessuna copia per cliente).
CREATE TABLE modelli_documentali_master (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codice                 VARCHAR(80) NOT NULL,
  titolo                 VARCHAR(240) NOT NULL,
  categoria              ENUM('contratto','privacy','ai_act','pubblicita_politica','onboarding','commerciale','altro') NOT NULL,
  destinatario           ENUM('tutti','candidato','partito','lista_civica','comitato','agenzia','altro') NOT NULL DEFAULT 'tutti',
  condizione_utilizzo     VARCHAR(220) NULL,
  attivo                 TINYINT(1) NOT NULL DEFAULT 1,
  creato_il              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_modello_master_codice (codice)
) ENGINE=InnoDB;

-- Ogni versione del MASTER è autonoma: mai sovrascrivere una versione
-- usata da un cliente. file_rif identifica il modello in storage privato.
CREATE TABLE versioni_modelli_master (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  modello_id             BIGINT UNSIGNED NOT NULL,
  versione               VARCHAR(30) NOT NULL,
  stato                  ENUM('bozza','da_verificare','approvata','ritirata') NOT NULL DEFAULT 'bozza',
  file_rif               VARCHAR(500) NOT NULL,
  sha256                 CHAR(64) NOT NULL,
  variabili_json         LONGTEXT NULL,
  verificato_da          BIGINT UNSIGNED NULL,
  approvato_da           BIGINT UNSIGNED NULL,
  approvato_il           DATETIME NULL,
  creato_il              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_master_versione (modello_id, versione),
  KEY ix_versioni_master_stato (stato),
  CONSTRAINT fk_versione_master_modello FOREIGN KEY (modello_id) REFERENCES modelli_documentali_master (id),
  CONSTRAINT fk_versione_master_verifica FOREIGN KEY (verificato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT fk_versione_master_approva FOREIGN KEY (approvato_da) REFERENCES generalita (id),
  CONSTRAINT ck_master_variabili_json CHECK (variabili_json IS NULL OR JSON_VALID(variabili_json)),
  CONSTRAINT ck_master_approvazione CHECK (stato <> 'approvata' OR (approvato_da IS NOT NULL AND approvato_il IS NOT NULL))
) ENGINE=InnoDB;

-- Campi extra per personalizzazione, registrati UNA volta per cliente.
-- Usare valore_cifrato per informazioni riservate; valore_pubblico
-- SOLO per contenuti effettivamente pubblicabili. Nessun dato politico
-- degli elettori in questa tabella.
CREATE TABLE campi_clienti_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id            BIGINT UNSIGNED NOT NULL,
  chiave                 VARCHAR(120) NOT NULL,
  valore_pubblico        TEXT NULL,
  valore_cifrato         TEXT NULL,
  evidenza_rif           VARCHAR(500) NULL,
  verificato_da          BIGINT UNSIGNED NULL,
  verificato_il          DATETIME NULL,
  aggiornato_il          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_campo_cliente_atlante (cliente_id, chiave),
  CONSTRAINT fk_campo_cliente_atlante FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_campo_cliente_verificato FOREIGN KEY (verificato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT ck_campo_atlante_un_valore CHECK (valore_pubblico IS NULL OR valore_cifrato IS NULL)
) ENGINE=InnoDB;

-- Il fascicolo appartiene al cliente. Il vincolo composito impedisce
-- di attribuirgli accidentalmente il sito di un altro cliente.
CREATE TABLE fascicoli_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id            BIGINT UNSIGNED NOT NULL,
  sito_id               BIGINT UNSIGNED NULL,
  codice                VARCHAR(100) NOT NULL,
  titolo                VARCHAR(240) NOT NULL,
  stato                 ENUM('aperto','in_verifica','completo','archiviato') NOT NULL DEFAULT 'aperto',
  creato_da             BIGINT UNSIGNED NULL,
  creato_il             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fascicolo_atlante (cliente_id, codice),
  KEY ix_fascicolo_atlante_sito_cliente (sito_id, cliente_id),
  CONSTRAINT fk_fascicolo_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_fascicolo_atlante_sito_cliente FOREIGN KEY (sito_id, cliente_id) REFERENCES siti_atlante (id, cliente_id),
  CONSTRAINT fk_fascicolo_atlante_autore FOREIGN KEY (creato_da) REFERENCES generalita (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- I PDF/DOCX firmati e gli snapshot dei campi sono in storage privato,
-- cifrati quando necessari; nel DB solo riferimenti e impronte.
-- La firma non è prodotta né verificata automaticamente dal DDL.
CREATE TABLE documenti_atlante (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fascicolo_id           BIGINT UNSIGNED NOT NULL,
  versione_master_id     BIGINT UNSIGNED NOT NULL,
  revisione_cliente      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  stato                  ENUM('bozza','da_verificare','pronto_firma','firmato','annullato','scaduto') NOT NULL DEFAULT 'bozza',
  file_rif               VARCHAR(500) NULL,
  sha256                 CHAR(64) NULL,
  snapshot_cifrato_rif   VARCHAR(500) NULL,
  generato_da            BIGINT UNSIGNED NULL,
  verificato_da          BIGINT UNSIGNED NULL,
  verificato_il          DATETIME NULL,
  approvato_da           BIGINT UNSIGNED NULL,
  approvato_il           DATETIME NULL,
  scadenza_conservazione_il DATETIME NULL,
  creato_il              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aggiornato_il          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_documento_atlante_revisione (fascicolo_id, versione_master_id, revisione_cliente),
  KEY ix_documenti_atlante_stato (stato, scadenza_conservazione_il),
  CONSTRAINT fk_doc_atlante_fascicolo FOREIGN KEY (fascicolo_id) REFERENCES fascicoli_atlante (id),
  CONSTRAINT fk_doc_atlante_master FOREIGN KEY (versione_master_id) REFERENCES versioni_modelli_master (id),
  CONSTRAINT fk_doc_atlante_generatore FOREIGN KEY (generato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT fk_doc_atlante_verificatore FOREIGN KEY (verificato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT fk_doc_atlante_approvatore FOREIGN KEY (approvato_da) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT ck_doc_atlante_pronto CHECK (stato NOT IN ('pronto_firma','firmato') OR (file_rif IS NOT NULL AND sha256 IS NOT NULL AND verificato_il IS NOT NULL AND approvato_il IS NOT NULL))
) ENGINE=InnoDB;

-- Le firme sono evidenze distinte; un flag "firmato" non basta.
-- La validità della firma e i poteri del firmatario si verificano fuori DB.
CREATE TABLE firme_documenti_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  documento_id          BIGINT UNSIGNED NOT NULL,
  parte                 ENUM('cliente','fornitore','altro') NOT NULL,
  firmatario_id         BIGINT UNSIGNED NULL,
  firmato_il            DATETIME NULL,
  evidenza_rif          VARCHAR(500) NOT NULL,
  evidenza_sha256       CHAR(64) NOT NULL,
  verificata_da         BIGINT UNSIGNED NULL,
  verificata_il         DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_firme_documento_atlante (documento_id, parte),
  CONSTRAINT fk_firma_atlante_documento FOREIGN KEY (documento_id) REFERENCES documenti_atlante (id),
  CONSTRAINT fk_firma_atlante_firmatario FOREIGN KEY (firmatario_id) REFERENCES generalita (id) ON DELETE SET NULL,
  CONSTRAINT fk_firma_atlante_verificatore FOREIGN KEY (verificata_da) REFERENCES generalita (id),
  CONSTRAINT ck_firma_atlante_verificata CHECK (verificata_il IS NULL OR (firmato_il IS NOT NULL AND verificata_da IS NOT NULL))
) ENGINE=InnoDB;

-- Screening e verifiche effettive (GDPR, DPA, DPIA, cookie, AI Act,
-- pubblicità politica, sicurezza). Non impostare esiti falsamente.
CREATE TABLE verifiche_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id            BIGINT UNSIGNED NOT NULL,
  sito_id               BIGINT UNSIGNED NULL,
  documento_id          BIGINT UNSIGNED NULL,
  oggetto                ENUM('gdpr_ruoli','dpa','dpia','cookie','sicurezza','ai_act','pubblicita_politica','revisione_legale','altro') NOT NULL,
  esito                 ENUM('da_fare','integrazioni','conclusa','non_applicabile') NOT NULL DEFAULT 'da_fare',
  motivazione           TEXT NULL,
  evidenza_rif          VARCHAR(500) NULL,
  validata_da           BIGINT UNSIGNED NULL,
  validata_il           DATETIME NULL,
  creato_il             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_verifiche_atlante_cliente (cliente_id, oggetto, esito),
  KEY ix_verifiche_atlante_sito_cliente (sito_id, cliente_id),
  CONSTRAINT fk_verifica_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_verifica_atlante_sito_cliente FOREIGN KEY (sito_id, cliente_id) REFERENCES siti_atlante (id, cliente_id),
  CONSTRAINT fk_verifica_atlante_documento FOREIGN KEY (documento_id) REFERENCES documenti_atlante (id),
  CONSTRAINT fk_verifica_atlante_validatore FOREIGN KEY (validata_da) REFERENCES generalita (id),
  CONSTRAINT ck_verifica_atlante_chiusura CHECK (esito NOT IN ('conclusa','non_applicabile') OR (validata_da IS NOT NULL AND validata_il IS NOT NULL AND motivazione IS NOT NULL))
) ENGINE=InnoDB;

-- Audit di operazioni sui fascicoli: registrare solo metadati minimi,
-- niente PII o documenti in chiaro nei log. Solo inserimento applicativo:
-- non concedere UPDATE/DELETE di questa tabella all'utente DB del web.
CREATE TABLE audit_atlante (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id            BIGINT UNSIGNED NOT NULL,
  attore_id             BIGINT UNSIGNED NULL,
  oggetto               ENUM('cliente','referente','sito','funzionalita','offerta','modello','fascicolo','documento','firma','verifica') NOT NULL,
  oggetto_id            BIGINT UNSIGNED NOT NULL,
  azione                VARCHAR(80) NOT NULL,
  esito                 ENUM('riuscita','negata','errore') NOT NULL,
  creato_il             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_atlante_cliente_data (cliente_id, creato_il),
  CONSTRAINT fk_audit_atlante_cliente FOREIGN KEY (cliente_id) REFERENCES clienti_atlante (id),
  CONSTRAINT fk_audit_atlante_attore FOREIGN KEY (attore_id) REFERENCES generalita (id) ON DELETE SET NULL
) ENGINE=InnoDB;

