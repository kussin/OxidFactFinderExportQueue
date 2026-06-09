# FACT Finder Export Queue User Guide

This guide explains the operational use of the `wmdk/wmdkffexportqueue` module in the OXID eShop PE 7.4 migration context.

The module prepares OXID article data for external product data exports. It currently supports:

- FACT Finder product data exports
- Spotler/Sooqr product data exports
- Doofinder product data exports
- flour POS product data exports
- Trusted Shops rating imports
- Queue cleanup and reset maintenance

## Important Concepts

The module does not export directly from `oxarticles`.

It uses the table `wmdk_ff_export_queue` as an intermediate product data queue. The general data flow is:

1. OXID articles are inserted or marked in `wmdk_ff_export_queue`.
2. The queue command updates queue rows from current OXID article data.
3. Export commands create CSV or XML files from queue rows.
4. Maintenance commands repair, add, reset, or disable queue rows.

## Run Commands

Run all commands from the OXID Composer project root:

```bash
cd html/source
```

On the server this is typically the deployment root that contains `vendor/bin/oe-console`.

For cron jobs, pass `--cron` explicitly:

```bash
vendor/bin/oe-console wmdkffexport:cron:queue --cron
```

The `--cron` option only marks the command response as a cron run. It does not change the business logic.

## Console Commands

### Database Installation

```bash
vendor/bin/oe-console wmdkffexport:install:db
```

Installs or updates required database structures:

- `wmdk_ff_export_queue`
- `wmdk_ff_export_queue_tmp_ts`
- required `oxarticles` extension columns

The command is designed to be idempotent. It should not drop existing queue data.

### Queue Processing

```bash
vendor/bin/oe-console wmdkffexport:cron:queue --cron
```

Processes pending queue rows and updates queue data from OXID article data.

Typical result:

```json
{
  "success": true,
  "queued_products": ["2100001234567"],
  "validation_errors": [],
  "system_errors": [],
  "timestamp": "2026-06-03 13:49:26",
  "cronjob": true
}
```

The command selects rows from `wmdk_ff_export_queue` and updates their export data if the OXID article data is newer than the queue row.

### Queue Reset

```bash
vendor/bin/oe-console wmdkffexport:cron:reset --cron
```

Repairs and resets queue rows based on several rules.

This command does not mean "reset all products". It only resets rows that match one of the built-in repair or change-detection rules.

#### What The Command Does

The reset command currently performs these checks:

- `reseted_products`: resets queue rows where the matching OXID article has a newer `OXTIMESTAMP` than the queue row and was changed within `sWmdkFFCronResetExistingArticlesSinceDays`.
- `reseted_variants`: resets variant rows when their parent article was changed and the command is running on configured weekdays and within the configured time window.
- `reseted_varname`: resets queue rows with malformed attribute data starting with `=`.
- `missing_products`: marks OXID articles with `WMDK_FFQUEUE = 0` if they are missing in the first configured FACT Finder channel.
- `added_products`: inserts missing `wmdk_ff_export_queue` rows for articles where `oxarticles.WMDK_FFQUEUE = 0`.
- `correct_parent_stock`: updates parent article variant stock totals in `oxarticles`.
- `update_status`: resets queue rows if article active status changed.
- `update_stock`: resets queue rows if article stock changed.
- `reset_variants`: resets variants whose parent article was modified recently.
- `reseted_siblings`: resets sibling variants when sibling updates are enabled.
- `fixed_nopic`: resets rows with placeholder image URLs during the configured time window.
- `disable_missing_oxids`: disables queue rows whose `OXID` no longer exists in `oxarticles`.

#### Example Response

```json
{
  "success": true,
  "reseted_products": 0,
  "reseted_variants": 0,
  "reseted_siblings": 0,
  "reseted_varname": 0,
  "missing_products": 0,
  "added_products": 150,
  "validation_errors": [],
  "system_errors": [],
  "correct_parent_stock": true,
  "update_status": true,
  "update_stock": true,
  "reset_variants": true,
  "disable_missing_oxids": true,
  "timestamp": "2026-06-03 17:15:15",
  "cronjob": true
}
```

#### How To Read This Response

`success: true` means the command completed without a fatal error.

`reseted_products: 0` does not necessarily indicate a problem. It means no queue rows matched the "article timestamp is newer than queue timestamp" rule during this run.

`added_products: 150` means the command inserted queue rows for missing products. With two configured channels, this usually means 75 OXID articles were added to the queue, because each article receives one row per configured channel.

Boolean values such as `update_status: true` mean that the repair step executed successfully. They do not report the number of changed rows.

#### Why No Products May Be Reset

It is normal that all `reseted_*` counters are `0` when:

- no articles were changed since the configured lookback period
- affected queue rows already have `OXTIMESTAMP = 0000-00-00 00:00:00`
- stock/status repair steps found no differences
- variant reset rules are outside their configured weekday or time window
- missing products were added instead of existing rows being reset

#### Relevant Settings

The reset command depends mainly on these module settings:

- `sWmdkFFGeneralChannelList`
- `sWmdkFFQueueResetLimit`
- `sWmdkFFCronResetExistingArticlesSinceDays`
- `sWmdkFFCronResetExistingVariantsDays`
- `sWmdkFFCronResetArticlesWithNoPicFrom`
- `sWmdkFFCronResetArticlesWithNoPicTo`
- `bWmdkFFQueueUpdateSiblings`

