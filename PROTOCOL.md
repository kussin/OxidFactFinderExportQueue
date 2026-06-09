# PROTOCOL.md

This document records agreements and implementation decisions for `wmdk/wmdkffexportqueue`.

## Scope

- Package root: `html/source/packages/wmdk/wmdkffexportqueue/`
- Composer package name: `wmdk/wmdkffexportqueue`
- Active target platform: OXID eShop PE 7.4

## Stable Decisions

- `wmdk/*` is the former Kussin namespace and is treated as Kussin-owned custom development.
- Keep Composer package name `wmdk/wmdkffexportqueue` during the first OXID 7.4 migration step.
- Require `oxid-esales/oxideshop-ce:^7.4` to prevent accidental installation in OXID 6.5 projects.
- Migrate this package together with `kussin/factfinder-integration` because the storefront integration depends on queue table and field semantics.
- Before the OXID 7.4 runtime migration, update the copied OXID 6 source to the latest known upstream version `1.11.6`.
- Use local migration package version `2.0.0` for the OXID 7.4 migration while preserving the source-baseline reference to upstream `1.11.6`.
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
- Apply the same cleanup rule to `kussin/factfinder-integration` before the FACT Finder migration is considered complete.
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
- Removed inactive OXID 6 leftovers after command validation: the old package CLI script, unregistered `core/*` helpers, unregistered legacy `controllers/admin/*` classes, inactive browser-only `wmdkffexport_ajax` and `wmdkffexport_mapping` views, unused AJAX/popup template registrations, the obsolete attribute popup template, and stale package-local SQL files. The active installation path is `DatabaseInstaller`; the current SQL reference stays in root `db/sql/`.
- Kept `views/admin/*` language source files for now because the OXID 7 `views/admin_twig/*` language entry points still include them. They are legacy-structured but still used.
- Rewrote `USER_GUIDE.md` as an operational user guide and documented `wmdkffexport:cron:reset` counters, expected zero-reset scenarios, relevant settings, and SQL checks. Linked the guide from `README.md`.
