# Kussin | OXID 6 FACT Finder Export Queue 6.0

## Module Settings

### General Configuration

Basic settings of the extension including the module activation.

TODO: Will follow soon

## Initial Product Import (into Queue)

1. Log into your OXID eShop database interface (e.g. [phpMyAdmin](https://www.phpmyadmin.net/))
2. Select OXID eShop database
3. Open the following SQL file: [`modules/wmdk/wmdkffexportqueue/sql/initialize.sql`](modules/wmdk/wmdkffexportqueue/sql/initialize.sql)
4. Set the following variables in lines 2-4:
   - `@ffchannel` - FACT Finder Channel ID
   - `@store` - OXID eShop Store ID (Default: 1)
   - `@lang` - OXID eShop Language ID (Default: 0)
5. Execute the SQL file

**NOTE:** You can execute the initial import as often as you like it will always remove all previous data.

## Bash Commands (Modes)

### Queue (default)

To execute the queue, you can use the following command:

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_queue'
   ```

### CSV Export

To execute the CSV export, you can use the following command:

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_export&channel=[CHANNEL_ID]&shop_id=[STORE_ID]&lang=[LANG_ID]'
   ```

**NOTE:** The exported files can be fetched from https://www.domain.tld/export/factfinder/productData/[CHANNEL_ID].csv.

#### Spotler XML Export

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_sooqr&channel=[CHANNEL_ID]&shop_id=[STORE_ID]&lang=[LANG_ID]'
   ```

**NOTE:** The exported files can be fetched from https://www.domain.tld/export/factfinder/productData/[CHANNEL_ID].sooqr.xml.

#### Doofinder XML Export

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_doofinder&channel=[CHANNEL_ID]&shop_id=[STORE_ID]&lang=[LANG_ID]'
   ```

**NOTE:** The exported files can be fetched from https://www.domain.tld/export/factfinder/productData/[CHANNEL_ID].doofinder.xml.gz.

#### flour POS XML Export

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_flour&channel=[CHANNEL_ID]&shop_id=[STORE_ID]&lang=[LANG_ID]'
   ```

**NOTE:** The exported files can be fetched from https://www.domain.tld/export/factfinder/productData/[CHANNEL_ID].flour.csv.

### Reset

To execute the queue reset, you can use the following command:

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_reset'
   ```

### Trusted Shops Review Import

To execute the Trusted Shops Review Import, you can use the following command:

   ```bash
   curl -i -X GET \
 'https://www.domain.tld/index.php?cl=wmdkffexport_ts&channel=[CHANNEL_ID]'
   ```

## Crons

TODO: Will follow soon

## Bugtracker and Feature Requests

Please use the [Github Issues](https://github.com/kussin/OxidFactFinderExportQueue/issues) for bug reports and feature requests.

## Support

Kussin | eCommerce und Online-Marketing GmbH<br>
Fahltskamp 3<br>
25421 Pinneberg<br>
Germany

Fon: +49 (4101) 85868 - 0<br>
Email: info@kussin.de

## Copyright

&copy; 2006-2025 Kussin | eCommerce und Online-Marketing GmbH