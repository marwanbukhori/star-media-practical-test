# Star Media Group — 4-page consent site

## What this is
A practical-test deliverable: a 4-page website (Home/Overview, About-Contact, Privacy Policy,
Terms & Conditions) in **PHP 8 + MySQL 8**, with a blocking first-visit cookie-consent gate
recorded to both a cookie and the database, plus an optional secured admin portal.

Requirements: `docs/handoff/Practical Test - S. Web Developer.pdf` (gitignored — local reference only).
Design + full spec: `docs/handoff/README.md` — **read it before writing UI.**
Visual reference: `docs/handoff/Star Theme Kit.dc.html` (open in a browser).

## Stack rules
- PHP 8.2+, no framework, no Composer dependency unless asked. PDO for MySQL.
- Plain CSS: `tokens.css` (copied from the handoff, do not rewrite) then `site.css`.
  **Every colour, size and space comes from a `var(--smg-*)` token.** No new hex values.
- Vanilla JS only, progressive enhancement. Every flow must work with JS disabled.
- No build step. `php -S localhost:8000 -t public` must be enough to run it.

## Design rules
- Red (`--smg-red`) is a signal: max one red element per viewport on content pages.
  The consent dialog is the one exception, and so is the consent bar on privacy/terms (its
  Accept button only — the bar's border is ink).
- Square print aesthetic: `--smg-radius` (2px) on controls, 0 on cards. No emoji.
- Source Serif 4 for headings, IBM Plex Sans for body, IBM Plex Mono for eyebrows/metadata/GUIDs.
- Mobile: 44px minimum hit target, 48px on the consent buttons.
- **Photos and gradients are allowed on the homepage** (index.php) as of the homepage revamp —
  the original "no images/no gradients" rule came from the Star Theme Kit design handoff, which
  is a creative reference, not the actual graded requirement (the requirements PDF has no visual
  constraints at all). Real photos require actual image files (never fabricated/stock-implied as
  if real); real logos of third parties are never used to imply a partnership that doesn't exist
  — see `docs/BUILD-PLAN.md`'s homepage-revamp entry for how the brand marquee is sourced (Star
  Media Group's own real portfolio, not third-party trademarks). Gradients are functional only
  (e.g. a scrim behind photo captions for text legibility), built from `var(--smg-*)` tokens via
  `color-mix()`, not new arbitrary colors. The other 3 pages (about/privacy/terms) and the admin
  portal stay within the original no-photo, token-only system.

## Consent rules (the graded core — do not improvise)
- `CONSENT_VERSION = 1`, one constant. Dialog reappears if the cookie's version is lower.
- Accept: GUID v4 from `random_bytes(16)`; cookie `smg_consent` (JSON: guid, accepted_at, version),
  **365-day** expiry, `Path=/; Secure; HttpOnly; SameSite=Lax`; plus a `consent_log` row.
- Decline: cookie `smg_consent_declined` with the timestamp, **1-day** expiry, no row required.
- Accept writes the `consent_log` row **before** setting the cookie, so it fails closed: no row,
  no cookie. Decline always sets its cookie; a failed decline row is only logged.
- `privacy.php` and `terms.php` are exempt from the blocking gate (`Consent::GATE_EXEMPT_PAGES`)
  so visitors can read what they're consenting to. While a choice is pending they show a
  non-modal sticky consent bar with the same verbatim copy (`templates/consent-form.php`, shared
  with the dialog).
- Scroll lock must be applied **server-side** (`class="smg-locked"` on `<html>`) on gated pages so there is no
  flash of a scrollable page. JS removes it after the choice.
- Consent copy is verbatim from the requirements PDF — never reword it.
  [Terms & Conditions] → `terms.php`, [Privacy Statement] → `privacy.php`.

## Security baseline
Prepared statements only. `htmlspecialchars()` on every echo. CSRF token on the consent POST and
the admin login. `session_regenerate_id(true)` on login. `password_verify()` for auth.
Secrets in a gitignored `config.php`; commit `config.example.php`.
Catch `PDOException` where DB work happens and `error_log()` it; visitors only ever see an inline message or `Smg\ErrorPage`.

## Definition of done
All 4 pages responsive at 375 / 768 / 1440. Consent gate correct on first visit, after accept,
after decline, after each cookie expires, and with JS off. `README.md` at the repo root with
local setup steps, and `db/schema.sql` importable in one command.
