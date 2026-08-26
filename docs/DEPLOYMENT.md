# Deploying to Railway

This is a runbook, not something already deployed — it walks through getting this repo running
on [Railway](https://railway.app) using the `Dockerfile` and `src/Config.php`'s environment-variable
config path already in this repo (see `docker-compose.yml` for the equivalent local setup, which
exercises the exact same code path).

## Prerequisites
- A Railway account under **marwanbukhori's own personal account, never any employer/company
  account** — this project isn't company work, and any Railway CLI/MCP session used for it must
  be authenticated as the personal account specifically.
- This repo pushed to a GitHub repository Railway can access

## 1. Create the project and database

1. In Railway, **New Project → Deploy from GitHub repo** → select this repository.
2. Railway will detect the `Dockerfile` at the repo root and build from it automatically — no
   Nixpacks/buildpack config needed.
3. **New → Database → Add MySQL** in the same project. Railway provisions a managed MySQL 8
   instance and automatically injects `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`,
   and `MYSQLPASSWORD` into any service you link it to.
4. Open the app service's **Variables** tab → **Add Reference** → link the MySQL service's
   variables. `src/Config.php` reads exactly these names, so no manual mapping is needed.

## 2. Import the schema

Railway's MySQL plugin starts empty — `db/schema.sql` isn't auto-imported the way
`docker-compose.yml` does it locally. From the MySQL service's own **Data** tab, open the query
console and paste the contents of `db/schema.sql`, or connect with the CLI credentials Railway
shows on that tab:
```bash
mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE> < db/schema.sql
```

## 3. Seed an admin user

Railway's CLI can run a one-off command against the deployed service with its real env vars:
```bash
railway run php bin/seed-admin.php
```
(Install the CLI first: `npm i -g @railway/cli`, then `railway login` and `railway link` to
this project.) This prompts for a username/password exactly like the local version.

## 4. Optional variables

These aren't required — the app has sane defaults — but are worth setting for a real deployment
(**Variables** tab, plain key/value, no linking needed):

| Variable | Default | Purpose |
|---|---|---|
| `MAIL_TO_ADDRESS` | `contact@starmediagroup.example` | Where the contact form's notification email goes |
| `MAIL_FROM_ADDRESS` | `no-reply@starmediagroup.example` | From address on outgoing mail |
| `MAIL_FROM_NAME` | `Star Media Group` | From name on outgoing mail |
| `ADMIN_SESSION_NAME` | `smg_admin_session` | Admin session cookie name |
| `ADMIN_LOGIN_RATE_LIMIT` | `5` | Max admin login attempts per IP per 60s |
| `APP_TIMEZONE` | `Asia/Kuala_Lumpur` | Display timezone (the DB always stores UTC) |

## 5. Verify after deploy

- Visit the Railway-assigned `*.up.railway.app` URL — the consent dialog should appear on first
  visit, and accepting/declining should redirect back correctly.
- Check that the `smg_consent` cookie actually gets set: Railway serves everything over HTTPS at
  its edge, and `Consent::isSecureContext()` detects this via the `X-Forwarded-Proto: https`
  header the edge proxy adds (Railway's containers receive plain HTTP internally, so
  `$_SERVER['HTTPS']` alone would never see it). If the cookie doesn't stick, that detection is
  the first thing to check — Railway's proxy setup is what makes it work, not a container config.
- Sign in at `/admin/login.php` with the user seeded in step 3.
- Submit the contact form and confirm the row lands in `contact_messages` — see the mail
  limitation below before expecting an actual email.

## Known limitation: outgoing mail

`src/Mailer.php` uses PHP's native `mail()`, which needs a local MTA (`sendmail`) configured on
the host. The `php:8.2-apache` base image this Dockerfile uses doesn't ship one, so `mail()`
will silently fail on Railway — the contact form still validates and persists every submission
to `contact_messages` regardless (that's the existing design, not new fallback behavior added
for this), so no data is lost, but no notification email will actually arrive. Wiring real
delivery would mean either configuring an MTA in the image or switching to an SMTP-based mailer
(e.g. PHPMailer via Composer) — out of scope here since the app currently has zero runtime
dependencies by design (see `CLAUDE.md`); tracked as a `v1.1+` item in `docs/ROADMAP.md` if this
became a real production handoff.

## Redeploying

Railway redeploys automatically on every push to the tracked branch — no extra steps beyond a
normal `git push`. Schema *changes* (not the initial import) still need to be applied manually
via the query console, the same as step 2, since there's no migration tool in this project
(`docs/ROADMAP.md` lists that as a possible `v1.1+` addition).
