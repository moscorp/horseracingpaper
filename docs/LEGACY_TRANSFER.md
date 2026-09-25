# How to get legacy code into this project (no 6.9GB upload)

## Short answers

| Question | Answer |
|----------|--------|
| Do I upload `wp-content.zip` (6.9G) to GitHub? | **No.** Chat/GitHub limits (~25–100MB). Most of that zip is `uploads` / cache / unused plugins. |
| Can the Cloud Agent read `D:\codz\...` on my PC? | **No.** Only this Linux VM + GitHub + what you attach / FTP. |
| What should I attach in chat? | Only **`fengrmkw_moosay.sql` (113KB)** — under 25MB. |
| How do we get theme + `/00`? | **FTP pull from the live server** using the secrets you already added. |

## GitHub Actions secrets (confirm these exact names)

Repo → Settings → Secrets and variables → Actions:

| Secret name | Value |
|-------------|--------|
| `FTP_SERVER` | `ftp.fengins.com` |
| `FTP_PORT` | `21` |
| `FTP_USERNAME` | `buycarlftp@buycarl.com` |
| `FTP_PASSWORD` | *(your password)* |
| `FTP_SERVER_DIR` | site root as seen by FTP (often `/` or `/buycarl.com` or `/home/fengrmkw/buycarl.com`) |

If you created a secret literally named `FTP`, rename or add **`FTP_USERNAME`** — workflows expect `FTP_USERNAME`.

Optional for this Cloud Agent (so it can FTP without waiting on Actions): add the same `FTP_PASSWORD` / `FTP_USERNAME` as **Cursor environment secrets** when prompted.

## What we pull from FTP (slim)

```text
/00/                              # racing PHP (exclude huge caches if any)
wp-content/themes/                # themes only
wp-content/plugins/               # plugins only (still large — we may filter further)
NOT: wp-content/uploads/
NOT: cache, node_modules, .git
```

Workflow: `.github/workflows/fetch-legacy-ftp.yml`  
Trigger: Actions → “Fetch legacy from FTP” → Run workflow.

## After the workflow finishes

Tell the agent “FTP fetch done” (or re-ping this chat). The agent will download the workflow artifact with `gh run download` and start the rewrite.

## Still attach in chat (small)

`fengrmkw_moosay.sql` — 113KB — attach to the next message.
