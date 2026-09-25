# horseracingpaper / Horse Racing Paper（賽馬報紙）

Chinese-first racing site for **buycarl.com** (WordPress CMS shell, no store).

## Layout

- `wp-content/themes/horse-racing-paper/` — dense public theme
- `wp-content/plugins/horse-racing-paper/` — admin console, settings, cron registry
- `docs/DATABASE_MAP.md` — 92 tables from `fengrmkw_moosay.sql`
- `docs/LEGACY_TRANSFER.md` — FTP import notes
- `ARCHITECTURE.md` — product decisions

## Status

FTP legacy fetch may still be running. Scaffold + DB map land first; wire `/00` after artifact arrives.

Deploy target: Namecheap `/home/fengrmkw/buycarl.com` via GitHub Actions FTP (secrets already configured).
