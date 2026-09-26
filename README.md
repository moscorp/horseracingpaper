# horseracingpaper / Horse Racing Paper（賽馬報紙）

Chinese-first racing site for **buycarl.com** (WordPress CMS shell, no store).

## Layout

- `wp-content/themes/horse-racing-paper/` — dense public theme
- `wp-content/plugins/horse-racing-paper/` — admin console, settings, cron registry
- `docs/DATABASE_MAP.md` — 92 tables from `fengrmkw_moosay.sql`
- `docs/LEGACY_TRANSFER.md` — FTP import notes
- `ARCHITECTURE.md` — product decisions

## Status

- Legacy `/00` in `legacy/00-slim/` (~387 PHP files)
- Plugin v0.2: dense race-day UI wired to `hkracing_*` via `$wpdb`
- Blog priority thresholds editable in WP admin（強烈/留意/配腳）
- Deploy: Actions → **Deploy theme & plugin to Namecheap**
- Activation steps: [docs/ACTIVATION.md](./docs/ACTIVATION.md)

**Do not activate theme/plugin until after a successful deploy.**