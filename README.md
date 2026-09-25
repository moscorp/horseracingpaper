# horseracingpaper / BuyCarl

Rewrite workspace for **buycarl.com** (Namecheap shared hosting + WordPress + `/00` racing tooling).

## Start here

1. Read [SETUP_CHECKLIST.md](./SETUP_CHECKLIST.md) — credentials, source, DB, deploy
2. Read [CRON_INVENTORY.md](./CRON_INVENTORY.md) — cron-job.org vs hosting recommendation

## Current status

- GitHub repo initialized
- Live site surveyed (WordPress / PHP 7.4 / LiteSpeed / Cloudflare)
- Local legacy path `D:\codz\workspace\buycarl.com\00` **not yet uploaded**
- Implementation blocked on Priority 1 items in the setup checklist

## Target stack (planned)

- WordPress store kept (unless you choose full rewrite)
- Custom racing plugin + dense Tailwind/JS frontend
- Backend admin: settings/constants + cron registry
- GitHub Actions → Namecheap FTP/SFTP on every `main` update
