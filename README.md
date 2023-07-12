# Kussin | OXID 6 FACT Finder Export Queue

Kussin | OXID 6 FACT Finder Export Queue provides real-time CSV Exports for FACT Finder NG.

**The following configuration options are available:**

TODO: Will follow soon

## Requirement

1. OXID eSales CE/PE/EE v6.2.5 or newer
2. PHP 7.4 or newer
3. [FACT Finder NG v3.1.149 or newer](https://www.fact-finder.com/)

## Installation Guide

### Initial Installation

TODO: Will follow soon

### Configuration

#### Step 1: Database

1. Log into your OXID eShop database interface (e.g. [phpMyAdmin](https://www.phpmyadmin.net/))
2. Select OXID eShop database
3. Execute the following SQL file: [`modules/wmdk/wmdkffexportqueue/sql/install.sql`](modules/wmdk/wmdkffexportqueue/sql/install.sql)
4. Refresh [OXID eShop database views](https://docs.oxid-esales.com/eshop/en/6.2/installation/update/standard-update.html#schritt-optional-generating-views)
5. Clear [OXID eShop eShop cache](https://docs.oxid-esales.com/eshop/en/6.2/configuration/caching/caching.html)

#### Step 2: Module

To install the module, please execute the following commands in OXID eShop root directory:

   ```bash
   composer config repositories.kussin_ffqueue vcs https://github.com/kussin/OxidFactFinderExportQueue.git
   composer require wmdk/wmdkffexportqueue --no-update
   composer clearcache
   composer update --no-interaction
   vendor/bin/oe-console oe:module:install-configuration source/modules/wmdk/wmdkffexportqueue/
   vendor/bin/oe-console oe:module:apply-configuration
   ```

**NOTE:** If you are using VCS like GIT for your project, you should add the following path to your `.gitignore` file:
`/source/modules/wmdk/`

#### Step 3: Export Directories

1. Connect to your OXID eShop server via SSH or FTP
2. Upload the directory [`export/`](export/) to `/path/to/oxid/source/`.
3. Set the permissions to `755` for the directory `/path/to/oxid/source/export/`

#### Step 4: Cronjob

TODO: Will follow soon

## User Guide

[User Guide](USER_GUIDE.md)

## Bugtracker and Feature Requests

Please use the [Github Issues](https://github.com/kussin/OxidFactFinderExportQueue/issues) for bug reports and feature requests.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25462 Rellingen<br>
Germany

Fon: +49 (4101) 85868 - 0<br>
Email: info@kussin.de

## Licence

[End-User Software License Agreement](LICENSE.md)

## Copyright

&copy; 2006-2023 Kussin | eCommerce und Online-Marketing GmbH