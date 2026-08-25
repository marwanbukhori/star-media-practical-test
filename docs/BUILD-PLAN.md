# Build plan — Star Media Group consent site

Working plan for the practical test, following `PROMPTS.md`'s step sequence. Updated as
decisions get made; current status at the bottom.

For the v1.0-vs-future-enhancements view (unit/e2e/load testing, CI, post-submission feature
ideas), see `docs/ROADMAP.md`.

## File-by-file plan

**Config & foundation**
- `.gitignore` — `config.php`, `.DS_Store`, `*.log`
- `config.example.php` — DB credentials, mail sender, admin session/rate-limit settings,
  `app.force_https` (auto-detects HTTPS unless forced), `app.timezone` (display only)
- `config.php` — gitignored, created locally from the example
- `db/schema.sql` — `consent_log`, `admin_users` (from the handoff bundle) plus two additions:
  `login_attempts` (admin rate limiting) and `contact_messages` (About/Contact form persistence)
- `bin/seed-admin.php` — CLI script to create the first admin user (replaces `seed.sql`, since
  `password_hash()` has to run in PHP, not SQL)

**src/ (framework-free PHP classes)**
- `src/Db.php` — PDO factory, `ATTR_EMULATE_PREPARES => false`, exceptions on ✅
- `src/Csrf.php` — token generate/verify via `$_SESSION` ✅
- `src/Consent.php` — `CONSENT_VERSION` constant (single source of truth), cookie read/write,
  GUID v4 generation, accept/decline state machine, `consent_log` upsert — *in progress*
- `src/Auth.php` — `password_verify`, `session_regenerate_id(true)`, login rate limiting
  (queries `login_attempts`) — step 5
- `src/Mailer.php` — thin wrapper over PHP's native `mail()` for the contact form — step 4

**templates/**
- `header.php`, `footer.php` — shared chrome ✅ (now also accept `$consentGateOpen` to mark
  themselves `inert` while the dialog is open, so background content can't be tabbed into)
- `consent-dialog.php` — always rendered in the DOM (visible or `hidden`), so "Cookie settings"
  can reopen it instantly via JS without a page reload — *in progress*
- `legal-page.php` — shared partial for `privacy.php`/`terms.php` — step 4

**public/**
- `index.php`, `about.php`, `privacy.php`, `terms.php` — step 4
- `consent.php` — POST `accept|decline`, CSRF-checked, real form POST+redirect (no-JS) with a
  fetch-enhanced JSON path — *in progress*
- `assets/css/tokens.css` ✅ · `assets/css/site.css` — base/header/footer done ✅, dialog +
  button system being added now
- `assets/js/consent.js` — focus trap, scroll lock (with iOS Safari fix), Esc/backdrop rules,
  fetch-enhanced form, "Cookie settings" reopen hook — *in progress*

**public/admin/** (bonus) — step 5
- `login.php`, `index.php`, `logout.php`, `export.php`

## Assumptions

1. Cookie `Secure` flag is conditional on `$_SERVER['HTTPS']` (or `config.app.force_https`) so
   local HTTP testing on `localhost` still works; always Secure in a real HTTPS deployment.
2. DB stores UTC; the `smg_consent` cookie's `accepted_at` is formatted in MST (UTC+8) per the
   handoff spec's explicit field format. Display conversions elsewhere also target UTC+8.
3. Re-accepting via "Cookie settings" reuses the GUID from the existing cookie (updates the row)
   rather than minting a new visitor identity — that's what makes the `ON DUPLICATE KEY` upsert
   meaningful.
4. Declining is also logged to `consent_log` (`action='declined'`), each with a fresh GUID used
   only as a DB row key (never exposed to the client) — the README calls this a "defensible
   bonus" the schema already supports.
5. Legal body copy (About/Privacy/Terms) stays plausible placeholder text, not scraped real SMG
   policy — only the consent-dialog copy must be verbatim.
6. PHP 8.2+ only (installed 8.5.9 locally via Homebrew) — the PDF's "PHP 7 or 8" is superseded
   by CLAUDE.md's more specific floor.

## Decisions from the ambiguity review

1. **Admin login rate limit (5/min)** — added `login_attempts` (`ip_address`, `attempted_at`) to
   `schema.sql` rather than session/file-based limiting, so it survives across PHP processes.
2. **"Cookie settings" reopen** — industry-standard pattern: the dialog markup is always present
   in the page (hidden via the `hidden` attribute when not needed). JS reopens it instantly with
   no page reload (matches OneTrust/Cookiebot-style CMPs) and marks it dismissible (Esc/backdrop
   click close it, since a valid decision already exists). The no-JS path is a real link to
   `?consent=manage`, which the server honors by rendering the page with the dialog forced open,
   scroll-locked, and dismissible — same mechanism, no JS required. Background content
   (`header`/`footer`/`main`) gets `inert` — server-rendered when the gate forces itself open,
   JS-toggled when reopened voluntarily — so keyboard focus can't leak into the page behind the
   modal either way.
3. **IP capture** — direct `$_SERVER['REMOTE_ADDR']`, no reverse-proxy `X-Forwarded-For` trust
   (local practical test, not behind a load balancer).
4. **Contact form sends email** — PHP's native `mail()` (no Composer/PHPMailer, keeping with
   "no dependency unless asked"). Every submission also persists to `contact_messages` so
   nothing is lost if local mail delivery isn't configured. README documents pointing
   `sendmail_path` at Mailpit/MailHog for local capture.
5. **Admin CSV export is filterable** — honors the same `?q=` GUID search param as the table.

## Status

- [x] Step 0 — orient (this doc)
- [x] Step 1 — scaffold + tokens (verified: schema imported, PDO connection smoke-tested live)
- [x] Step 2 — shared chrome (verified: desktop + mobile hamburger screenshots)
- [x] Step 3 — consent gate (verified: all 6 states via curl/DB, focus trap + Esc/backdrop +
      inert via Playwright, mobile bottom sheet screenshot)
- [x] Step 4 — the four pages (verified: all 4 pages at 375/768/1440, contact form validation
      + success + DB row + real mail() delivery, live consent-record callout on legal pages)
- [ ] Step 4 — the four pages
- [ ] Step 5 — admin portal
- [ ] Step 6 — harden and verify (all 6 consent states, no-JS path)
- [ ] Step 7 — deliverables (finalize README, git init, one clean commit)
