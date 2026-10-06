# PROTOCOL.md

## 2026-10-06: Legacy patches 62637 and 67380 removed

The custom queue fields introduced by legacy patch `62637` have been removed. Queue processing no
longer reads `oxarticles.WMDKSOTD` or calculates the Sale-of-the-Day and kids flags. New queue tables
omit all three derived columns; module activation or `wmdkffexport:install:db` removes them from an
existing queue table. The export path filters the retired names from persisted legacy field lists so
schema cleanup cannot break an existing configuration. The dedicated calculations, configuration,
tests, and documentation were removed as well.

Legacy patch `67380` is also no longer applied. The five Smarty variant block templates contain their
original package implementations instead of delegating to `smarty.block.parent`. `PATCHES.txt` now
records that no Composer patches are applied to this package.

## 2026-10-05: KUSSIN | FACT Finder Export Queue - Monitor

The OXID admin now exposes **KUSSIN | FACT Finder Export Queue - Monitor**. Its whitelist-based grid provides
type-aware filters, sorting, and fixed 100-record pagination without allowing request parameters to
become SQL identifiers. Filtered CSV exports stream all queue columns and are protected against CSV
formula injection. Manual selection is article-based: selecting any queue row resets every record
with the same `OXID` across all channels, shops, and languages. The monitor uses the shared strict-mode
sentinels and recognizes both current and legacy unsynchronized timestamps in its waiting counter.

The waiting indicator uses the OXID 6 zero-date marker for both timestamp columns. Comparisons cast
the columns to text so strict mode does not parse the zero date; the temporary 1000/1970 migration
markers remain recognized during the transition. Active, hidden, and minimum-stock constraints are
read from the matching export module settings. Results are grouped by `OXID`, then counted, so
channels and languages do not inflate the article total. A small JSON action refreshes only this
number at the interval selected through `sKussinFFMonitorRefreshInterval`. The Kussin-prefixed select
setting allows 5, 10, 15, 20, or 30 seconds; 15 seconds is both its default and validated fallback.

The companion ETA deliberately uses the same distinct `OXID` result as the waiting counter. Channels,
shops, and languages therefore cannot inflate its displayed calculation basis. Remaining runs are
calculated as `ceil(articles / sWmdkFFQueueLimit)` and multiplied by the documented 120-second
production interval. The JSON refresh returns the shared article count and remaining seconds; the
browser renders the localized approximate completion time and duration.

The admin controller resolves `sWmdkFFExportOnlyActive`, `sWmdkFFExportHidden`, and
`sWmdkFFExportStockMin` through the OXID 7-aware `ModuleSettingsReader`. Direct `getConfigParam()`
access is intentionally avoided because module settings are stored in the generated shop YAML and
are otherwise returned as `null` in this request context, which previously made the monitor count
inactive records with a zero stock threshold.

The monitor displays the calculated, sort-only `FlourMSRP` value instead of `FlourSaleAmount`.
Its `IF(MSRP > 0, MSRP, Price)` expression is identical to the OXID 6 flour export fallback. This is
a grid projection only; the complete CSV remains based on the physical queue-table columns.

Although numeric columns are otherwise sort-only, `Stock` has a dedicated three-state filter: all
records, positive stock, or zero/negative stock. The same condition applies to the filtered CSV.

The Deeplink grid heading is presented as Preview. Preview targets are normalized for the admin UI:
relative paths use the active shop URL, absolute HTTP(S) URLs remain unchanged, and unsafe schemes
are not rendered as links. Stored queue data and CSV values remain untouched.

The `OXID` column uses `kussin/oxid-base`'s `AdminArticleEditUrlBuilder` and opens the corresponding
`oxarticles` entry on the standard `article_main` tab in a separate, authenticated OXID admin shell.
The queue package declares the base package as a runtime dependency. The shared implementation was
derived from the working Magnalister pattern, while the Magnalister vendor package remains unchanged.
The OXID and storefront-preview links both expose localized title text, and the OXID value carries
the same external-link icon as the Preview column.

Reverse navigation is available from the bottom `article_main` Actions bar selected explicitly by
`body > div.actions`: the controller extension resolves the current article's root parent and all
variants from `oxarticles`, then opens the monitor in a new tab with a multi-value OXID filter. The
link is inserted between the new-article and preview actions and is omitted for unsaved or unselected
articles. Its target is built by `kussin/oxid-base`'s `AdminUrlBuilder`, which emits a dedicated
monitor-controller URL with the current challenge token and admin session rather than copying the
article frame's `admin_start`/`#kussinshare` URL. Multiple complete OXIDs are normalized and matched
through a parameterized `IN` condition; the existing single-value partial-text filter remains
backward-compatible.

