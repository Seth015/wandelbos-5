CREATE DATABASE IF NOT EXISTS wandelbos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wandelbos;

CREATE TABLE IF NOT EXISTS Bezoeker (
  BezoekerID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Naam VARCHAR(100) NOT NULL,
  SmartphoneType VARCHAR(50) NOT NULL,
  PRIMARY KEY (BezoekerID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Locatie (
  LocatieID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Naam VARCHAR(100) NOT NULL,
  Adres VARCHAR(200) NOT NULL,
  Beschrijving VARCHAR(255) NOT NULL,
  PRIMARY KEY (LocatieID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS QRCode (
  QRCodeID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodeWaarde VARCHAR(255) NOT NULL,
  LocatieID INT UNSIGNED NOT NULL,
  PRIMARY KEY (QRCodeID),
  UNIQUE KEY uq_qrcode_codewaarde (CodeWaarde),
  KEY idx_qrcode_locatie (LocatieID),
  CONSTRAINT fk_qrcode_locatie
    FOREIGN KEY (LocatieID)
    REFERENCES Locatie (LocatieID)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Informatiepagina (
  PaginaID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Titel VARCHAR(150) NOT NULL,
  Tekstinhoud TEXT NOT NULL,
  LocatieID INT UNSIGNED NOT NULL,
  PRIMARY KEY (PaginaID),
  KEY idx_informatiepagina_locatie (LocatieID),
  CONSTRAINT fk_informatiepagina_locatie
    FOREIGN KEY (LocatieID)
    REFERENCES Locatie (LocatieID)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Panoramabeeld (
  BeeldID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  URL VARCHAR(255) NOT NULL,
  Beschrijving VARCHAR(255) NOT NULL,
  Interactief BIT NOT NULL,
  LocatieID INT UNSIGNED NOT NULL,
  PRIMARY KEY (BeeldID),
  KEY idx_panoramabeeld_locatie (LocatieID),
  CONSTRAINT fk_panoramabeeld_locatie
    FOREIGN KEY (LocatieID)
    REFERENCES Locatie (LocatieID)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ScanLog (
  ScanID INT UNSIGNED NOT NULL AUTO_INCREMENT,
  BezoekerID INT UNSIGNED NOT NULL,
  `Timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  QRCodeID INT UNSIGNED NOT NULL,
  PRIMARY KEY (ScanID),
  KEY idx_scanlog_bezoeker (BezoekerID),
  KEY idx_scanlog_qrcode (QRCodeID),
  CONSTRAINT fk_scanlog_bezoeker
    FOREIGN KEY (BezoekerID)
    REFERENCES Bezoeker (BezoekerID)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_scanlog_qrcode
    FOREIGN KEY (QRCodeID)
    REFERENCES QRCode (QRCodeID)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_blocks (
  id VARCHAR(100) NOT NULL,
  title VARCHAR(255) NULL,
  body TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO content_blocks (id, title, body) VALUES
  ('home_hero_title', 'Ontdek de Geschiedenis op een Interactieve Manier', 'Duik in het verleden met een meer interactieve leerervaring.'),
  ('home_hero_subtitle', NULL, 'Speel educatieve spellen en test je kennis met uitdagende quizzes over belangrijke historische gebeurtenissen.'),
  ('home_hero_cta_game', NULL, 'Speel het Spel'),
  ('home_hero_cta_quiz', NULL, 'Doe de Quiz'),
  ('home_origin_title', 'Hoe is dit project ontstaan?', NULL),
  ('home_origin_body', NULL, 'Dit project is ontstaan uit de behoefte om geschiedenis toegankelijker en boeiender te maken voor een breed publiek.'),
  ('home_why_title', 'Waarom dit project?', NULL),
  ('home_why_educatief_title', 'Educatief', NULL),
  ('home_why_educatief_body', NULL, 'Leer op een gestructureerde manier over belangrijke historische gebeurtenissen.'),
  ('home_why_interactief_title', 'Interactief', NULL),
  ('home_why_interactief_body', NULL, 'Geen passief lezen meer. Doe mee met spellen, quizzes en interactieve opdrachten.'),
  ('home_why_alle_leeftijden_title', 'Voor alle leeftijden', NULL),
  ('home_why_alle_leeftijden_body', NULL, 'Voor scholieren, docenten en iedereen die geïnteresseerd is in geschiedenis.'),
  ('home_footer_title', 'Geschiedenis Project', NULL),
  ('home_footer_body', NULL, 'Ontdek geschiedenis op een innovatieve en interactieve manier.'),
  ('home_footer_contact', 'Contact', 'info@geschiedenisproject.nl'),
  ('home_footer_address', 'Adres', 'Amsterdam, Nederland'),
  ('home_footer_phone', 'Telefoon', '+31 (0)20 123 4567'),
  ('home_footer_follow', 'Volg ons', 'Facebook · Instagram · Twitter')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  body = VALUES(body);
