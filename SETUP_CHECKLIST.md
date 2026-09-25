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

## Blocked — slim transfer only (see `docs/LEGACY_TRANSFER.md`)

This agent **cannot** open `D:\codz\workspace\buycarl.com\`.

| Item | Do this |
|------|---------|
| `wp-content.zip` 6.9G | **Do not upload** |
| `/00` 171MB zip | **Do not upload to chat/GitHub** — pull via FTP Action |
| `fengrmkw_moosay.sql` 113KB | **Attach in chat** (fits under 25MB) |
| FTP secrets | Already partly added — ensure `FTP_USERNAME` exists |

### Steps

1. Attach `fengrmkw_moosay.sql` to the next chat message  
2. Repo secrets: `FTP_SERVER`, `FTP_PORT`, `FTP_USERNAME`, `FTP_PASSWORD`  
3. GitHub → Actions → **Fetch legacy from FTP** → Run  
4. Reply here when the run is green (or add Cursor FTP secrets so the agent can pull directly)

## After legacy lands

1. Map tables from SQL  
2. Inventory theme + `/00` constants  
3. Scaffold theme + plugin  
4. Dense Chinese UI + admin console  
5. Cron registry + blog sequence fixes  
6. Deploy workflow (push → FTP)  
