# FACT Finder Export Queue User Guide for OXID 6

This guide explains the operational use of `wmdk/wmdkffexportqueue` on the `dev` branch. The last tested shop version is OXID eShop 6.5.5. For OXID 6.0 through 6.2, use [`bwc/oxid60`](https://github.com/kussin/OxidFactFinderExportQueue/tree/bwc/oxid60). For OXID 7.4 and later 7.x releases, use [`update/69446_oxid7`](https://github.com/kussin/OxidFactFinderExportQueue/tree/update/69446_oxid7) and follow that branch's documentation.

## Purpose and Data Flow

The module prepares OXID article data for:

- FACT Finder CSV exports
- Spotler/Sooqr XML exports
- Doofinder XML and compressed XML exports
- flour POS CSV exports
- Trusted Shops product rating imports

It uses `wmdk_ff_export_queue` as an intermediate product table. A normal processing cycle is:

1. Reset or add queue records for changed and missing articles.
2. Process pending records and copy current OXID article data into the queue.
3. Create the required channel-specific export files.
4. Let the target system retrieve or import those files.

The queue limit means that one `queue` call may process only part of a large backlog. Run it regularly until the pending records have been processed.

## Installation and Deployment

Run Composer and OXID console commands from the OXID Composer project root.

### Database

Back up the database before executing any module SQL.

For an initial installation, review and adapt:

```text
source/modules/wmdk/wmdkffexportqueue/sql/install.sql
```

The file is intended to create:

- `wmdk_ff_export_queue`
- `wmdk_ff_export_queue_tmp_ts`
- required `oxarticles` extension columns and indexes

Important limitations:

- The script drops and recreates both queue tables and therefore deletes existing queue data.
- Its `Channel` enum contains fixed legacy channel names that must match the target configuration.
- Its `ALTER TABLE oxarticles` block must be reviewed against the current database before execution.
- It is an initial installation script, not an idempotent migration or update mechanism.

After changing the article table, refresh the OXID database views and clear the shop cache.

### Module and Runtime Files

Install the module configuration and apply it:

```bash
vendor/bin/oe-console oe:module:install-configuration source/modules/wmdk/wmdkffexportqueue/
vendor/bin/oe-console oe:module:apply-configuration
```

Ensure that:

- the repository's `export/` directory structure exists below `source/export/`
- `source/export/factfinder/productData/` is writable by both the web server and cron user
- `bin/wmdkffexport.php` is deployed as `source/bin/wmdkffexport.php`
- the module is active in the OXID admin

## Module Settings

Review the settings in the OXID admin before initializing the queue. The most important groups are:

- General: channel, shop, and language combinations in `sWmdkFFGeneralChannelList`
- Export: output directory, exported fields, delimiters, active/hidden filters, and minimum stock
- Queue: batch limits, article status, stock threshold, attributes, PHP timeout, and memory limit
- Cron timings: lookback periods, weekdays, and time windows used by reset rules
- Spotler/Sooqr and Doofinder: field mappings and XML field types
- flour POS: exported fields, field mappings, prices, URLs, and export markers
- Trusted Shops: product review feed URL
- Debug: log files, debug mode, and allowed legacy cron IP addresses

The default values contain demonstration or project-specific data. In particular, replace channel names, Trusted Shops URLs, flour URLs, and the legacy cron IP list before production use.

## Initial Queue Population

Open:

```text
source/modules/wmdk/wmdkffexportqueue/sql/initialize.sql
```

Set these variables:

- `@ffchannel`: FACT Finder channel ID
- `@store`: OXID shop ID, normally `1`
- `@lang`: OXID language ID, normally `0` for the first language

The script truncates both queue tables, resets `oxarticles.WMDK_FFQUEUE`, and initializes one channel. It must not be run once per channel because every run removes the data created by the previous run. Configure `sWmdkFFGeneralChannelList` and use the reset process to add missing channel records after the initial setup.

## CLI Wrapper

The OXID 6 wrapper starts the normal OXID runtime and invokes the same controllers as the browser entry points.

Run it from the OXID Composer project root:

```bash
php source/bin/wmdkffexport.php <action> [options]
```

Display its built-in help with:

```bash
php source/bin/wmdkffexport.php --help
```

### Actions and Options

| Action | Purpose | Required options |
| --- | --- | --- |
| `reset` | Detect and reset changed or inconsistent queue rows and add missing rows | none |
| `queue` | Process pending queue rows | none |
| `export` | Create a FACT Finder CSV file | `--channel`, `--shop-id`, `--lang` |
| `ts` | Import Trusted Shops product ratings | `--channel` |
| `sooqr` | Create a Spotler/Sooqr XML file | `--channel`, `--shop-id`, `--lang` |
| `doofinder` | Create Doofinder XML and Gzip files | `--channel`, `--shop-id`, `--lang` |
| `flour` | Create a flour POS CSV file | `--channel`, `--shop-id`, `--lang` |

`--flour-id=<value>` is optional for `flour`. In the legacy implementation it acts as an export restriction for records with a flour ID; it should not be interpreted as a reliable filter for one exact ID without verifying the generated file.

### Examples

```bash
php source/bin/wmdkffexport.php reset
php source/bin/wmdkffexport.php queue
php source/bin/wmdkffexport.php export --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php export --channel=kussin_live_en --shop-id=1 --lang=1
php source/bin/wmdkffexport.php ts --channel=kussin_live_de
php source/bin/wmdkffexport.php sooqr --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php doofinder --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php flour --channel=kussin_live_de --shop-id=1 --lang=0 --flour-id=1
```

## Queue Reset Behavior

`reset` does not reset every product. It applies repair and change-detection rules, including:

- resetting recently changed articles and variants
- resetting variants with malformed attribute data
- adding missing channel records
- synchronizing active status and stock changes
- correcting parent variant stock
- resetting recently changed siblings when enabled
- retrying records with placeholder images during the configured time window
- disabling queue rows whose source article no longer exists

Zero reset counters can be valid when no records match the configured time windows or change rules.

## Export Files

With the default `sWmdkFFExportDirectory`, files are written below:

```text
source/export/factfinder/productData/
```

The generated names are:

| Export | File |
| --- | --- |
| FACT Finder | `<channel>.csv` |
| Spotler/Sooqr | `<channel>.sooqr.xml` |
| Doofinder | `<channel>.doofinder.xml` and `<channel>.doofinder.xml.gz` |
| flour POS | `<channel>.flour.csv` |

Verify file permissions, modification times, sizes, and contents after the first run for every configured channel.

## Recommended Cron Sequence

Use the CLI wrapper for new cron definitions. A typical sequence is:

```bash
php source/bin/wmdkffexport.php reset
php source/bin/wmdkffexport.php queue
php source/bin/wmdkffexport.php export --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php export --channel=kussin_live_en --shop-id=1 --lang=1
```

Schedule reset before queue processing and export only after queue processing has had enough runs to clear the expected backlog. Add Trusted Shops and third-party exports only when they are configured for the shop. Prevent overlapping queue runs; the queue controller also uses a flag file below `source/tmp/` as a legacy concurrency guard.

Redirect command output when cron diagnostics are required:

```bash
php source/bin/wmdkffexport.php queue >> source/log/WMDK_FF_QUEUE_CONSOLE.log 2>&1
```

## Legacy Browser Entry Points

The controllers can also be called through OXID browser URLs:

```text
index.php?cl=wmdkffexport_queue
index.php?cl=wmdkffexport_reset
index.php?cl=wmdkffexport_export&channel=<channel>&shop_id=<shop-id>&lang=<lang>
index.php?cl=wmdkffexport_ts&channel=<channel>
index.php?cl=wmdkffexport_sooqr&channel=<channel>&shop_id=<shop-id>&lang=<lang>
index.php?cl=wmdkffexport_doofinder&channel=<channel>&shop_id=<shop-id>&lang=<lang>
index.php?cl=wmdkffexport_flour&channel=<channel>&shop_id=<shop-id>&lang=<lang>
```

These endpoints execute data-changing or export operations. Prefer CLI cron jobs and restrict browser access at the web server, firewall, or reverse proxy. The module's legacy cron IP setting influences response/logging behavior and must not be treated as complete access control.

## Diagnostics

Check records waiting for processing:

```sql
SELECT OXID, Channel, ProductNumber, MasterProductNumber, LASTSYNC, OXTIMESTAMP, ProcessIp
FROM wmdk_ff_export_queue
WHERE LASTSYNC = '0000-00-00 00:00:00'
   OR OXTIMESTAMP = '0000-00-00 00:00:00'
LIMIT 100;
```

Check articles not yet marked as queued:

```sql
SELECT OXID, OXARTNUM, OXTITLE, WMDK_FFQUEUE
FROM oxarticles
WHERE WMDK_FFQUEUE = '0'
LIMIT 100;
```

Check all channel rows for one product number:

```sql
SELECT OXID, Channel, OXSHOPID, LANG, ProductNumber, MasterProductNumber, LASTSYNC, OXTIMESTAMP, ProcessIp
FROM wmdk_ff_export_queue
WHERE ProductNumber = 'example-product-number'
   OR MasterProductNumber = 'example-product-number';
```

When troubleshooting, also inspect the configured queue/export log files, PHP error log, writable directory permissions, channel configuration, and the generated file timestamp.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25421 Pinneberg<br>
Germany

Phone: +49 (4101) 85868 - 0<br>
Email: info@kussin.de

---

&copy; 2006-2026 [Kussin | eCommerce und Online-Marketing GmbH](https://www.kussin.de/). All rights reserved.
