# BuyCarl Cron Inventory & Hosting Recommendation

Source list: jobs currently triggered via https://console.cron-job.org/jobs against `https://buycarl.com/00/...`

**Verdict (short):** Keep **cron-job.org** as the primary scheduler for scraping / batch / mail jobs on Namecheap shared hosting. Add a **backend admin Cron Registry** that lists every job, last run, status, and schedule. Use **cPanel cron** only for a few lightweight health/heartbeat tasks (and optionally as a backup trigger).

Why not move everything to Namecheap cron?
- Shared hosting kills long PHP requests (timeouts / CPU limits).
- Many of these jobs are scrapers + batch writers (odds, history, blogs) — they need staggered external HTTP triggers.
- cron-job.org already gives monitoring, retries, and independent scheduling.
- Hosting cron is better as a thin local watchdog, not as the only runner for 25 heavy jobs.

---

## Recommendation legend

| Tag | Meaning |
|-----|---------|
| **KEEP-EXTERNAL** | Stay on cron-job.org (primary) |
| **HOST-OK** | Short enough for cPanel cron if desired |
| **EITHER** | Both fine; prefer one place for ops simplicity |
| **REVIEW** | Likely redundant / merge / retire after code audit |
| **SECURE** | Must add secret token before any public hit |

All jobs below are **SECURE** today if they run without auth (confirmed: at least one sync script executes on bare GET).

---

## A. HKJC racing core (data + model)

| Job URL | Likely role | Recommendation |
|---------|-------------|----------------|
| `HKJCRacingDataManager_sync-v2.php` | Main race/horse sync queues (history + incremental) | **KEEP-EXTERNAL** — long batch; stagger; token auth |
| `HKJCHorseHistoryBatchManager_sync.php` | Horse history batch | **KEEP-EXTERNAL** — may overlap with v2; audit for **REVIEW** duplicate |
| `HKJCOddsManager_cron.php` | Odds ingest manager | **KEEP-EXTERNAL** — race-day frequent |
| `cron_odds2db.php` | Odds → DB | **REVIEW** vs OddsManager — merge if duplicate |
| `cron_updateoddsdrop.php` | Odds-drop / movement | **KEEP-EXTERNAL** on race days only |
| `cron_racecard2.php` | Race card scrape | **KEEP-EXTERNAL** |
| `cron_rsdata.php` | Racing stats / RS data | **KEEP-EXTERNAL** |
| `cron_barierresult.php` | Barrier / draw results | **KEEP-EXTERNAL** |
| `cron_proppreresult.php` | Prop / pre-result | **KEEP-EXTERNAL** |
| `cron_hrp_prophist2.php` | Prop history | **KEEP-EXTERNAL** / schedule off-peak |
| `cron_horseinfo2oversea.php` | Overseas horse info | **KEEP-EXTERNAL** / low frequency |
| `HKJCRacing_barrier_signal_cron.php?type=batch&force=1` | Barrier signals batch | **KEEP-EXTERNAL**; drop `force=1` as default |
| `HKJCRacing_update_priority_cron.php` | Priority updates | **EITHER** if short; else **KEEP-EXTERNAL** |
| `HKJCRacing_v2_score_cron.php?type=batch` | Scoring model batch | **KEEP-EXTERNAL** — CPU heavy |

## B. Content / WordPress publishing

| Job URL | Likely role | Recommendation |
|---------|-------------|----------------|
| `HKJC_blogpost.php?type=batch&force=1` | Auto race analysis posts | **KEEP-EXTERNAL**; protect write access |
| `HKJC_race_review.php?type=batch` | Post-race review posts | **KEEP-EXTERNAL** |
| `M6_blogpost.php?action=full_update` | Mark Six blog full update | **KEEP-EXTERNAL** |
| `M6_blogpost_update_yoast.php` | Yoast meta for M6 posts | **HOST-OK** if quick; else after `M6_blogpost` chain on cron-job.org |
| `M6_scraper_all.php` | Mark Six scrape all | **KEEP-EXTERNAL** |
| `cron_stheadline.php` | Sing Tao / headline scrape | **KEEP-EXTERNAL** |

## C. Mail / reports

| Job URL | Likely role | Recommendation |
|---------|-------------|----------------|
| `cron_6.php?mail=1` | Daily/periodic report mail | **KEEP-EXTERNAL** (mail + scrape combo often slow) |
| `cron_6Gann.php?mail=1` | Gann report mail | **KEEP-EXTERNAL** |

## D. Funds / Hang Seng (separate product surface)

| Job URL | Likely role | Recommendation |
|---------|-------------|----------------|
| `funds/cron_funds.php?mail=1` | Funds update + mail | **KEEP-EXTERNAL** (or pause if out of v1 scope) |
| `funds/cron_fund_hangseng.php` | Hang Seng fund scrape | **KEEP-EXTERNAL** / off-peak |
| `funds/cron_hsi_hangseng.php` | HSI data | **KEEP-EXTERNAL** |
| `funds/cron_hsi_meta.php` | HSI meta | **HOST-OK** if light |

---

## Suggested operating model (after rewrite)

1. **Single Cron Registry in backend admin**
   - Columns: name, URL path, schedule, provider (`cron-job.org` / `cpanel`), enabled, last HTTP status, last duration, last error, secret token id
   - “Run now” button (admin-only, CSRF protected)
   - Import list from this file / DB seed

2. **One authenticated runner**
   - `POST /wp-json/buycarl/v1/cron/{job}` or `/00/runner.php?job=odds&token=...`
   - Old public filenames become thin wrappers or 410 Gone after migration

3. **Provider split**
   - **cron-job.org:** scrapers, batches, mail, race-day high frequency
   - **cPanel cron (every 5–15 min):** heartbeat that records “scheduler alive” + optionally queues due jobs if external provider is down
   - Do **not** run the same heavy job from both places

4. **Race-day vs off-day profiles**
   - Odds / drop / card: aggressive on race day only
   - History / overseas / funds: off-peak nightly
   - Blog / review: after results settle

5. **Dedup pass (code review once `/00` is uploaded)**
   Likely overlaps to confirm:
   - `HKJCOddsManager_cron.php` vs `cron_odds2db.php` vs `cron_updateoddsdrop.php`
   - `HKJCRacingDataManager_sync-v2.php` vs `HKJCHorseHistoryBatchManager_sync.php`
   - `force=1` batch jobs vs normal batch — avoid double-publish

---

## Admin page mock fields (to implement)

```text
Cron Jobs
[Filter: racing | m6 | funds | content | mail]

Name                 Provider        Schedule        Last run     Status  Enabled
Racing sync v2       cron-job.org    */10 race-day   12:39 HKT    OK      [x]
Odds manager         cron-job.org    */5 race-day    12:40 HKT    OK      [x]
Blog post batch      cron-job.org    after card      09:12 HKT    OK      [x]
Heartbeat            cPanel          */15 * * * *    12:45 HKT    OK      [x]

[Add job] [Sync from cron-job.org API] [Export]
```

Constants that today live in PHP will move to Settings (score weights, thresholds, blog templates, mail flags) and be editable without deploy.

---

## Immediate actions for you (cron-job.org)

1. Export job list (name, URL, schedule, enabled) as CSV/JSON if the UI allows — attach here.
2. Add a shared secret query param to every job URL **now** (temporary), e.g. `?key=LONG_RANDOM`, and reject missing key in each script (or via `.htaccess`).
3. Disable `force=1` on schedules unless intentionally recovering; use force only manually.
4. Confirm which funds jobs are still wanted in production.
