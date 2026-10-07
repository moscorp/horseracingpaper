# Activate theme & plugin on buycarl.com

After deploy (Actions → **Deploy theme & plugin to Namecheap**, or merge to `main`):

## One-time activation (you)

1. WordPress admin → **外觀 → 佈景主題** → activate **Horse Racing Paper**
2. **外掛** → activate **Horse Racing Paper**
3. **設定 → 閱讀**：首頁顯示可維持「最新文章」或靜態頁；主題 `front-page.php` 會顯示賽事紙 + 左側階層選單
4. Optional: **設定 → 一般** 將網站標題改為「自由馬紙」
5. Open **自由馬紙** admin menu → **設定**：
   - 上傳站徽 Logo / Favicon（可改掉主題內建預設）
   - 確認側欄四個分類名稱（賽事結果 / 賽事分析 / 六合彩結果 / 六合彩分析）
   - 編輯關於我們 / 私隱政策 / 使用條款（儲存後同步到 `about` / `privacy` / `terms` 頁面）
6. Confirm「最近賽日」shows data from `hkracing_*`
7. Visit https://buycarl.com/ — dense race table + left nav should load

## Tailwind

Frontend + HRP admin use compiled Tailwind CSS (`npm run build:css` in repo root). Deployed CSS lives under theme/plugin `assets` / `admin/css` / `public/css`. No Node required on the server.

## Security (important)

Legacy `lib/constants.php` previously contained live DB / WP app / OpenWA secrets and was scrubbed in git.
**Rotate** those credentials in cPanel / OpenWA if the repo is or was public:
- DB user password for `fengrmkw_melvin`
- WordPress application password
- OpenWA API key / session

Create `00/lib/constants.local.php` on the server (not in git) for legacy cron scripts.

## You do NOT need to activate until deploy finishes

Local GitHub code alone does not change the live site. Activate only after the deploy workflow succeeds (or you manually upload `wp-content/themes/horse-racing-paper` and `wp-content/plugins/horse-racing-paper`).

## If single posts fatal: `getPostViews()`

Cause: **AliDropship (alids)** blog template still renders singles and calls `getPostViews()`, which lived in the old theme.

Fix (plugin ≥ 0.2.1): Horse Racing Paper defines `getPostViews` / `setPostViews` compatibility shims. Redeploy plugin, then reload the post.

Optional later: disable alids “blog template” / use our theme `single.php` only, so store plugin stops owning race-analysis posts.
