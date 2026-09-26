# Activate theme & plugin on buycarl.com

After deploy (Actions → **Deploy theme & plugin to Namecheap**, or merge to `main`):

## One-time activation (you)

1. WordPress admin → **外觀 → 佈景主題** → activate **Horse Racing Paper**
2. **外掛** → activate **Horse Racing Paper**
3. **設定 → 閱讀**：首頁顯示可維持「最新文章」或靜態頁；主題 `front-page.php` 會顯示賽事紙 + 最新分析
4. Optional: **設定 → 一般** 將網站標題改為「賽馬報紙」
5. Open **賽馬報紙** admin menu → confirm「最近賽日」shows data from `hkracing_*`
6. Visit https://buycarl.com/ — dense race table should load

## Security (important)

Legacy `lib/constants.php` previously contained live DB / WP app / OpenWA secrets and was scrubbed in git.
**Rotate** those credentials in cPanel / OpenWA if the repo is or was public:
- DB user password for `fengrmkw_melvin`
- WordPress application password
- OpenWA API key / session

Create `00/lib/constants.local.php` on the server (not in git) for legacy cron scripts.

## You do NOT need to activate until deploy finishes

Local GitHub code alone does not change the live site. Activate only after the deploy workflow succeeds (or you manually upload `wp-content/themes/horse-racing-paper` and `wp-content/plugins/horse-racing-paper`).