Normalized monitor filters persist in the current OXID admin session under a Kussin-owned session
key. A dedicated Clear filters action deletes that state; sorting and pagination links continue to
carry the active filters explicitly.

The monitor's default and invalid-input fallback ordering is `LASTSYNC DESC`.

The monitor presentation follows the established Kussin admin visual vocabulary while keeping all
styles scoped to the monitor, avoiding a runtime dependency on another package. An `aria-live`
loading overlay and `aria-busy` state provide immediate feedback for filter, clear, export, reset,
sort, and pagination actions. The overlay is removed on page restoration; for the non-navigating CSV
download it is also dismissed automatically after the response has been triggered.

Filter controls intercept Enter and explicitly submit the regular filter button. This avoids browser
dependent implicit-submit behavior and guarantees that the adjacent CSV action is not selected as
the submitter; the normal loading feedback remains active.

## 2026-10-05: OXID 6 calculation parity audit

All arithmetic and derived queue fields in the active OXID 7 package were compared with the OXID 6
`1.11.6` source baseline. OXID 6 remains authoritative for retained business calculations. The audit
restored rounding before flooring the regular sale percentage and restored the Flour export's MSRP
fallback and discount recalculation. It also repaired mojibake in the cloned color and converter
mappings because the damaged values changed matching behavior.

OXID 7-only changes were retained where they do not alter business calculations: request and module
API migration, strict-mode-compatible synchronization timestamps, safe handling of missing fields and
manufacturers, XML sanitization, and the corrected Trusted Shops target-field comparisons.

## 2026-10-05: flour POS queue synchronization

The new `wmdkffexport:maintenance:sync-flour` command synchronizes the four flour source fields from
`oxarticles` into existing `wmdk_ff_export_queue` rows and emits one JSON response. It updates only
rows with differing flour data and then marks those rows as unsynchronized. Both timestamp columns
use the OXID 6 zero-date marker through a narrowly scoped `UPDATE IGNORE`. This was verified against
the migrated database and avoids the timezone-dependent lower boundary of the `TIMESTAMP` type.

The migrated OXID 7 trait previously calculated the retained price percentage instead of the OXID 6
runtime discount. Queue processing and the maintenance command now share the OXID 6 formula
`100 - (flour price * 100 / reference price)`, including the `OXPRICE` fallback when MSRP is zero.
The historical one-time `sql/flour-data.init.sql` used the retained percentage instead; the runtime
trait and the field label "discount" are authoritative for the migrated behavior.

## 2026-10-05: Flour POS article columns

The idempotent database installer now creates the four legacy `oxarticles` source columns required
by `FlourTrait`: `WMDKFLOURID`, `WMDKFLOURACTIVE`, `WMDKFLOURWAREHOUSEPRICE`, and
`WMDKFLOURSHORTURL`. Their types, nullability, and defaults follow the OXID 6 production schema so
existing imports retain their data semantics. Module activation covers new installations; existing
installations must rerun `wmdkffexport:install:db` after updating. Existing columns are left unchanged,
and the installer regenerates OXID's database views so the article model can read newly added fields.

This document records agreements and implementation decisions for `wmdk/wmdkffexportqueue`.

## Scope

- Package root: `html/source/packages/wmdk/wmdkffexportqueue/`
- Composer package name: `wmdk/wmdkffexportqueue`
- Active project platform: OXID eShop PE 7.5; declared package compatibility: `^7.4`

## Stable Decisions

