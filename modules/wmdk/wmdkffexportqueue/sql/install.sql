SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wmdk_ff_export_queue`;
CREATE TABLE `wmdk_ff_export_queue` (
    `OXID` CHAR(32) NOT NULL COMMENT 'Article id' COLLATE 'latin1_general_ci',
    `Channel` ENUM('wh1_live_de','wh1_live_en','wh1_dev_de','wh1_dev_en','wh1_stage_de','wh1_stage_en') NOT NULL DEFAULT 'wh1_live_de' COMMENT 'FACT-Finder Channel' COLLATE 'utf8_general_ci',
    `OXSHOPID` INT(1) NOT NULL DEFAULT '1' COMMENT 'Store ID (oxarticles__oxshopid)',
    `LANG` ENUM('0','1') NOT NULL DEFAULT '0' COMMENT 'Exportsprache' COLLATE 'utf8_general_ci',
    `LASTSYNC` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ProcessIp` VARCHAR(55) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
    `OXACTIVE` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Status (oxarticles__oxactive)',
    `OXHIDDEN` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Sichtbarkeit (oxarticles__oxhidden)',
    `OXTIMESTAMP` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Letzter Aktualisierungszeitpunkt (oxarticles__oxtimestamp)',
    `ProductNumber` VARCHAR(255) NULL DEFAULT '' COMMENT 'Artikelnummer (SKU) (oxarticles__oxartnum)' COLLATE 'utf8_general_ci',
    `MasterProductNumber` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Elternartikel' COLLATE 'utf8_general_ci',
    `Title` VARCHAR(255) NULL DEFAULT '' COMMENT 'Kombinierter Artikelname' COLLATE 'utf8_general_ci',
    `Short` VARCHAR(255) NULL DEFAULT '' COMMENT 'Kurzbeschreibung (oxarticles__oxshortdesc)' COLLATE 'utf8_general_ci',
    `HasProductImage` VARCHAR(1) NOT NULL DEFAULT '' COMMENT 'Boolischer Wert ob Produktbild' COLLATE 'utf8_general_ci',
    `ImageURL` VARCHAR(255) NULL DEFAULT '' COMMENT 'Produktbild in der Listenansicht' COLLATE 'utf8_general_ci',
    `SuggestPictureURL` VARCHAR(255) NULL DEFAULT '' COMMENT 'Produktbild in der Suggest-Suche' COLLATE 'utf8_general_ci',
    `HasFromPrice` VARCHAR(1) NOT NULL DEFAULT '' COMMENT 'Boolischer Wert ob von Preis' COLLATE 'utf8_general_ci',
    `Price` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Produktbeschreibung',
    `MSRP` DOUBLE NOT NULL DEFAULT '0' COMMENT 'UVP (oxarticles__oxtprice)',
    `BasePrice` VARCHAR(32) NOT NULL COMMENT 'Grundpreis' COLLATE 'utf8_general_ci',
    `Tax` DOUBLE NOT NULL DEFAULT '19' COMMENT 'VAT',
    `Stock` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Bestand (oxarticles__oxstock)',
    `Description` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
    `Deeplink` VARCHAR(255) NULL DEFAULT '' COMMENT 'Produktlink' COLLATE 'utf8_general_ci',
    `Marke` VARCHAR(255) NOT NULL DEFAULT '0' COMMENT 'Hersteller' COLLATE 'utf8_general_ci',
    `CategoryPath` TEXT NULL DEFAULT NULL COMMENT 'Kategoriepfade' COLLATE 'utf8_general_ci',
    `HasCustomAsnRestrictions` VARCHAR(64) NOT NULL DEFAULT '1' COMMENT 'Boolischer Wert oder ID ob ASN Filter ausgeblendet werden sollen' COLLATE 'utf8_general_ci',
    `Attributes` TEXT NULL DEFAULT NULL COMMENT 'ASN Attribute' COLLATE 'utf8_general_ci',
    `NumericalAttributes` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
    `SearchAttributes` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
    `SearchKeywords` TEXT NULL DEFAULT NULL COMMENT 'Benutzerdefinierte Keywords (oxarticles__oxsearchkeys)' COLLATE 'utf8_general_ci',
    `EAN` VARCHAR(128) NULL DEFAULT '' COMMENT 'International Article Number (EAN)' COLLATE 'utf8_general_ci',
    `MPN` VARCHAR(16) NULL DEFAULT '' COMMENT 'Hersteller-Artikelnummer (oxarticles__oxmpn)' COLLATE 'utf8_general_ci',
    `DISTEAN` VARCHAR(128) NULL DEFAULT '' COMMENT 'Hersteller-EAN (oxarticles__oxdistean)' COLLATE 'utf8_general_ci',
    `Weight` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Gewicht (oxarticles__oxweight)',
    `Rating` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Durchschnittliche Bewertung (oxarticles_oxrating)',
    `RatingCnt` INT(11) NOT NULL DEFAULT '0' COMMENT 'Anzahl der Bewertungen (oxarticles__oxratingcnt)',
    `TrustedShopsRating` VARCHAR(4) NOT NULL DEFAULT '' COMMENT '#48290 Durchschnittliche Bewertung (TS JSON API)' COLLATE 'utf8_general_ci',
    `TrustedShopsRatingCnt` VARCHAR(6) NOT NULL DEFAULT '' COMMENT '#48290 Anzahl der Bewertungen (TS JSON API)' COLLATE 'utf8_general_ci',
    `TrustedShopsRatingPercentage` VARCHAR(3) NOT NULL DEFAULT '' COMMENT '#48290 HTML Markup des Sterne' COLLATE 'utf8_general_ci',
    `HasNewFlag` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel neu' COLLATE 'utf8_general_ci',
    `HasTopFlag` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel ein Topseller ist' COLLATE 'utf8_general_ci',
    `HasSaleFlag` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel rabattiert ist' COLLATE 'utf8_general_ci',
    `SaleAmount` VARCHAR(4) NOT NULL COMMENT 'Rabatt als Prozentsatz' COLLATE 'utf8_general_ci',
    `HasSaleOfTheDayFlag` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel Sale of the Day ist' COLLATE 'utf8_general_ci',
    `SaleOfTheDayDate` VARCHAR(10) NOT NULL COMMENT 'Datum des Sale of the Day' COLLATE 'utf8_general_ci',
    `HasKidsFlag` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob es ein Kinderartikel ist' COLLATE 'utf8_general_ci',
    `HasVariantsSizelist` VARCHAR(1) NOT NULL COMMENT 'Boolischer Wert ob Varianten existieren' COLLATE 'utf8_general_ci',
    `VariantsSizelistMarkup` TEXT NOT NULL COMMENT 'HTML Markup zur Darstellung der Variantengrößen' COLLATE 'utf8_general_ci',
    `FlourId` CHAR(32) NULL DEFAULT NULL COMMENT 'flour POS ID' COLLATE 'utf8_general_ci',
    `FlourActive` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'flour POS Active Flag',
    `FlourPrice` DOUBLE NOT NULL DEFAULT '0' COMMENT 'flour POS Preis',
    `FlourSaleAmount` VARCHAR(4) NOT NULL COMMENT 'flour POS Rabatt als Prozentsatz' COLLATE 'utf8_general_ci',
    `FlourShortUrl` VARCHAR(255) NULL DEFAULT '' COMMENT 'flour POS Short URL' COLLATE 'utf8_general_ci',
    `SoldAmount` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Anzahl der Verkäufe (oxarticles__oxsoldamount)',
    `DateInsert` DATE NOT NULL DEFAULT '0000-00-00' COMMENT 'Anlagedatum (oxarticles__oxinsert)',
    `DateModified` DATE NULL DEFAULT '0000-00-00',
    UNIQUE INDEX `MasterId` (`OXID`, `Channel`) USING BTREE,
    INDEX `OXID` (`OXID`) USING BTREE,
    INDEX `Channel` (`Channel`) USING BTREE,
    INDEX `OXSHOPID` (`OXSHOPID`) USING BTREE,
    INDEX `LANG` (`LANG`) USING BTREE,
    INDEX `ProductNumber` (`ProductNumber`) USING BTREE,
    INDEX `MasterProductNumber` (`MasterProductNumber`) USING BTREE,
    INDEX `LASTSYNC` (`LASTSYNC`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Articles information';

DROP TABLE IF EXISTS `wmdk_ff_export_queue_tmp_ts`;
CREATE TABLE IF NOT EXISTS `wmdk_ff_export_queue_tmp_ts` (
  `ProductNumber` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Artikelnummer (SKU) (oxarticles__oxartnum)',
  `TrustedShopsRating` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '#49535 Durchschnittliche Bewertung (TS JSON API)',
  `TrustedShopsRatingCnt` varchar(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '#49535 Anzahl der Bewertungen (TS JSON API)',
  `TrustedShopsRatingPercentage` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '#49535 HTML Markup des Sterne',
  `RelatedProductNumbers` text COLLATE utf8_unicode_ci COMMENT '#49535 WMDK Trusted Shops Related Products',
  `Added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '#49535 Erstellungszeitpunkt des Datensatzes',
  UNIQUE KEY `ProductNumber` (`ProductNumber`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='#49535 WMDK FF Queue Trusted Shops tmp. Import';

ALTER TABLE `oxarticles`
    ADD COLUMN `WMDK_FFQUEUE` ENUM('1','0') NOT NULL DEFAULT '0' COMMENT 'WMDK Flag if product is added to FF queue',
	ADD COLUMN `WMDKMODIFIED` DATE NULL DEFAULT '0000-00-00' COMMENT 'WMDK Date for product order in FF' AFTER `WMDK_FFQUEUE`,
	ADD INDEX `WMDK_FFQUEUE` (`WMDK_FFQUEUE`);

UPDATE IGNORE oxarticles SET OXTIMESTAMP = OXTIMESTAMP, WMDKMODIFIED = DATE_FORMAT(OXTIMESTAMP, '%Y-%m-%d') WHERE WMDKMODIFIED LIKE "0000-00-00";

SET FOREIGN_KEY_CHECKS = 1;