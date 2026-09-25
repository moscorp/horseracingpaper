# Horse Racing Paper — Architecture Decision

Site rename: **Horse racing paper**（賽馬報紙）  
Domain stays `buycarl.com` for now (DNS/hosting path `/home/fengrmkw/buycarl.com`).

## Locked decisions (from you)

| Topic | Decision |
|-------|----------|
| Store / WooCommerce | **Remove** — no storefront |
| Frontpage | Racing-first, brand **Horse racing paper** |
| Language | **Chinese only** |
| Modules v1 | Racing + Mark Six + Funds (Funds = **backend email only**) |
| PHP | Stay on 7.4 until rewrite deploys; **upgrade to 8.1 after** |
| Deploy | GitHub Actions → FTP/SFTP → `/home/fengrmkw/buycarl.com` |
| Repo | https://github.com/moscorp/horseracingpaper |
| Prior Cursor review | Other buycarl repo/chat — **not available here**; treat uploaded code + SQL as source of truth |

---

## WordPress vs full rewrite — recommendation

You asked to rebuild off WordPress **and** keep WP Admin / cron blog posts.

**Recommended path: WordPress as CMS shell only (not a “store site”).**

Keep WordPress for:
- Blog posts that cron already creates (`wp_posts` + Yoast)
- Login / roles / Application Passwords
- Dense custom **Admin Console** under `wp-admin` (custom plugin pages — compact UI, low padding)
- Media uploads if needed

Remove / stop using:
- WooCommerce / shop pages / product chrome
- Current storefront theme clutter on the homepage

Build new:
- Custom theme `horse-racing-paper` — Chinese racing homepage + race day UI (Tailwind + interactive JS, dense grouping)
- Custom plugin `horse-racing-paper` — racing API, settings/constants, cron registry, M6, funds email jobs, blog publisher fixes
- Migrate `/00/*.php` into the plugin (authenticated cron runner); thin redirects during cutover

### Why not “delete WordPress entirely” in v1?

Going 100% custom PHP means rebuilding auth, admin, blog editor, SEO (Yoast), and cron→post publishing from scratch on Namecheap shared hosting — while your biggest pain is racing UI density, constants, cron ops, and broken blog sequencing. Keeping WP as a thin CMS is the lower-risk way to fix those first.

If you still want zero WordPress later (v2), we can migrate posts to a custom `posts` table and drop WP — after the racing product is stable.

---

## Blog + cron — how to implement (and fix bugs)

**Keep publishing into WordPress posts**, but fix the pipeline:

1. **Single publisher service** in the plugin  
   - Input: race date / venue / scores / M6 draw  
   - Output: one post, stable slug, correct `post_date` / `post_date_gmt`, category, Yoast meta  
2. **Fix sequence bugs** (typical causes we will verify in SQL + code once uploaded):
   - Wrong `post_date` timezone (UTC vs HKT) → wrong order on homepage
   - `force=1` re-runs creating duplicates
   - Title/slug collisions overwriting or scattering posts
   - Draft vs publish race between scrape and blog cron
   - Homepage query not `orderby=date` / sticky / custom orderby messing sequence
3. **Idempotent jobs**  
   - Key = `(type, race_date, venue)` or `(type, draw_no)`  
   - Re-run updates the same post; no duplicate
4. **Admin “Publishing” console**  
   - Queue, last publish, errors, “rebuild post”, sequence preview  
5. Mark Six + race review use the same publisher with templates editable in Settings (no hard-coded copy in PHP)

---

## Product surfaces

```text
Public (Chinese)
├── /                 Race day paper (dense one-page)
├── /race/{date}      Historical race day
├── /horse/{code}     Horse profile
├── /marksix/         Mark Six paper
└── /blog/            Cron-generated analysis / reviews

Admin (wp-admin → Horse Racing Paper console)
├── Dashboard         Sync health, next race, job failures
├── Racing data       Dense tables (low padding)
├── Scores / signals  Constants & weights (editable)
├── Cron registry     All jobs + provider + last run
├── Publishing        Blog queue / sequence / Yoast
├── Mark Six          Draws + analysis settings
└── Funds email       Hang Seng / HSI jobs + mail only
```

---

## Deploy model

```text
git push main
  → GitHub Action (FTP/SFTP Deploy)
  → /home/fengrmkw/buycarl.com
     wp-content/themes/horse-racing-paper/
     wp-content/plugins/horse-racing-paper/
     (legacy /00 wrappers during migration)
```

Required GitHub Secrets (you add in repo Settings → Secrets):

| Secret | Purpose |
|--------|---------|
| `FTP_SERVER` | Namecheap FTP/SFTP host |
| `FTP_USERNAME` | FTP user |
| `FTP_PASSWORD` | FTP password |
| `FTP_SERVER_DIR` | `/home/fengrmkw/buycarl.com/` or `public_html/` equivalent |
| `CRON_SECRET` | Shared token for cron URLs |
| `DB_*` (optional in CI) | Only if we add migrate step |

Do **not** commit FTP passwords or `wp-config.php`.

---

## Still blocked in this Cloud Agent

Cannot read Windows path `D:\codz\workspace\buycarl.com\...`.

Please **upload into this Cursor chat / project** (or `git push` into this repo):

1. `wp-content.zip`
2. `fengrmkw_moosay.sql`
3. Full `/00` folder (zip) — still required for cron/managers rewrite
4. FTP host + username (password via GitHub Secret or Cursor secret — not in chat if avoidable)

Once those land, next steps: parse SQL → table map, extract theme, scaffold theme+plugin, dense Chinese UI, cron registry, blog sequence fix, FTP deploy workflow.
