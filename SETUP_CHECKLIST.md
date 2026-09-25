# BuyCarl Rewrite — What You Need To Provide First

This Cloud Agent **cannot** read `D:\codz\workspace\buycarl.com\00` on your PC. Until the source, DB schema, and deploy credentials are in this GitHub repo (or uploaded here), rebuild + Namecheap deploy cannot start.

Live site snapshot (already confirmed from the public site):
- WordPress + Yoast SEO on Namecheap (LiteSpeed / PHP 7.4.33) behind Cloudflare
- Storefront theme + WooCommerce-style shop
- Racing / Mark Six tooling under `https://buycarl.com/00/*.php`
- External cron triggers from [cron-job.org](https://console.cron-job.org/jobs)
- **Security note:** several `/00/` cron URLs run with no auth when opened in a browser — those must be locked behind a secret token before rewrite ships

---

## Priority 1 — Must have before coding

### 1. Source code of `/00`
Upload or push the full local folder into this repo, e.g.:

```text
legacy/00/          # current PHP scripts (all cron + managers)
legacy/theme/       # active WP theme (or zip)
legacy/plugins/     # custom plugins only (not all of wp-content/plugins)
```

Preferred: zip `D:\codz\workspace\buycarl.com\00` and attach it in Cursor, **or** push it to this GitHub repo yourself.

Also include any prior review notes / docs you mentioned (“previous review on repositories buycarl.com”).

### 2. Database structure + sample data
Provide **one** of:
- phpMyAdmin → Export structure-only SQL for racing tables (preferred), **or**
- `SHOW CREATE TABLE` for every custom table, **or**
- A sanitized dump (no real user emails / payment data)

Especially needed:
- Horse / race / odds / barrier / score / blog-queue tables
- Mark Six tables
- Funds / Hang Seng tables (if kept)
- Any WP options / custom tables those crons write to

If export is large, structure-only + 1–2 days of sample race rows is enough.

### 3. Namecheap deploy access (for “every GitHub update → buycarl.com”)
Pick **one** deploy path and send credentials securely (do **not** paste passwords into git):

| Option | What to provide | Notes |
|--------|-----------------|-------|
| **A. Recommended** GitHub Actions → FTP/SFTP | FTP host, username, password, remote path (usually `public_html`) | Auto-deploy on every push to `main` |
| **B.** cPanel Git Version Control | cPanel login or SSH + repo path on server | Pull on push webhook |
| **C.** Manual | Confirm you will pull yourself | Agent pushes GitHub only |

Also confirm:
- Document root path (`public_html` vs subdomain)
- Whether `/00` stays at `buycarl.com/00` or moves to e.g. `buycarl.com/racing-api`
- PHP version you can upgrade to (8.1+ strongly preferred; site is on 7.4)

### 4. Secrets / config that are hard-coded today
List or export (as env values, not committed):
- DB host / name / user / password (or `wp-config.php` constants for custom DB)
- Any HKJC / scraper cookies, API keys, User-Agent tokens
- Mail credentials used by `cron_*.php?mail=1`
- cron-job.org API key (optional; for admin “list jobs” sync)
- WordPress Application Password (admin) for REST + plugin install tests

### 5. Product scope decisions (short answers)
Reply with yes/no or a choice:
1. Keep WordPress store + replace only racing frontend/backend under `/00`? **(recommended)**  
   or rebuild whole site off WordPress?
2. Keep Mark Six + Funds modules in v1, or racing-only first?
3. Public language: Chinese-first, bilingual, or English store + Chinese racing?
4. Admin UI: WP admin pages, or separate `/admin` app?
5. Which “previous review” file/path should we treat as the source of truth?

---

## Priority 2 — Nice to have (speeds up UI fix)

- Screenshots of the overcrowded race page (desktop + mobile)
- List of hard-coded constants you already know (weights, score thresholds, barriers, blog templates)
- Desired information groups for the race day page (example target layout):

```text
Header: date | venue | race count | last sync
Toolbar: race tabs R1…Rn | filters
Per race card (dense):
  [No] [Horse] [Jockey] [Draw] [Odds] [Score] [Signal]
Expand row → form / barrier / history (not all open by default)
Footer actions: refresh | export | admin settings link
```

---

## What I will build once Priority 1 is in place

1. **Backend admin** (settings + cron registry + constants editor — no more magic numbers in PHP)
2. **Professional racing frontend** (Tailwind + interactive JS): dense grouped layout, less padding, one-page readable data
3. **Custom WP theme/plugin** (or keep store theme and ship a racing plugin) wired to the same DB
4. **GitHub → Namecheap auto-deploy** on each update
5. **Cron strategy** — inventory in admin; migrate safe jobs to hosting; keep heavy/external ones on cron-job.org if needed (see `CRON_INVENTORY.md`)

---

## Security / hosting constraints (Namecheap shared)

- Shared hosting: limited long-running PHP, process timeouts, no always-on workers
- Cron: cPanel cron is fine for short jobs; long scrapers often still need cron-job.org with staggered schedules
- Every cron URL must require `?token=...` (or WP nonce / signed HMAC)
- Do not commit `wp-config.php`, FTP passwords, or DB dumps with PII

---

## Your next message — paste this filled in

```text
[ ] Source: attached zip / pushed to GitHub path: ___
[ ] DB: structure SQL attached / phpMyAdmin export path: ___
[ ] Deploy: A FTP  /  B cPanel Git  /  C manual
    host: ___  user: ___  remote path: ___
[ ] Scope: WP+racing plugin  /  full rewrite
[ ] Modules v1: racing  /  racing+M6  /  racing+M6+funds
[ ] Prior review file: ___
[ ] PHP target: 7.4 keep  /  8.1+ upgrade OK
```

After that, implementation starts on branch `cursor/buycarl-rewrite-setup-ca2a` (and follow-up feature branches).
