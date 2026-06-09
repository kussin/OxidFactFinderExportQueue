<?php

namespace Wmdk\FactFinderQueue\Setup;

use OxidEsales\Eshop\Core\DatabaseProvider;

class DatabaseInstaller
{
    public function install(): void
    {
        $this->createQueueTable();
        $this->createTrustedShopsTempTable();
        $this->addArticleColumns();
        $this->addArticleIndexes();
    }

    private function createQueueTable(): void
    {
        $this->execute(<<<'SQL'
CREATE TABLE IF NOT EXISTS `wmdk_ff_export_queue` (
  `OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Article id',
  `Channel` enum('kussin_live_de','kussin_live_en','kussin_dev_de','kussin_dev_en','kussin_stage_de','kussin_stage_en') NOT NULL DEFAULT 'kussin_live_de' COMMENT 'FACT-Finder Channel',
  `OXSHOPID` int(1) NOT NULL DEFAULT '1' COMMENT 'Store ID (oxarticles__oxshopid)',
  `LANG` enum('0','1') NOT NULL DEFAULT '0' COMMENT 'Export language',
  `LASTSYNC` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ProcessIp` varchar(55) DEFAULT NULL,
  `OXACTIVE` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Article active state',
  `OXHIDDEN` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Article hidden state',
  `OXTIMESTAMP` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last article update timestamp',
  `ProductNumber` varchar(255) DEFAULT '' COMMENT 'Article number',
  `MasterProductNumber` varchar(255) NOT NULL DEFAULT '' COMMENT 'Parent article number',
  `Title` varchar(255) DEFAULT '' COMMENT 'Combined article name',
  `Short` varchar(255) DEFAULT '' COMMENT 'Short description',
  `HasProductImage` varchar(1) NOT NULL DEFAULT '' COMMENT 'Product image flag',
  `ImageURL` varchar(255) DEFAULT '' COMMENT 'List product image URL',
  `SuggestPictureURL` varchar(255) DEFAULT '' COMMENT 'Suggest product image URL',
  `HasFromPrice` varchar(1) NOT NULL DEFAULT '' COMMENT 'From price flag',
  `Price` double NOT NULL DEFAULT '0' COMMENT 'Product price',
  `FromPrice` double NOT NULL DEFAULT '0' COMMENT 'From price',
  `MSRP` double NOT NULL DEFAULT '0' COMMENT 'Manufacturer suggested retail price',
  `BasePrice` varchar(32) NOT NULL COMMENT 'Base price',
  `Tax` double NOT NULL DEFAULT '19' COMMENT 'VAT',
  `Stock` double NOT NULL DEFAULT '0' COMMENT 'Stock',
  `Description` text,
  `Deeplink` varchar(255) DEFAULT '' COMMENT 'Product link',
  `Marke` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Manufacturer',
  `CategoryPath` text COMMENT 'Category paths',
  `HasCustomAsnRestrictions` varchar(64) NOT NULL DEFAULT '1' COMMENT 'ASN filter restriction flag or id',
  `Attributes` text COMMENT 'ASN attributes',
  `ClonedAttributes` text COMMENT 'Cleaned, mapped and combined ASN attributes',
  `NumericalAttributes` text,
  `SearchAttributes` text,
  `SearchKeywords` text COMMENT 'Custom search keywords',
  `EAN` varchar(128) DEFAULT '' COMMENT 'International article number',
  `MPN` varchar(16) DEFAULT '' COMMENT 'Manufacturer part number',
  `DISTEAN` varchar(128) DEFAULT '' COMMENT 'Distributor EAN',
  `Weight` double NOT NULL DEFAULT '0' COMMENT 'Weight',
  `Rating` double NOT NULL DEFAULT '0' COMMENT 'Average rating',
  `RatingCnt` int(11) NOT NULL DEFAULT '0' COMMENT 'Rating count',
  `TrustedShopsRating` varchar(4) NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating',
  `TrustedShopsRatingCnt` varchar(6) NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating count',
  `TrustedShopsRatingPercentage` varchar(3) NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating percentage',
  `HasNewFlag` varchar(1) NOT NULL COMMENT 'New article flag',
  `HasUsedFlag` varchar(1) NOT NULL COMMENT 'Used article flag',
  `HasTopFlag` varchar(1) NOT NULL COMMENT 'Top seller flag',
  `HasSaleFlag` varchar(1) NOT NULL COMMENT 'Sale flag',
  `SaleAmount` varchar(4) NOT NULL COMMENT 'Sale percentage',
  `HasSaleOfTheDayFlag` varchar(1) NOT NULL COMMENT 'Sale of the day flag',
  `SaleOfTheDayDate` varchar(10) NOT NULL COMMENT 'Sale of the day date',
  `HasKidsFlag` varchar(1) NOT NULL COMMENT 'Kids article flag',
  `HasVariantsSizelist` varchar(1) NOT NULL COMMENT 'Variant size list flag',
  `VariantsSizelistMarkup` text NOT NULL COMMENT 'Variant size list HTML markup',
  `Season` varchar(10) DEFAULT NULL COMMENT 'Season',
  `FlourId` char(32) DEFAULT NULL COMMENT 'flour POS ID',
  `FlourActive` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'flour POS active flag',
  `FlourPrice` double NOT NULL DEFAULT '0' COMMENT 'flour POS price',
  `FlourSaleAmount` varchar(4) NOT NULL COMMENT 'flour POS sale percentage',
  `FlourShortUrl` varchar(255) DEFAULT '' COMMENT 'flour POS short URL',
  `SoldAmount` double NOT NULL DEFAULT '0' COMMENT 'Sold amount',
  `DateInsert` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Insert date',
  `DateModified` date DEFAULT '0000-00-00',
  UNIQUE KEY `MasterId` (`OXID`,`Channel`),
  KEY `OXID` (`OXID`),
  KEY `Channel` (`Channel`),
  KEY `OXSHOPID` (`OXSHOPID`),
  KEY `LANG` (`LANG`),
  KEY `ProductNumber` (`ProductNumber`),
  KEY `MasterProductNumber` (`MasterProductNumber`),
  KEY `LASTSYNC` (`LASTSYNC`),
  KEY `EAN` (`EAN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Articles information'
SQL);
    }

    private function createTrustedShopsTempTable(): void
    {
        $this->execute(<<<'SQL'
CREATE TABLE IF NOT EXISTS `wmdk_ff_export_queue_tmp_ts` (
  `ProductNumber` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Article number',
  `TrustedShopsRating` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating',
  `TrustedShopsRatingCnt` varchar(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating count',
  `TrustedShopsRatingPercentage` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Trusted Shops rating percentage',
  `RelatedProductNumbers` text COLLATE utf8_unicode_ci COMMENT 'Trusted Shops related products',
  `Added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  UNIQUE KEY `ProductNumber` (`ProductNumber`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='WMDK FF Queue Trusted Shops tmp import'
SQL);
    }

    private function addArticleColumns(): void
    {
        $columns = [
            'WMDKVARSELECTMAPPING' => "VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'WMDK variants mapping value'",
            'WMDKVARSELECTMAPPING_1' => "VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'WMDK variants mapping value #1'",
            'WMDKVARSELECTMAPPING_2' => "VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'WMDK variants mapping value #2'",
            'WMDKVARSELECTMAPPING_3' => "VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'WMDK variants mapping value #3'",
            'WMDK_FFQUEUE' => "ENUM('1','0') NOT NULL DEFAULT '0' COMMENT 'WMDK flag if product is added to FF queue'",
            'WMDKMODIFIED' => "DATE NULL DEFAULT '0000-00-00' COMMENT 'WMDK date for product order in FF'",
            'WMDKTRUSTEDSHOPSRELATEDPRODUCTS' => "TEXT NULL COMMENT 'Trusted Shops related product numbers'",
        ];

        foreach ($columns as $columnName => $definition) {
            if (!$this->columnExists('oxarticles', $columnName)) {
                $this->execute(sprintf('ALTER TABLE `oxarticles` ADD COLUMN `%s` %s', $columnName, $definition));
            }
        }
    }

    private function addArticleIndexes(): void
    {
        if (!$this->indexExists('oxarticles', 'WMDK_FFQUEUE')) {
            $this->execute('ALTER TABLE `oxarticles` ADD INDEX `WMDK_FFQUEUE` (`WMDK_FFQUEUE`)');
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = DatabaseProvider::getDb(false)->select(
            sprintf('SHOW COLUMNS FROM `%s` LIKE %s', $tableName, DatabaseProvider::getDb()->quote($columnName))
        );

        return $result !== false && $result->count() > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = DatabaseProvider::getDb(false)->select(
            sprintf('SHOW INDEX FROM `%s` WHERE `Key_name` = %s', $tableName, DatabaseProvider::getDb()->quote($indexName))
        );

        return $result !== false && $result->count() > 0;
    }

    private function execute(string $sql): void
    {
        DatabaseProvider::getDb()->execute($sql);
    }
}
