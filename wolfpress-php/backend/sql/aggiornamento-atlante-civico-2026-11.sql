-- Migrazione additiva Atlante Civico da applicare al DB WolfPress ESISTENTE.
-- Prima di eseguire: backup verificato e prova su staging.
-- Eseguire UNA volta; CREATE TABLE IF NOT EXISTS non controlla strutture divergenti.
-- Non modifica le iscrizioni storiche: nessun valore esistente prova un consenso.
USE wolfpress;

ALTER TABLE iscrizioni MODIFY COLUMN newsletter TINYINT(1) NOT NULL DEFAULT 0;

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
CREATE TABLE IF NOT EXISTS clienti_atlante (
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
CREATE TABLE IF NOT EXISTS referenti_clienti_atlante (
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
CREATE TABLE IF NOT EXISTS siti_atlante (
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
CREATE TABLE IF NOT EXISTS funzionalita_siti_atlante (
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
CREATE TABLE IF NOT EXISTS offerte_atlante (
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
CREATE TABLE IF NOT EXISTS modelli_documentali_master (
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
CREATE TABLE IF NOT EXISTS versioni_modelli_master (
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
CREATE TABLE IF NOT EXISTS campi_clienti_atlante (
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
CREATE TABLE IF NOT EXISTS fascicoli_atlante (
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
CREATE TABLE IF NOT EXISTS documenti_atlante (
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
CREATE TABLE IF NOT EXISTS firme_documenti_atlante (
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
CREATE TABLE IF NOT EXISTS verifiche_atlante (
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
CREATE TABLE IF NOT EXISTS audit_atlante (
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

