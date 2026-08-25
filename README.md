# Star Media Group — 4-Page Consent Site

A practical-test build in PHP 8.2+ and MySQL 8, no framework, no build step. Four public
pages (Home, About/Contact, Privacy Policy, Terms & Conditions) with a blocking first-visit
cookie-consent gate recorded to both a cookie and the database, plus a secured admin portal
for reviewing consent acceptances.

## What's implemented

- **4 public pages** — Home, About/Contact (with a working contact form), Privacy Policy and
  Terms & Conditions (one shared template)
- **Consent gate** — blocking on first visit, works with JavaScript fully disabled (real form
  POST + redirect), scroll-locked server-side with no flash, reappears on cookie expiry or a
  bumped notice version
- **Contact form** — server-side validated, sends via PHP's native `mail()`, persisted to the
  database regardless of delivery outcome
- **Admin portal** (bonus) — authenticated dashboard with stat cards, a searchable/paginated
  consent table, and a filterable CSV export
- Responsive at 375 / 768 / 1440px throughout

## Prerequisites
- PHP 8.2+ with the `pdo_mysql` extension
- MySQL 8

## Local setup
```bash
git clone https://github.com/marwanbukhori/star-media-practical-test.git
cd star-media-practical-test

# 1. Database
mysql -u root -p < db/schema.sql

# 2. Config
cp config.example.php config.php
# edit config.php with your DB credentials

# 3. Run
php -S localhost:8000 -t public
```
Visit `http://localhost:8000`.

## Create an admin user
```bash
php bin/seed-admin.php
```
Prompts for a username and password (min. 8 characters) and inserts the bcrypt hash into
`admin_users`. Then sign in at `http://localhost:8000/admin/login.php`.

## Local email testing
The contact form sends via PHP's native `mail()` — no Composer dependency. To capture outgoing
mail locally without a real SMTP server, point `sendmail_path` in `php.ini` (or via
`php -S -d sendmail_path=...`) at a local capture tool such as
[Mailpit](https://github.com/axllent/mailpit) or MailHog. Every submission is also persisted to
the `contact_messages` table regardless of whether delivery succeeds, so nothing is lost if
mail isn't configured.

## Project structure
```
public/                 index.php  about.php  privacy.php  terms.php  consent.php
public/admin/           login.php  index.php  logout.php  export.php
public/assets/css/      tokens.css  site.css
public/assets/js/       consent.js
src/                    Db.php  Csrf.php  Consent.php  Auth.php  Mailer.php
templates/              bootstrap.php  admin-bootstrap.php  header.php  footer.php
                        consent-dialog.php  legal-page.php
db/                     schema.sql
bin/                    seed-admin.php
config.example.php  .gitignore  README.md
```

## Design decisions

### The consent cookie
- **Two cookies, two purposes.** `smg_consent` (365-day) holds `{ guid, accepted_at, version }`
  as JSON; `smg_consent_declined` (1-day) just marks that a decline happened. Both are
  `HttpOnly` — the server alone decides whether to show the dialog, so JavaScript never needs
  to read them.
- **GUID v4 via `random_bytes(16)`** with the version/variant bits set correctly (never
  `uniqid()`, which is predictable and not suitable as an identifier here).
- **`Secure` is conditional on the request actually being HTTPS** (`$_SERVER['HTTPS']`, or
  `config.app.force_https` to override). A hardcoded `Secure` flag would silently stop the
  cookie from being set at all on plain local HTTP — a nasty local-only bug that's easy to miss
  and confusing to debug once you hit it.
- **Re-accepting reuses the existing GUID** rather than minting a new one. If you've already
  accepted and later reopen "Cookie settings" to reconfirm, the `consent_log` row is updated
  (`ON DUPLICATE KEY UPDATE` on the unique `guid`), not duplicated — the GUID identifies a
  browser's consent decision, not a fresh event each time.
- **Declining also logs to `consent_log`** (`action = 'declined'`, with its own throwaway GUID
  used only as a DB row key). Not required by the spec, but the schema already supports it and
  it's a small, defensible addition for anyone auditing decline volume later.
- **Version bump forces re-consent.** `CONSENT_VERSION` is a single PHP constant
  (`src/Consent.php`). If a visitor's cookie carries a lower version than the constant, the
  dialog reappears even though the cookie itself hasn't expired — the mechanism a senior
  reviewer would specifically check for.

### The database
- **UTC in the database, MST (UTC+8) for display and for the cookie's own `accepted_at` field**
  (per the functional spec). Storing UTC avoids timezone-conversion bugs in date range queries
  (e.g. the admin dashboard's "Today" stat); converting only at render time keeps that logic in
  exactly one place.
- **`login_attempts` and `contact_messages`** were added to the schema beyond what the original
  brief specified, to support the admin login rate limit (5/min, keyed by IP — needs a durable
  store, not a per-process counter) and contact-form persistence respectively.
- **IP addresses stored as `VARBINARY(16)` via `INET6_ATON()`**, handling both IPv4 and IPv6
  uniformly. No reverse-proxy (`X-Forwarded-For`) trust — this is a local/direct deployment,
  not behind a load balancer.
- **Every query is a prepared statement** (`PDO::ATTR_EMULATE_PREPARES => false`), including
  ones with no dynamic input, for consistency and to close off any future refactor accidentally
  reintroducing string-built SQL.

## Further reading
- `docs/BUILD-PLAN.md` — the file-by-file build plan, assumptions, and a verification log for
  every step
- `docs/ROADMAP.md` — what's in this v1.0 submission vs. what I'd add next (tests, CI, further
  features) if this became a real production handoff