- `wmdk/*` is the former Kussin namespace and is treated as Kussin-owned custom development.
- Keep Composer package name `wmdk/wmdkffexportqueue` as an explicit OXID 7 compatibility boundary.
- Require `oxid-esales/oxideshop-ce:^7.4` to prevent accidental installation in OXID 6.5 projects.
- Migrate this package together with `kussin/oxid-factfinder-integration` because the storefront integration depends on queue table and field semantics.
- Before the OXID 7.4 runtime migration, update the copied OXID 6 source to the latest known upstream version `1.11.6`.
- Current local package version is `2.1.3`; preserve the source-baseline reference to upstream `1.11.6` as migration history.
- Replace browser-facing cron views with OXID console commands for Bash execution.
- Use `wmdkffexport:*` as command namespace for queue cron operations.
- Add module-owned database installation logic for required queue tables and article extension columns.
- Add module-owned queue maintenance commands for deleting orphaned queue records and resetting selected queue records for reprocessing.
- Keep module activation event handlers in package `src/` so OXID can validate them through Composer autoload before module services are imported.
- Keep the package-root `services.yaml` as the module service entry point because OXID 7 module configuration resolves module services relative to the Composer package source.
- Keep queue command service definitions directly in the package-root `services.yaml` because OXID's module service loader did not resolve the nested `src/Command/services.yaml` import reliably in this module.
- Keep OXID 7 runtime service classes in Composer-autoloaded `src/` folders.
- Keep legacy OXID module files directly in the package root. Do not reintroduce the old `modules/wmdk/wmdkffexportqueue/` nesting.
- Define console command names through `configure()->setName()` and keep the same command name in the service tag, matching the registration pattern used by working OXID 7 modules.
- Do not keep OXID 6-style admin class extensions as path-based `extend` metadata entries during the CLI migration step. They must be migrated to valid OXID 7 namespaced classes before being re-enabled.
- Use `db/sql/wmdk_ff_export_queue.sql` as the current table-structure and data reference because the old package SQL is not current.
- Copied OXID 6, Smarty, Flow, and browser-facing cron patterns are migration input only.
- The OXID 6 metadata `files` section is removed for OXID 7.4 installability. Its old view/helper registrations must be migrated to explicit OXID 7 services, controllers, or console commands where still needed.
- Preserve legacy compatibility only while it is needed for migration comparison. Remove obsolete wrappers, browser cron entry points, stale templates, unused SQL files, and other historical leftovers before finalizing the FACT Finder modules.

## Follow-Up Tasks

- Remove migrated legacy cron artifacts after the OXID console commands are validated in the OXID 7.4 runtime.
- Review old Smarty/browser templates and delete or convert them once they are no longer needed for admin UI or migration comparison.
- Rebuild the old `article_attribute`, `article_attribute_ajax`, and `module_main` admin UI behavior as new OXID 7 namespaced controllers if the removed legacy popup/AJAX mapping UI or module status display is required again.
- Re-check the original `wmdk/wmdkffexportqueue` `1.11.6` `extend` block before final cleanup so no required admin behavior is lost during the OXID 7 migration.
- Apply the same cleanup rule to `kussin/oxid-factfinder-integration` before the FACT Finder migration is considered complete.
- Add a repeat option to `wmdkffexport:cron:queue` so one command execution can run the queue processing multiple times in sequence, for example `--repeat=<count>`. Define safe limits, output aggregation, and stop-on-error behavior before implementation.
- Review `wmdkffexport:export:flour --flour-id` naming. The legacy request parameter currently acts as a Boolean flag that restricts Flour export rows to records with any non-empty `FlourId`; it does not filter by the provided ID value.
- Re-test Doofinder and Sooqr XML exports with another data set before final acceptance. Both commands work in the current data set, but XML sanitizing, entity handling, CDATA handling, XML validation, and Doofinder `.gz` creation still need validation against different product data.

## Verification Log

### 2026-06-02

