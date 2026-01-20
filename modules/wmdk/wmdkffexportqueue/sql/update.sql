-- Turn off foreign key check
SET FOREIGN_KEY_CHECKS = 0;

-- Update data OXARTICLES
ALTER TABLE `wmdk_ff_export_queue`
	ADD INDEX `Stock` (`Stock`),
	ADD INDEX `idx_queue_sync` (`OXACTIVE`, `Stock`, `LASTSYNC`);

-- Update data OXARTICLES
UPDATE
	oxarticles a,
	wmdk_ff_export_queue b
SET
	a.WMDK_FFQUEUE = "1",
	a.OXTIMESTAMP = a.OXTIMESTAMP
WHERE
	(a.OXID = b.OXID)
	AND (
		(b.OXSHOPID = 1)
		AND (b.LANG = "0")
	);

-- Turn on foreign key check
SET FOREIGN_KEY_CHECKS = 1;
