# KUSSIN | FACT Finder Export Queue for OXID 6

The KUSSIN | FACT Finder Export Queue for OXID 6 prepares OXID eShop article data in a dedicated queue and creates product feeds for [FACT Finder](https://www.fact-finder.com/). It also supports [Spotler/Sooqr](https://spotler.com/sooqr-is-now-spotler), [Doofinder](https://www.doofinder.com/), [flour POS](https://www.flour.io/), and Trusted Shops product rating imports.

This package contains KUSSIN custom development. Some technical identifiers retain the former WMDK namespace for backward compatibility.

## Supported OXID Versions and Branches

| OXID eShop version | Branch                                                                                             | Status |
| --- |----------------------------------------------------------------------------------------------------| --- |
| 6.2.5 through 6.5.x | [`main`](https://github.com/kussin/OxidFactFinderExportQueue) | Current OXID 6 branch; last tested with OXID eShop 6.5.5 |
| 6.0 through 6.2 | [`bwc/oxid60`](https://github.com/kussin/OxidFactFinderExportQueue/tree/bwc/oxid60) | Backward-compatibility branch for older OXID 6 installations |
| 7.4 and later 7.x releases | [`update/69446_oxid7`](https://github.com/kussin/OxidFactFinderExportQueue/tree/update/69446_oxid7) | OXID 7 migration branch; see its documentation for the current status |

The `main` branch declares `oxid-esales/oxideshop-ce:^6.0`, but its maintained target starts at OXID 6.2.5. Use `bwc/oxid60` for older shops instead of relying on the broad Composer constraint. OXID 6 versions other than 6.5.5 have not been verified against the current `dev` revision unless stated separately.

## Architecture

The module does not export directly from `oxarticles`. It uses `wmdk_ff_export_queue` as an intermediate product data table:

1. OXID articles are added to or marked for the queue.
2. The queue process copies current article data into queue rows.
3. Export processes create CSV or XML files from those rows.
4. The reset process detects changed, missing, or inconsistent rows and schedules them for processing again.

The OXID 6 implementation registers classic module controllers and Smarty templates. A CLI wrapper invokes the same controllers for server-side execution without an HTTP request.

## Requirements

- OXID eShop CE, PE, or EE 6.2.5 or newer within the 6.x series
- PHP 7.4 or newer, according to the package metadata
- `ext-zlib`
- A writable export directory below the OXID shop source directory
- FACT Finder NG 3.1.149 or newer when the FACT Finder export is used

## Installation

Run Composer commands from the OXID Composer project root:

```bash
composer config repositories.kussin_ffqueue vcs https://github.com/kussin/OxidFactFinderExportQueue.git
composer require wmdk/wmdkffexportqueue:dev-main --no-update
composer clear-cache
composer update --no-interaction
vendor/bin/oe-console oe:module:install-configuration source/modules/wmdk/wmdkffexportqueue/
vendor/bin/oe-console oe:module:apply-configuration
```

Before activating the module, complete these deployment steps:

1. Back up the OXID database.
2. Review and adapt [`modules/wmdk/wmdkffexportqueue/sql/install.sql`](modules/wmdk/wmdkffexportqueue/sql/install.sql) for the target shop, then execute it only for an initial installation.
3. Refresh the OXID database views and clear the shop cache.
4. Copy the repository's `export/` structure to `source/export/` and make it writable by the shop and cron user.
5. Deploy `bin/wmdkffexport.php` as `source/bin/wmdkffexport.php` if the deployment does not already place the wrapper there.
6. Activate the module and configure its channel list and export settings in the OXID admin.

The legacy installation SQL is destructive: it drops and recreates both queue tables. It also contains a fixed channel enum and shop-specific schema assumptions. Do not run it as an update script, and validate its `ALTER TABLE oxarticles` block before execution.

## Initial Queue Population

Edit the channel, shop, and language variables in [`modules/wmdk/wmdkffexportqueue/sql/initialize.sql`](modules/wmdk/wmdkffexportqueue/sql/initialize.sql) before running it.

The script truncates both queue tables and resets the article queue flags. Use it only for a deliberate full initialization, never as routine maintenance. For operational details and multi-channel processing, see the [User Guide](USER_GUIDE.md).

## CLI Operations

Run the wrapper from the OXID Composer project root:

```bash
php source/bin/wmdkffexport.php reset
php source/bin/wmdkffexport.php queue
php source/bin/wmdkffexport.php export --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php ts --channel=kussin_live_de
php source/bin/wmdkffexport.php sooqr --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php doofinder --channel=kussin_live_de --shop-id=1 --lang=0
php source/bin/wmdkffexport.php flour --channel=kussin_live_de --shop-id=1 --lang=0 --flour-id=1
```

Use the CLI wrapper for new cron definitions. Browser-facing controller URLs remain available for legacy OXID 6 integrations but should not be exposed publicly without access restrictions.

See the [KUSSIN | FACT Finder Export Queue User Guide](USER_GUIDE.md) for setup, settings, command parameters, cron examples, output files, and troubleshooting.

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
