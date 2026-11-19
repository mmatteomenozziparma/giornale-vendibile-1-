-- Da eseguire SOLO su un database creato con la prima versione di schema.sql
-- (quella senza le colonne per wolfpress-php). Un database nuovo usa direttamente schema.sql.
USE wolfpress;

ALTER TABLE politici
  ADD COLUMN livello ENUM('comune','regione','camera','senato','europa') NOT NULL DEFAULT 'comune',
  ADD COLUMN dati LONGTEXT NULL;
ALTER TABLE giornali
  ADD COLUMN dati LONGTEXT NULL,
  ADD COLUMN impostazioni LONGTEXT NULL;
ALTER TABLE giornalisti ADD COLUMN bio VARCHAR(500) NULL;
ALTER TABLE ticket
  ADD COLUMN mittente VARCHAR(120) NULL,
  ADD COLUMN video VARCHAR(300) NULL;
ALTER TABLE articoli_giornali
  ADD COLUMN firma VARCHAR(160) NULL,
  ADD COLUMN letture INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE partiti (
  slug   VARCHAR(160) NOT NULL,
  nome   VARCHAR(160) NOT NULL,
  dati   LONGTEXT NULL,
  PRIMARY KEY (slug)
) ENGINE=InnoDB;

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

-- Seconda parte (letture reali): eseguire solo se la tabella non esiste ancora.
-- Letture degli articoli, giorno per giorno, per il grafico dell'area cliente.
CREATE TABLE IF NOT EXISTS letture_giornaliere (
  giornale_id  BIGINT UNSIGNED NOT NULL,
  giorno       DATE NOT NULL,
  letture      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (giornale_id, giorno),
  CONSTRAINT fk_letture_giornale FOREIGN KEY (giornale_id) REFERENCES giornali (id) ON DELETE CASCADE
) ENGINE=InnoDB;
