# horseracingpaper / Horse Racing Paper（賽馬報紙）

Chinese-first racing site for **buycarl.com** (WordPress CMS shell, no store).

## Layout

- `wp-content/themes/horse-racing-paper/` — dense public theme
- `wp-content/plugins/horse-racing-paper/` — admin console, settings, cron registry
- `docs/DATABASE_MAP.md` — 92 tables from `fengrmkw_moosay.sql`
- `docs/LEGACY_TRANSFER.md` — FTP import notes
- `ARCHITECTURE.md` — product decisions

## Status

Legacy `/00` imported (slim source in `legacy/00-slim/`, ~387 PHP files). Full FTP extract kept local-only (gitignored).

Deploy target: Namecheap `/home/fengrmkw/buycarl.com` via GitHub Actions FTP (secrets already configured).
