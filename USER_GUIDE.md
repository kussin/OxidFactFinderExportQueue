# KUSSIN | FACT Finder Export Queue User Guide

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

### KUSSIN | FACT Finder Export Queue - Monitor

Open **KUSSIN | FACT Finder Export Queue - Monitor** in the OXID admin. The grid shows at most 100 queue records per
page and supports sorting plus type-aware filters. Numeric values are intentionally not filterable,
except for `Stock`, which offers All, Yes (`Stock > 0`), and No (`Stock <= 0`);
`LASTSYNC` and `OXTIMESTAMP` are sort-only as well; boolean flags use Yes/No selectors. The counter
above the grid counts distinct article OXIDs whose
`LASTSYNC` and `OXTIMESTAMP` contain the OXID 6 zero-date marker and which satisfy the configured
`sWmdkFFExportOnlyActive`, `sWmdkFFExportHidden`, and `sWmdkFFExportStockMin` export settings. The
temporary 1000/1970 migration markers are recognized as well. The counter refreshes itself without
reloading the grid or the page. Configure the interval under **KUSSIN | FACT Finder Export Queue - Monitor** with
`sKussinFFMonitorRefreshInterval`; the available values are 5, 10, 15, 20, and 30 seconds, and the
default is 15 seconds.

The Estimated completion card shows an approximate finish time and remaining duration. Its
calculation uses the exact same distinct article OXID count as the waiting counter, so additional
channels, shops, and languages do not inflate either value. It divides that count by
`sWmdkFFQueueLimit`, assumes one queue run every two minutes, and uses the configured monitor refresh
interval.
Manually running the queue more frequently shortens the actual completion time accordingly.

On the initial page load and whenever no valid sorting is supplied, records are ordered by
`LASTSYNC DESC`.

Pressing Enter while focused on any filter field applies the current filter selection immediately.
This always invokes the regular filter action and never the adjacent CSV export or reset action.

The most recently applied filters are retained for the current admin session, including when the
monitor is reopened. Use Clear filters to delete the stored filters and return to the unfiltered
first page.

The monitor uses the same visual language as the other Kussin admin modules. A loading overlay is
shown immediately while filters, sorting, pagination, CSV export, filter clearing, or a manual reset
is being processed. During a CSV download it disappears automatically after the download has been
triggered, because the current page itself is not reloaded.

The grid shows `FlourMSRP` instead of `FlourSaleAmount`. Like the flour export, this calculated
sort-only column uses `MSRP` when it is greater than zero and falls back to `Price` otherwise. The
CSV still exports every physical database field, including `FlourSaleAmount`.

The final column is named Preview and opens the PDP in a new browser tab. Relative queue deeplinks
are resolved against the current storefront URL, for example `elvine/product.html` becomes
`https://dev1.warehouse-one.de/elvine/product.html` in the development shop.

Click an `OXID` value to open the corresponding article directly on the OXID article master-data tab
in a new browser tab. The existing monitor remains open. This link requires the Composer dependency
and active OXID module `kussin/oxid-base`, which supplies the shared authenticated admin shell.

The article master-data tab's bottom Actions bar (`body > div.actions`) contains an **Open in FACT
Finder Monitor** link between the new-article and article-preview actions. Like the preview action, it
is shown only when an article is selected. It opens the monitor in a new browser tab with its OXID
filter set to the complete article family. The new tab uses an explicit authenticated admin URL with
the current challenge token and admin session; it does not reuse the article frame's
`admin_start`/`#kussinshare` address. The parent and every variant are shown regardless of which family
member was open. The OXID filter also accepts several complete 32-character OXIDs separated by commas,
whitespace, semicolons, pipes, or SQL-like `OR` notation.

The CSV button exports all database fields and every record matching the active filters, not only the
visible page. Selecting one or more rows and using the reset button resets the complete selected
article OXIDs. All queue records for those OXIDs are re-queued across channels, shops, and languages.
The reset uses the OXID 6 values `LASTSYNC = 0` and `OXTIMESTAMP = 0`. The narrowly scoped queue
write uses `UPDATE IGNORE`, allowing MariaDB/MySQL to store both as `0000-00-00 00:00:00` even when
the connection enables strict or `NO_ZERO_DATE` modes.

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

Check article OXIDs waiting for processing (the shown active, hidden, and stock values are the module
defaults and must match the current export settings):

