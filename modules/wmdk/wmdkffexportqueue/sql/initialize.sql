-- CONFIGURATION
SET @ffchannel = "demo_de";
SET @store = 1;
SET @lang = "0";

-- RESET QUEUE
TRUNCATE `wmdk_ff_export_queue`;
TRUNCATE `wmdk_ff_export_queue_tmp_ts`;
UPDATE IGNORE oxarticles SET `OXTIMESTAMP` = `OXTIMESTAMP`, `WMDK_FFQUEUE` = "0" WHERE `WMDK_FFQUEUE` NOT LIKE "0";

-- INSERT QUEUE
INSERT IGNORE INTO 
	`wmdk_ff_export_queue` (`OXID`, `Channel`, `OXSHOPID`, `LANG`, `LASTSYNC`, `ProcessIp`, `OXTIMESTAMP`)
SELECT 
	OXID, 
	@ffchannel AS `Channel`, 
	@store AS `OXSHOPID`, 
	@lang AS `LANG`,
	'0000-00-00 00:00:00' AS `LASTSYNC`,
	'initialize.sql' AS `ProcessIp`,
	'0000-00-00 00:00:00' AS `OXTIMESTAMP`
FROM 
	`oxarticles`
WHERE
	`WMDK_FFQUEUE` = "0";
	
-- UPDATE FACT FINDER QUEUE STATUS
UPDATE IGNORE oxarticles SET `OXTIMESTAMP` = `OXTIMESTAMP`, `WMDK_FFQUEUE` = "1" WHERE `WMDK_FFQUEUE` = "0";