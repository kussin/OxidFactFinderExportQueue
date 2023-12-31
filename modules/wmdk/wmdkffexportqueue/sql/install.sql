SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wmdk_ff_export_queue`;
CREATE TABLE IF NOT EXISTS `wmdk_ff_export_queue` (
  `OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Article id',
  `Channel` enum('demo_de') NOT NULL DEFAULT 'demo_de' COMMENT 'FACT-Finder Channel',
  `OXSHOPID` int(1) NOT NULL DEFAULT '1' COMMENT 'Store ID (oxarticles__oxshopid)',
  `LANG` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'Exportsprache',
  `LASTSYNC` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ProcessIp` varchar(55) DEFAULT NULL,
  `OXACTIVE` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status (oxarticles__oxactive)',
  `OXHIDDEN` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Sichtbarkeit (oxarticles__oxhidden)',
  `OXTIMESTAMP` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Letzter Aktualisierungszeitpunkt (oxarticles__oxtimestamp)',
  `ProductNumber` varchar(255) DEFAULT '' COMMENT 'Artikelnummer (SKU) (oxarticles__oxartnum)',
  `MasterProductNumber` varchar(255) NOT NULL DEFAULT '' COMMENT 'Elternartikel',
  `Title` varchar(255) DEFAULT '' COMMENT 'Kombinierter Artikelname',
  `Short` varchar(255) DEFAULT '' COMMENT 'Kurzbeschreibung (oxarticles__oxshortdesc)',
  `HasProductImage` varchar(1) NOT NULL DEFAULT '' COMMENT 'Boolischer Wert ob Produktbild',
  `ImageURL` varchar(255) DEFAULT '' COMMENT 'Produktbild in der Listenansicht',
  `SuggestPictureURL` varchar(255) DEFAULT '' COMMENT 'Produktbild in der Suggest-Suche',
  `HasFromPrice` varchar(1) NOT NULL DEFAULT '' COMMENT 'Boolischer Wert ob von Preis',
  `Price` double NOT NULL DEFAULT '0' COMMENT 'Produktbeschreibung',
  `MSRP` double NOT NULL DEFAULT '0' COMMENT 'UVP (oxarticles__oxtprice)',
  `BasePrice` varchar(32) NOT NULL COMMENT 'Grundpreis',
  `Stock` double NOT NULL DEFAULT '0' COMMENT 'Bestand (oxarticles__oxstock)',
  `Description` text,
  `Deeplink` varchar(255) DEFAULT '' COMMENT 'Produktlink',
  `Marke` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Hersteller',
  `CategoryPath` text COMMENT 'Kategoriepfade',
  `HasCustomAsnRestrictions` varchar(64) NOT NULL DEFAULT '1' COMMENT 'Boolischer Wert oder ID ob ASN Filter ausgeblendet werden sollen',
  `Attributes` text COMMENT 'ASN Attribute',
  `NumericalAttributes` text,
  `SearchAttributes` text,
  `SearchKeywords` text COMMENT 'Benutzerdefinierte Keywords (oxarticles__oxsearchkeys)',
  `EAN` varchar(128) DEFAULT '' COMMENT 'International Article Number (EAN)',
  `MPN` varchar(16) DEFAULT '' COMMENT 'Hersteller-Artikelnummer (oxarticles__oxmpn)',
  `DISTEAN` varchar(128) DEFAULT '' COMMENT 'Hersteller-EAN (oxarticles__oxdistean)',
  `Weight` double NOT NULL DEFAULT '0' COMMENT 'Gewicht (oxarticles__oxweight)',
  `Rating` double NOT NULL DEFAULT '0' COMMENT 'Durchschnittliche Bewertung (oxarticles_oxrating)',
  `RatingCnt` int(11) NOT NULL DEFAULT '0' COMMENT 'Anzahl der Bewertungen (oxarticles__oxratingcnt)',
  `TrustedShopsRating` varchar(4) NOT NULL DEFAULT '' COMMENT '#48290 Durchschnittliche Bewertung (TS JSON API)',
  `TrustedShopsRatingCnt` varchar(6) NOT NULL DEFAULT '' COMMENT '#48290 Anzahl der Bewertungen (TS JSON API)',
  `TrustedShopsRatingPercentage` varchar(3) NOT NULL DEFAULT '' COMMENT '#48290 HTML Markup des Sterne',
  `HasNewFlag` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel neu',
  `HasTopFlag` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel ein Topseller ist',
  `HasSaleFlag` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel rabattiert ist',
  `SaleAmount` varchar(4) NOT NULL COMMENT 'Rabatt als Prozentsatz',
  `HasSaleOfTheDayFlag` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob Artikel Sale of the Day ist',
  `SaleOfTheDayDate` varchar(10) NOT NULL COMMENT 'Datum des Sale of the Day',
  `HasKidsFlag` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob es ein Kinderartikel ist',
  `HasVariantsSizelist` varchar(1) NOT NULL COMMENT 'Boolischer Wert ob Varianten existieren',
  `VariantsSizelistMarkup` text NOT NULL COMMENT 'HTML Markup zur Darstellung der Variantengrößen',
  `SoldAmount` double NOT NULL DEFAULT '0' COMMENT 'Anzahl der Verkäufe (oxarticles__oxsoldamount)',
  `DateInsert` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Anlagedatum (oxarticles__oxinsert)',
  `DateModified` date DEFAULT '0000-00-00',
  UNIQUE KEY `MasterId` (`OXID`,`Channel`),
  KEY `OXID` (`OXID`),
  KEY `Channel` (`Channel`),
  KEY `OXSHOPID` (`OXSHOPID`),
  KEY `LANG` (`LANG`),
  KEY `ProductNumber` (`ProductNumber`),
  KEY `MasterProductNumber` (`MasterProductNumber`),
  KEY `LASTSYNC` (`LASTSYNC`)
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