The active settings are stored in:

```text
var/configuration/shops/1/modules/wmdkffexportqueue.yaml
```

#### Useful SQL Checks

Check configured queue rows for one product number:

```sql
SELECT OXID, Channel, ProductNumber, MasterProductNumber, LASTSYNC, OXTIMESTAMP, ProcessIp, OXACTIVE, OXHIDDEN
FROM wmdk_ff_export_queue
WHERE ProductNumber = '2100003766335'
   OR MasterProductNumber = '2100003766335';
```

Check articles that are still marked as not added to the queue:

```sql
SELECT OXID, OXARTNUM, OXTITLE, WMDK_FFQUEUE
FROM oxarticles
WHERE WMDK_FFQUEUE = '0'
LIMIT 100;
```

Check queue rows waiting for processing:

```sql
SELECT OXID, Channel, ProductNumber, MasterProductNumber, LASTSYNC, OXTIMESTAMP, ProcessIp
FROM wmdk_ff_export_queue
WHERE LASTSYNC = '0000-00-00 00:00:00'
   OR OXTIMESTAMP = '0000-00-00 00:00:00'
LIMIT 100;
```

### FACT Finder Export

```bash
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=kussin_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=kussin_live_en --shop-id=1 --lang=1 --cron
```

Creates the FACT Finder product export for the selected channel, shop, and language.

### Spotler/Sooqr Export

```bash
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=kussin_live_de --shop-id=1 --lang=0 --cron
```

Creates the Spotler/Sooqr export for the selected channel, shop, and language.

### Doofinder Export

```bash
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=kussin_live_de --shop-id=1 --lang=0 --cron
```

Creates the Doofinder export for the selected channel, shop, and language.

### flour POS Export

```bash
vendor/bin/oe-console wmdkffexport:export:flour --channel=kussin_live_de --shop-id=1 --lang=0 --cron
```

Creates the flour POS export.

Optional:

```bash
vendor/bin/oe-console wmdkffexport:export:flour --channel=kussin_live_de --shop-id=1 --lang=0 --flour-id=1 --cron
```

### Trusted Shops Import

```bash
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=kussin_live_de --cron
```

Imports Trusted Shops product ratings into the queue.

### Cleanup Maintenance

```bash
vendor/bin/oe-console wmdkffexport:maintenance:cleanup
```

Removes orphaned queue records whose `OXID` no longer exists in `oxarticles`.

It also marks queue rows without `ProductNumber` as inactive and hidden:

- `OXACTIVE = 0`
- `OXHIDDEN = 1`
- `LASTSYNC = 0`
- `OXTIMESTAMP = 1`
- `ProcessIp = wmdkffexport:maintenance:cleanup - <timestamp>`

Affected orphaned IDs are written to:

```text
source/log/KUSSIN_FACTFINDER_CLEANUP.log
```

The log file path is controlled by the module setting `sWmdkFFDebugLogFileCleanup`. Relative paths are resolved below the OXID shop root, for example `log/KUSSIN_FACTFINDER_CLEANUP.log` resolves to `source/log/KUSSIN_FACTFINDER_CLEANUP.log`.

### Targeted Queue Reset

```bash
vendor/bin/oe-console wmdkffexport:maintenance:reset --lastsync-from="2026-06-03 08:30:00" --title-like="%T-Shirt%"
```

Resets selected queue rows by explicit filters. Use `--help` for all supported options:

```bash
vendor/bin/oe-console wmdkffexport:maintenance:reset --help
```

Use `--dry-run` before applying broad filters:

```bash
vendor/bin/oe-console wmdkffexport:maintenance:reset --lastsync-from="2026-06-03 08:30:00" --title-like="%T-Shirt%" --dry-run
```

## Suggested Cron Sequence

A typical operational sequence is:

```bash
vendor/bin/oe-console wmdkffexport:cron:reset --cron
vendor/bin/oe-console wmdkffexport:cron:queue --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=kussin_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=kussin_live_en --shop-id=1 --lang=1 --cron
```

Add third-party exports only if they are used in the current shop setup.

## Write Command Output To A File

Overwrite the same log file on every run:

```bash
vendor/bin/oe-console wmdkffexport:cron:queue --cron > 69002_console.log 2>&1
```

Append instead of overwrite:

```bash
vendor/bin/oe-console wmdkffexport:cron:queue --cron >> 69002_console.log 2>&1
```

## Legacy Browser Entry Points

The old browser URLs such as `index.php?cl=wmdkffexport_queue` are migration references only.

Do not use them for new cron definitions. Use OXID console commands instead.

## Migration Status

Some commands still execute migrated legacy view logic internally to preserve behavior during the first OXID 7.4 migration step.

This is intentional for migration comparability, but it is not the final architecture. The legacy bridge should be replaced by dedicated OXID 7 services before final cleanup.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25421 Pinneberg<br>
Germany

Phone: +49 (4101) 85868 - 0<br>
Email: info@kussin.de

---

&copy; 2006-2026 [Kussin | eCommerce und Online-Marketing GmbH](https://www.kussin.de/). All rights reserved.
