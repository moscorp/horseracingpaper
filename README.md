# horseracingpaper / 自由馬紙（Carl's Racing Paper）

Chinese-first racing site for **buycarl.com** (WordPress CMS shell, no store).

## Layout

- `wp-content/themes/horse-racing-paper/` — dense Tailwind public theme + default logo/favicon
- `wp-content/plugins/horse-racing-paper/` — admin console, settings, cron registry, race-day shortcode
- `tailwind/` — Tailwind source; run `npm run build:css` after UI class changes
- `docs/DATABASE_MAP.md` — 92 tables from `fengrmkw_moosay.sql`
- `docs/LEGACY_TRANSFER.md` — FTP import notes
- `ARCHITECTURE.md` — product decisions

## Status

- Brand: **自由馬紙 / Carl's Racing Paper**
- Theme/plugin v0.3.13: Tailwind UI, 數據分析 tools, brand-normalized single posts (no alids purple), hierarchical nav
- Admin: logo/favicon upload, legal page editors, category labels for sidebar
- Legacy `/00` in `legacy/00-slim/` (~387 PHP files)
- Deploy: Actions → **Deploy theme & plugin to Namecheap**
- Activation steps: [docs/ACTIVATION.md](./docs/ACTIVATION.md)

**Do not activate theme/plugin until after a successful deploy.**