- Copied package source from `html/source/vendor/wmdk/wmdkffexportqueue/` to `html/source/packages/wmdk/wmdkffexportqueue/` for local migration work.
- Added local `AGENTS.md` and `PROTOCOL.md` to satisfy custom package documentation rules.
- Rewrote `README.md` in English and aligned it with the OXID 7.4 migration context.
- Recorded that `db/sql/wmdk_ff_export_queue.sql` is the current queue table/data reference.
- Changed metadata version to `2.1` and removed the unsupported OXID 6 `files` metadata section for Composer/OXID 7 installability.
- Recorded the deferred prerequisite to update the copied OXID 6 source to upstream `1.11.6` before the OXID 7.4 runtime migration starts.
- Updated the copied OXID 6 source to upstream `wmdk/wmdkffexportqueue` `1.11.6` while preserving local OXID 7.4 migration version `2.0.0`.
- Added OXID console commands for legacy queue actions: `queue`, `reset`, `export`, `trusted-shops`, `sooqr`, `doofinder`, and `flour`.
- Added `wmdkffexport:install:db` and module activation database installation for queue tables and required `oxarticles` columns.
- Kept the commands as legacy-view runners for the first migration step to preserve behavioral comparability.
- Moved activation setup classes to Composer-autoloaded `src/Setup/` after OXID rejected module-path event handlers as not callable during activation validation.
- Added package-root `services.yaml` as compatibility entry for OXID service activation when the module source path points to `vendor/wmdk/wmdkffexportqueue`.
- Replaced the package-root service import with direct command service definitions to avoid nested import resolution issues during OXID service loading.
- Changed the `Wmdk\FactFinderQueue\` PSR-4 prefix to the package-internal module source path so console command classes are Composer-autoloadable during OXID activation validation.
- Moved queue command classes from the legacy module path to `src/Command/`, changed the package PSR-4 root to `src/`, and added `configure()->setName()` command registration to match the registration pattern used by working OXID 7 modules.
- Flattened the package structure by moving legacy OXID module files from `modules/wmdk/wmdkffexportqueue/` into the package root and changing the OXID Composer `source-directory` to `.`.
- Removed the old path-based `extend` metadata entries because OXID reported them as invalid module files in the OXID 7 backend.
- Added `wmdkffexport:debug-paths` as a temporary diagnostic command without database or legacy view dependencies to isolate service registration issues.
- Replaced the nested command service import with direct command service definitions in the package-root `services.yaml` after isolated service loading showed that the root import failed while the nested file loaded directly.
- Removed the unused `src/Command/services.yaml` to avoid duplicate or stale service definitions.
- Temporarily reduced `services.yaml` to the minimal `wmdkffexport:debug-paths` command to isolate whether OXID rejects the module service import itself or one of the legacy command services.
- Restored all queue command service definitions after the missing commands were traced to a stale `omikron/oxid-factfinder` import in `active_module_services.yaml`, not to the WMDK command definitions.
- Removed the temporary `wmdkffexport:debug-paths` diagnostic command after the service import issue was resolved.
- Added `wmdkffexport:maintenance:cleanup` to delete queue rows whose `OXID` no longer exists in `oxarticles`, mark rows with empty `ProductNumber` as inactive and hidden, and log affected cleanup data to `source/log/KUSSIN_FACTFINDER_CLEANUP.log`.
- Added `wmdkffexport:maintenance:reset` to reset `LASTSYNC`, `OXTIMESTAMP`, and `ProcessIp` for explicitly filtered queue rows. The command requires at least one filter and supports `--dry-run`.
- Renamed queue commands to hierarchical command names: `install:db`, `export:*`, `cron:*`, `import:*`, and `maintenance:*`.
- Moved namespaced traits from `Traits/` to `src/Traits/` and kept a single Composer PSR-4 mapping `Wmdk\FactFinderQueue\` => `src/`. Namespaced OXID 7 module code belongs under `src/`; legacy non-namespaced views remain package-root migration artifacts until they are replaced.
- Added OXID 7 admin Twig language entry points under `views/admin_twig/{de,en}/wmdkffexportqueue_lang.php` so module setting labels from the legacy `module_options.php` files are loaded by the OXID 7 admin.
- Added package `assets/module.png` because OXID 7 installs module assets via `source/out/modules/<module-id>` symlinks and the package must not produce orphaned asset links.
- Added CLI legacy defaults for empty debug logfile settings and normalized empty CLI DOCUMENT_ROOT to sShopDir because legacy browser views build log paths from DOCUMENT_ROOT and module config values.
- Added a central log file path resolver so relative log settings are joined with `sShopDir` safely and always resolve below `source/log/` by default.
- Standardized FACT Finder queue log default filenames from the legacy `WMDK_` prefix to the `KUSSIN_` prefix and added `sWmdkFFDebugLogFileCleanup` for the cleanup command log.
- Added a central export directory manager so module activation creates the package `export/` directory tree below OXID `sShopDir/export/` and all legacy export commands create the currently configured `sWmdkFFExportDirectory` before writing files.
- Changed the shared legacy console bridge so `--cron` suppresses normal JSON STDOUT for all legacy view commands. PHP warnings and fatal errors still go to STDERR and must remain visible.
- Forced Doofinder and Sooqr exports to use the internal safe temporary delimiter when the configured temporary delimiter is empty, `|`, or equals the target export delimiter. The active `|` delimiter collided with attribute/category values and produced mismatched field/value counts.
- Replaced legacy Doofinder/Sooqr `var_dump()` and `die()` export failures with structured validation errors.
- Added export summary logging through `sWmdkFFDebugLogFileExport` when debug mode is enabled.
- Normalized export field aliases such as `FromPrice AS Price` before header generation so downstream third-party converters receive the expected mapped field names.
- Preserve configured `AS` expressions while building SQL SELECT field lists. Flour relies on expressions such as `Attributes AS Year` and maps the original configured expression to the final CSV header, while non-Flour exports only normalize aliases during header generation.
- Replaced the legacy non-namespaced Doofinder gzip compressor with a Composer-autoloaded `GzipCompressor` service and added the generated `.gz` file path to the Doofinder command response.
- Added centralized XML value sanitization for third-party XML exports. It decodes HTML entities, normalizes non-breaking spaces, removes XML-invalid control characters, and neutralizes embedded `]]>` sequences inside CDATA values before `ArrayToXml` receives product data.
- Changed `sWmdkFFExportTmpDelimiter` to a select setting with `csv` and `xml` modes. Runtime mapping keeps old direct delimiter values compatible while exposing safer backend choices.
- Added the global `--cron` flag for legacy view commands, normalized CLI JSON responses by removing `template` and `process_ip`, and added queue selection diagnostics for the queue cron command.
- Reintroduced the critical article save hooks from the original OXID 6 `extend` block as OXID 7 FQCN-based admin controller extensions. The old path-based metadata entries stay removed because they are invalid in OXID 7.
- Updated the module backend description for OXID 7 by removing old "new" labels, replacing browser cron references with OXID console commands, and adding an English description section.
- Changed admin article save queue marking to reset existing queue rows by `OXID`, `ProductNumber`, and `MasterProductNumber` across all channels before creating missing configured channel rows. This avoids missed resets when existing imported queue data uses production channels but module defaults still contain placeholder channel settings.
- Removed the `wmdkffexport_helper` dependency from the legacy reset view by moving the required channel-list parsing into the reset view until the reset flow is replaced by a dedicated OXID 7 service.
- Added a temporary module settings reader with an OXID 7 YAML fallback because legacy CLI views do not reliably receive module settings through `Registry::getConfig()->getConfigParam()`. This must be replaced by proper service-based settings access when the legacy views are removed.
- Scanned the package for remaining OXID 6 request access and replaced active `Registry::getConfig()->getRequestParameter()` calls in command-executed code with `Registry::getRequest()->getRequestParameter()`.
- Updated the legacy console bridge to preload OXID 7 module settings into the legacy config object before executing old view classes. This keeps migrated console commands stable while old views are still used as a temporary bridge.
- Removed inactive OXID 6 leftovers after command validation: the old package CLI script, unregistered `core/*` helpers, unregistered legacy `controllers/admin/*` classes, the inactive browser-only `wmdkffexport_ajax` view, the inactive `wmdkffexport_mapping` view, unused AJAX/popup template registrations, the obsolete attribute popup template, and stale package-local SQL files. The active installation path is `DatabaseInstaller`; the current SQL reference stays in root `db/sql/`.
- Kept `views/admin/*` language source files for now because the OXID 7 `views/admin_twig/*` language entry points still include them. They are legacy-structured but still used.
- Rewrote `USER_GUIDE.md` as an operational user guide and documented `wmdkffexport:cron:reset` counters, expected zero-reset scenarios, relevant settings, and SQL checks. Linked the guide from `README.md`.

### 2026-10-05

- Restored the Worker Mode compatibility URL `index.php?cl=wmdkffexport_ajax&job=reset&oxid=<article-oxid>` as a namespaced OXID 7 controller for Redmine issues `#65610` and `#70841`.
- Added the controller to both package metadata and the repository's active module configuration so the deployed module resolves the URL without a deactivate/activate cycle.
- Replaced the removed OXID 6 view/template and the leftover AJAX trait with `AjaxResetController` and `ArticleQueueResetter`.
- Preserved the historical unauthenticated OXID application contract and `reseted` JSON property required by Worker Mode and operational cURL callers while adding stable response fields, validation errors, HTTP status codes, no-cache headers, and queue response logging. Environment-level access controls such as HTTP Basic authentication remain outside the module.
- Replaced interpolated SQL with parameterized queries and the OXID 7-compatible `QueueSyncSentinel` values. A standalone product no longer accidentally matches every queue row whose `MasterProductNumber` is empty.
- The queue dump confirms that the ticket's sample parent resolves to the same twelve OXIDs shown in the historical response, and `git diff --check` passes. PHP lint, Composer validation, the existing PHP regression script, and live HTTP/database verification were not available in the current Windows shell and remain deployment checks.
