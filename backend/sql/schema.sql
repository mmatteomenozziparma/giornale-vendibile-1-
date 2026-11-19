-- =====================================================================
-- WolfPress — schema del database (MySQL 8.0.16+ / MariaDB 10.4+)
-- Serve i due front end: portale (editori, giornalisti, politici,
-- tecnici) e sito pubblico (lettori = user).
--
-- Uso:  mysql -u root -p < backend/sql/schema.sql
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
  PRIMARY KEY (id),
  UNIQUE KEY uq_giornalisti_generalita (generalita_id),
  KEY ix_giornalisti_giornale (giornale_id),
  CONSTRAINT fk_giornalisti_generalita FOREIGN KEY (generalita_id) REFERENCES generalita (id) ON DELETE CASCADE,
  CONSTRAINT fk_giornalisti_giornale   FOREIGN KEY (giornale_id)   REFERENCES giornali (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ISCRIZIONI: uno user segue (gratis) un giornale oppure un politico.
-- ---------------------------------------------------------------------
CREATE TABLE iscrizioni (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  giornale_id  BIGINT UNSIGNED NULL,
  politico_id  BIGINT UNSIGNED NULL,
  newsletter   TINYINT(1) NOT NULL DEFAULT 1,
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
