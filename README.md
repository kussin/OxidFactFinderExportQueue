# KUSSIN | FACT Finder Export Queue

The KUSSIN | FACT Finder Export Queue prepares OXID eShop article data in a dedicated queue and creates product feeds for [FACT Finder](https://www.fact-finder.com/). It also supports [Spotler/Sooqr](https://spotler.com/sooqr-is-now-spotler), [Doofinder](https://www.doofinder.com/), [flour POS](https://www.flour.io/), and Trusted Shops product rating imports.

This package contains KUSSIN custom development. Some technical identifiers retain the former WMDK namespace for backward compatibility.

## Current Context

- Composer package name: `wmdk/wmdkffexportqueue`
- Package root: `html/source/packages/wmdk/wmdkffexportqueue/`
- Active project platform: OXID eShop PE 7.5
- Source baseline before runtime migration: upstream `1.11.6`
- Local migration package version: `2.1.3`
- Migration status: copied OXID 6 package updated to upstream `1.11.6`; console commands and database installer are present for OXID 7 and the package is used on the 7.5 project baseline

## Architecture

The module does not export directly from `oxarticles`. It uses `wmdk_ff_export_queue` as an intermediate product data table:

1. OXID articles are added to or marked for the queue.
2. The queue process copies current article data into queue rows.
3. Export processes create CSV or XML files from those rows.
4. The reset process detects changed, missing, or inconsistent rows and schedules them for processing again.

## Migration Goal

Maintain the migrated package on OXID eShop PE 7.5 while keeping the Composer package name `wmdk/wmdkffexportqueue` as a compatibility boundary.

The package must remain compatible with `kussin/oxid-factfinder-integration` because the storefront module depends on the queue table and field semantics.

The package requires `kussin/oxid-base:0.1.0` for shared authenticated links that open queue articles
in a complete OXID admin editor tab. The base module must be active so its controller routes and Twig
templates are registered.

## Required OXID 7 Changes

- Browser-facing cron views are mapped to OXID console commands for Bash execution.
- Module-owned database installation logic creates the required queue tables and article extension columns.
- The installer restores the legacy Flour POS source columns `WMDKFLOURID`, `WMDKFLOURACTIVE`,
  `WMDKFLOURWAREHOUSEPRICE`, and `WMDKFLOURSHORTURL` on `oxarticles` when they are missing. Existing
  columns and data are preserved, and the OXID database views are regenerated. Run
  `wmdkffexport:install:db` after updating an already installed module.
- Use `db/sql/wmdk_ff_export_queue.sql` as the current table-structure and data reference because the old package SQL is not current.
- Keep browser/admin views only where they remain useful for human administration.
- Use OXID eShop PE 7.5 conventions for module metadata, services, autoloading, and templates. The Composer `^7.4` constraint remains intentional cross-minor compatibility.
- Use Twig for new or migrated templates.

## Current Data Reference

The current queue database dump with structure and data is stored at:

```text
db/sql/wmdk_ff_export_queue.sql
```

This dump is large and should not be treated as a normal deployment migration file. Extract the required table definitions into module-owned installation or migration logic during implementation.

## Installation During Migration

Run Composer commands from the OXID Composer project root:

```bash
cd html/source
composer require wmdk/wmdkffexportqueue
```

Do not activate the module before the OXID 7.4 runtime migration has been reviewed.

## Console Commands

Run commands from the OXID Composer project root:

```bash
cd html/source
vendor/bin/oe-console wmdkffexport:install:db
vendor/bin/oe-console wmdkffexport:cron:queue
vendor/bin/oe-console wmdkffexport:cron:reset
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=wh1_live_de
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=wh1_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=wh1_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:export:flour --channel=wh1_live_de --shop-id=1 --lang=0 --flour-id=1
vendor/bin/oe-console wmdkffexport:maintenance:sync-flour --dry-run
vendor/bin/oe-console wmdkffexport:maintenance:cleanup --dry-run
vendor/bin/oe-console wmdkffexport:maintenance:reset --lastsync-from="2026-06-03 08:30:00" --title-like="%T-Shirt%" --dry-run
```

Run `wmdkffexport:install:db` once after upgrading an existing installation. Besides applying the
current schema, it removes the obsolete fields introduced by legacy patch `62637` from the queue
table. Persisted legacy export-field settings are filtered at runtime so the schema cleanup cannot
break exports.

The commands currently execute the migrated OXID 6 view logic directly to keep behavior comparable during the first OXID 7.4 migration step.

The flour POS maintenance command synchronizes `WMDKFLOURID`, `WMDKFLOURACTIVE`,
`WMDKFLOURWAREHOUSEPRICE`, and `WMDKFLOURSHORTURL` from `oxarticles` into their queue columns.
`FlourSaleAmount` uses the OXID 6 runtime discount formula (`100 - flour price / MSRP * 100`) and
falls back to `OXPRICE` when `OXTPRICE` is zero. Only differing rows are updated and re-queued.

Isolated field verification:

```bash
php packages/wmdk/wmdkffexportqueue/tests/flour-sale-amount.test.php
php packages/wmdk/wmdkffexportqueue/tests/queue-field-calculator.test.php
php packages/wmdk/wmdkffexportqueue/tests/queue-monitor-query.test.php
```

The OXID admin menu contains **KUSSIN | FACT Finder Export Queue - Monitor**. It lists queue records with type-aware
filters, sorting, and pages of at most 100 rows. The CSV action exports every database column for all
records matching the active filters. Selecting a row and resetting it re-queues every record carrying
that `OXID`, across all channels, shops, and languages.

`LASTSYNC` and `OXTIMESTAMP` remain sortable but intentionally have no grid filter.
The initial and fallback ordering is `LASTSYNC DESC`.
`Stock` provides the three choices All, Yes (`Stock > 0`), and No (`Stock <= 0`).
The active filter set is retained in the current admin session. The Clear filters button removes the
stored selection and returns to the unfiltered first page.
The monitor follows the visual conventions of the other Kussin admin modules with a blue information
panel, bordered summary cards, and consistently styled action buttons. Submitting filters, clearing
filters, exporting, resetting records, sorting, or changing pages immediately displays an accessible
loading overlay. For CSV downloads the overlay closes automatically because the browser does not
navigate away from the current page.
The sort-only `FlourMSRP` grid column mirrors the flour export fallback: it shows `MSRP` when it is
greater than zero and otherwise shows `Price`. It replaces `FlourSaleAmount` in the monitor only;
the CSV export continues to contain all physical queue-table columns.
The final grid column is labelled Preview and opens the storefront PDP in a new tab. Relative queue
deeplinks are prefixed with the current shop URL; existing absolute HTTP(S) links are retained.
Each value in the `OXID` column links to the corresponding `oxarticles` record and opens the standard
OXID article master-data editor in a separate browser tab. The authenticated full-admin shell is
provided centrally by `kussin/oxid-base`, not by duplicated monitor or Magnalister code. Both the
article-editor link and storefront-preview link carry localized tooltips; the OXID link uses the same
external-link icon as the preview column so both link targets are visually recognizable.

The OXID article master-data tab provides the reverse navigation as well: its bottom Actions bar
(`body > div.actions`) places an "Open in FACT Finder Monitor" link between the new-article and
article-preview actions. It appears only for a selected, persisted article and opens the monitor in a
new browser tab through an explicit authenticated admin URL, prefiltered to the complete product
family. The URL carries the current OXID challenge token and admin session instead of reusing the
article frame's `admin_start`/`#kussinshare` URL. For a parent or a variant, the filter includes the
parent and every sibling variant from `oxarticles`. The monitor's OXID filter accepts multiple complete
32-character OXIDs separated by commas, whitespace, semicolons, pipes, or SQL-like `OR` notation;
multiple values are matched with one parameterized `IN` condition.

The waiting-article card counts one result per `OXID`. Its timestamp, active, hidden, and stock
conditions mirror the operational export selection; the latter three values come from
`sWmdkFFExportOnlyActive`, `sWmdkFFExportHidden`, and `sWmdkFFExportStockMin` through the OXID 7
module-settings reader. Only this card is
refreshed through the monitor's JSON action; the grid and page remain unchanged. The interval is
configured with `sKussinFFMonitorRefreshInterval` and accepts 5, 10, 15, 20, or 30 seconds, with 15
seconds as the default and invalid-value fallback.
The ETA card uses the exact same distinct `OXID` count as the waiting-article card, divides it by the
configured `sWmdkFFQueueLimit`, and assumes the documented production schedule of one queue run every
two minutes. It shows both the approximate completion time and remaining duration and refreshes with
the article counter. Manual or delayed cron execution naturally makes this estimate finish sooner or
later.
Unsynchronized timestamps use the OXID 6 zero-date marker. The module writes it only through scoped
`INSERT IGNORE`/`UPDATE IGNORE` statements and reads it through text casts for strict-mode safety;
temporary 1000/1970 markers created during migration remain readable until rewritten.

The old browser-facing entry points (`index.php?cl=wmdkffexport_*`) are **also registered again** as of 2026-08-05: the OXID 6 production crontab calls them over curl — the queue every 2 minutes, the FACT-Finder export twice every half hour, flour every 2 hours, the reset every 3 — and OXID 6 has no CLI entry points, so removing the URLs would have broken those entries at cutover. They are a compatibility shim, not the target runtime: prefer the console commands for new cron definitions. Both surfaces run the same legacy view through the same `LegacyConfigBridge`, and unlike OXID 6 the HTTP surface is IP-gated (`sWmdkFFDebugCronjobIpList`; CLI and localhost always pass).

The Worker Mode shortcut uses a separate compatibility endpoint:

```text
index.php?cl=wmdkffexport_ajax&job=reset&oxid=<article-oxid>
```

It immediately marks the selected product's related queue rows (the selected product and, where applicable, its variants or sibling variants) as unsynchronized. This does not run a FACT Finder export itself; it makes the normal queue worker pick up the family again. The endpoint keeps the historical JSON field `reseted` for existing clients, validates the OXID, uses parameterized database queries, emits non-cacheable JSON, and writes the response to the configured queue log. Access protection remains an infrastructure concern (for example HTTP Basic authentication on non-public environments), matching the historical endpoint contract and allowing non-session cURL clients.

For operational details, command explanations, expected JSON output, and troubleshooting notes, see the [KUSSIN | FACT Finder Export Queue User Guide](USER_GUIDE.md).

## Bug Reports and Feature Requests

Use [GitHub Issues](https://github.com/kussin/OxidFactFinderExportQueue/issues) for bug reports and feature requests.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25421 Pinneberg<br>
Germany

Phone: +49 (4101) 85868 - 0<br>
Email: info@kussin.eu

The module is distributed under the [End-User Software License Agreement](LICENSE.md).

---

&copy; 2006-2026 [Kussin | eCommerce und Online-Marketing GmbH](https://www.kussin.de/). All rights reserved.