```sql
SELECT COUNT(*)
FROM wmdk_ff_export_queue
WHERE CAST(LASTSYNC AS CHAR) = '0000-00-00 00:00:00'
  AND OXACTIVE = 1
  AND OXHIDDEN = 0
  AND CAST(OXTIMESTAMP AS CHAR) = '0000-00-00 00:00:00'
  AND Stock >= 1
GROUP BY OXID;
```

### FACT Finder Export

```bash
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_en --shop-id=1 --lang=1 --cron
```

Creates the FACT Finder product export for the selected channel, shop, and language.

### Spotler/Sooqr Export

```bash
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=wh1_live_de --shop-id=1 --lang=0 --cron
```

Creates the Spotler/Sooqr export for the selected channel, shop, and language.

### Doofinder Export

```bash
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=wh1_live_de --shop-id=1 --lang=0 --cron
```

Creates the Doofinder export for the selected channel, shop, and language.

### flour POS Export

```bash
vendor/bin/oe-console wmdkffexport:export:flour --channel=wh1_live_de --shop-id=1 --lang=0 --cron
```

Creates the flour POS export.

Optional:

```bash
vendor/bin/oe-console wmdkffexport:export:flour --channel=wh1_live_de --shop-id=1 --lang=0 --flour-id=1 --cron
```

### flour POS Queue Synchronization

Preview queue differences without writing data:

```bash
vendor/bin/oe-console wmdkffexport:maintenance:sync-flour --dry-run
```

Synchronize the queue:

```bash
vendor/bin/oe-console wmdkffexport:maintenance:sync-flour
```

The command copies the flour ID, active flag, warehouse price, and short URL from `oxarticles`.
`FlourSaleAmount` is calculated as the OXID 6 runtime discount percentage:

```text
100 - (WMDKFLOURWAREHOUSEPRICE * 100 / reference price)
```

The reference price is `OXTPRICE`, with `OXPRICE` as the fallback. Only rows whose flour values
differ are updated. Updated rows receive the OXID 6 unsynchronized markers `LASTSYNC = 0` and
`OXTIMESTAMP = 0` through an `UPDATE IGNORE` statement.

Example output:

```json
{
  "success": true,
  "command": "wmdkffexport:maintenance:sync-flour",
  "dry_run": false,
  "matched_records": 1200,
  "changed_records": 42,
  "updated_records": 42,
  "lastsync": "0000-00-00 00:00:00",
  "oxtimestamp": "0000-00-00 00:00:00"
}
```

### Trusted Shops Import

```bash
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=wh1_live_de --cron
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
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_en --shop-id=1 --lang=1 --cron
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

The cron-oriented browser URLs such as `index.php?cl=wmdkffexport_queue` are compatibility shims for schedules carried over from OXID 6.

Do not use them for new cron definitions. Use OXID console commands instead.

Worker Mode still needs the article-family reset endpoint:

```text
index.php?cl=wmdkffexport_ajax&job=reset&oxid=<article-oxid>
```

The `oxid` may identify a parent or a variant. The endpoint finds the related queue rows and resets their synchronization timestamps so that the regular queue process rebuilds them. It does not export to FACT Finder immediately. Like the OXID 6 endpoint, it does not require an OXID storefront session, so Worker Mode and operational cURL clients can use the same URL. Protect non-public environments at the web-server or reverse-proxy layer, for example with HTTP Basic authentication.

A successful response keeps the historical `reseted` property for client compatibility:

```json
{
  "success": true,
  "validation_errors": [],
  "system_errors": [],
  "reseted": ["6dc08f0bbfb24185de1f712dcee36337"]
}
```

An empty `reseted` list means that the supplied OXID has no matching row in `wmdk_ff_export_queue`. Invalid or missing parameters return HTTP 400; database failures return HTTP 500. Responses are not cacheable and are appended to the configured queue log.

## Migration Status

Some commands still execute migrated legacy view logic internally to preserve behavior during the first OXID 7.4 migration step.

This is intentional for migration comparability, but it is not the final architecture. The legacy bridge should be replaced by dedicated OXID 7 services before final cleanup.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25421 Pinneberg<br>
Germany

Phone: +49 (4101) 85868 - 0<br>
Email: info@kussin.eu

---

&copy; 2006-2026 [Kussin | eCommerce und Online-Marketing GmbH](https://www.kussin.de/). All rights reserved.
