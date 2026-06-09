# WMDK FACT Finder Export Queue

Kussin | OXID 6 FACT Finder Export Queue provides real-time CSV Exports for [FACT Finder](https://www.fact-finder.com/). It also supports [Spotler](https://spotler.com/sooqr-is-now-spotler) and [Doofinder](https://www.doofinder.com/) and [flour POS](https://www.flour.io/).

This package contains the FACT Finder export queue from the former WMDK namespace. The WMDK namespace is Kussin-owned and must be treated as custom development.

## Current Context

- Composer package name: `wmdk/wmdkffexportqueue`
- Package root: `html/source/packages/wmdk/wmdkffexportqueue/`
- Active target platform: OXID eShop PE 7.4
- Source baseline before runtime migration: upstream `1.11.6`
- Local migration package version: `2.0.0`
- Migration status: copied OXID 6 package updated to upstream `1.11.6`, console commands and database installer added for OXID 7.4 migration

## Migration Goal

Migrate the package to OXID eShop PE 7.4 while keeping the Composer package name `wmdk/wmdkffexportqueue` for the first migration step.

The package must remain compatible with `kussin/factfinder-integration` because the storefront module depends on the queue table and field semantics.

## Required OXID 7.4 Changes

- Browser-facing cron views are mapped to OXID console commands for Bash execution.
- Module-owned database installation logic creates the required queue tables and article extension columns.
- Use `db/sql/wmdk_ff_export_queue.sql` as the current table-structure and data reference because the old package SQL is not current.
- Keep browser/admin views only where they remain useful for human administration.
- Use OXID eShop PE 7.4 conventions for module metadata, services, autoloading, and templates.
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
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=kussin_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=kussin_live_de
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=kussin_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=kussin_live_de --shop-id=1 --lang=0
vendor/bin/oe-console wmdkffexport:export:flour --channel=kussin_live_de --shop-id=1 --lang=0 --flour-id=1
vendor/bin/oe-console wmdkffexport:maintenance:cleanup --dry-run
vendor/bin/oe-console wmdkffexport:maintenance:reset --lastsync-from="2026-06-03 08:30:00" --title-like="%T-Shirt%" --dry-run
```

The commands currently execute the migrated OXID 6 view logic directly to keep behavior comparable during the first OXID 7.4 migration step. The old browser-facing entry points must not be used for new cron definitions.

For operational details, command explanations, expected JSON output, and troubleshooting notes, see the [FACT Finder Export Queue User Guide](USER_GUIDE.md).

---

&copy; 2006-2026 [Kussin | eCommerce und Online-Marketing GmbH](https://www.kussin.de/). All rights reserved.
