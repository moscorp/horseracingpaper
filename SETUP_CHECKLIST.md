# Setup status — Horse Racing Paper

## Resolved (your answers)

- [x] No store — racing frontpage named **Horse racing paper**
- [x] Chinese only
- [x] Modules: racing + Mark Six + Funds (Funds = backend email)
- [x] Deploy: GitHub Actions → FTP/SFTP → `/home/fengrmkw/buycarl.com`
- [x] Repo: `moscorp/horseracingpaper`
- [x] PHP 8.1 after rewrite is live (keep 7.4 compatible until then)
- [x] Admin: professional dense console under WP Admin
- [x] Stack advice: **keep WordPress as CMS shell** for blog/cron/admin; remove store; rebuild racing UI/theme/plugin (see `ARCHITECTURE.md`)
- [x] Prior review chat not available here — code + SQL become source of truth

## Blocked — upload these from your PC

This agent **cannot** open `D:\codz\workspace\buycarl.com\`. Attach or push:

| File / folder | Why |
|---------------|-----|
| `wp-content.zip` | Theme + plugins to rewrite |
| `fengrmkw_moosay.sql` | Table structure |
| `00/` (zip of racing PHP) | Cron managers, scrapers, blog publishers |
| FTP host + user (+ password as GitHub/Cursor secret) | Auto-deploy |

### How to upload (pick one)

**A. Cursor chat (simplest)**  
Attach `wp-content.zip`, `fengrmkw_moosay.sql`, and `00.zip` to your next message in this agent.

**B. Git push from your PC**

```bash
cd D:\codz\workspace\buycarl.com
git clone https://github.com/moscorp/horseracingpaper.git
cd horseracingpaper
mkdir -p legacy
copy path\to\wp-content.zip legacy\
copy path\to\fengrmkw_moosay.sql legacy\
# zip the 00 folder into legacy\00.zip as well
git checkout -b cursor/legacy-import
git add legacy
git commit -m "Import legacy wp-content, SQL, and /00"
git push -u origin cursor/legacy-import
```

**C. GitHub release / large file**  
If zip is huge, upload to a private Drive/Dropbox link and paste the URL (or use Git LFS).

## After upload

1. Map all custom tables from SQL  
2. Inventory theme templates + hard-coded constants  
3. Scaffold `horse-racing-paper` theme + plugin  
4. Dense Chinese race UI + admin console  
5. Cron registry + blog sequence fixes  
6. GitHub Actions FTP deploy workflow  
