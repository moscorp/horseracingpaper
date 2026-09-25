# horseracingpaper / Horse Racing Paper

Rewrite of **buycarl.com** → Chinese racing site **Horse racing paper**（賽馬報紙）.  
Hosting: Namecheap `/home/fengrmkw/buycarl.com`. Deploy: GitHub Actions → FTP/SFTP.

## Docs

1. [ARCHITECTURE.md](./ARCHITECTURE.md) — locked product decisions + WP-shell recommendation  
2. [SETUP_CHECKLIST.md](./SETUP_CHECKLIST.md) — what to upload next  
3. [CRON_INVENTORY.md](./CRON_INVENTORY.md) — cron-job.org vs hosting

## Status

**Do not upload `wp-content.zip` (6.9G).** See [docs/LEGACY_TRANSFER.md](./docs/LEGACY_TRANSFER.md).

Next for you:
1. Attach only `fengrmkw_moosay.sql` (113KB) in chat  
2. Confirm GitHub secrets include `FTP_USERNAME` (not only `FTP`)  
3. Run Actions → **Fetch legacy from FTP**  
4. Add Cursor FTP secrets if prompted (agent cannot read GitHub secret values)
