# AGENTS.md

This file defines package-specific instructions for AI/code agents working on `wmdk/wmdkffexportqueue`.

## Scope

- Package root: `html/source/packages/wmdk/wmdkffexportqueue/`
- OXID Composer project root: `html/source/`
- OXID shop root: `html/source/source/`
- Active project platform: OXID eShop PE 7.5; package compatibility begins at OXID 7.4

## Ownership

`wmdk/*` is the former Kussin namespace. Treat this package as Kussin-owned custom development.

## Language Rules

- All Markdown files in this package must be written in English.
- All source-code comments in this package must be written in English.
- User-facing OXID translations may be localized where required.

## Migration Rules

- Treat copied OXID 6, Smarty, Flow, and browser-facing cron patterns as migration input only.
- Use OXID eShop PE 7.5 conventions for new or migrated code without needlessly breaking the declared `^7.4` compatibility range.
- Use Twig for new or migrated templates.
- Replace cron-facing views with OXID console commands that can be executed by Bash crontabs.
- Add module-owned database installation or migration logic for all required queue tables.
- Use `db/sql/wmdk_ff_export_queue.sql` as the current table-structure and data reference during migration.
- Keep Composer work in `html/source/`.
- Do not edit vendor copies directly when this local package source exists.
- Keep `kussin/oxid-base` as the declared runtime dependency for shared admin article links; do not
  copy the Magnalister-specific frameset implementation back into this package.

## Documentation Rules

Keep `README.md`, `AGENTS.md`, and `PROTOCOL.md` current when architecture, dependencies, migration state, risks, or verification results change.
