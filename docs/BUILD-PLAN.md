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
6. **Interviewer feedback (2026-09-17)** — (a) the gate blocked the very pages its copy links to;
   `privacy.php`/`terms.php` are now exempt and show a non-modal sticky consent bar instead
   (`Consent::GATE_EXEMPT_PAGES`, `dialogState()['banner']`). (b) "No try/catch blocks": failures
   are now caught where they happen — accept writes its row before the cookie and fails closed,
   decline is always honoured, the contact form and admin forms show inline errors, admin read
   pages and export render `Smg\ErrorPage` with a 503. The global exception handler stays as a
   backstop. Spec: `docs/superpowers/specs/2026-09-17-interviewer-feedback-design.md`. Audit
   writes on sign-in/sign-out are best-effort (they must never block access), while viewing a
   consent record and exporting CSV fail closed — no audit row, no personal data shown.

## Status

- [x] Step 0 — orient (this doc)
- [x] Step 1 — scaffold + tokens (verified: schema imported, PDO connection smoke-tested live)
- [x] Step 2 — shared chrome (verified: desktop + mobile hamburger screenshots)
- [x] Step 3 — consent gate (verified: all 6 states via curl/DB, focus trap + Esc/backdrop +
      inert via Playwright, mobile bottom sheet screenshot)
- [x] Step 4 — the four pages (verified: all 4 pages at 375/768/1440, contact form validation
      + success + DB row + real mail() delivery, live consent-record callout on legal pages)
- [x] Step 5 — admin portal (verified: auth guard, login, dashboard stats/search/pagination,
      CSV export content, 5/min rate limit at the 6th attempt, session_regenerate_id on login)
- [ ] Step 4 — the four pages
- [ ] Step 5 — admin portal
- [x] Step 6 — harden and verify. Static audit: every DB call is a prepared statement (one
      remaining ->query() converted), every echo of user/DB data confirmed escaped (one gap
      found in the CSV export link and fixed), CSRF confirmed enforced + rejection tested on
      all 3 POST endpoints, cookie flags consistent everywhere, zero raw hex in site.css, fresh
      schema.sql import verified, git history confirmed secret-free. Dynamic: all 6 consent
      states re-verified against the real pages (not the old harness) on all 4 public pages,
      manage-reopen dismissibility confirmed, no-JS path is what curl exercises throughout.
      Fixed 2 real bugs: a mobile horizontal-overflow on the admin search/export controls, and
      confirmed (via network trace) that fputcsv() deprecation warnings from step 5 don't
      recur. One false alarm investigated and ruled out (header nav at 768px looked tight in a
      screenshot but has a full 24px margin on inspection).
- [x] Step 7 — deliverables. Finalized root README.md (setup steps, admin seeding, local email
      testing, consent cookie + DB design rationale) and verified it end-to-end against a true
      fresh `git clone` of the pushed repo — schema import, config copy, server start, admin
      seed, and every route all worked exactly as documented, with a clean server log. Fixed
      two README issues while at it: it referenced `docs/handoff/` (gitignored, wouldn't exist
      for a cloner) and used an SSH clone URL (assumes the reviewer's own SSH key access) —
      both corrected. git init/commits happened incrementally per step rather than one final
      commit, per your direction.
- [x] Post-submission — homepage revamp (bonus, beyond the graded core). Real photos, animation,
      and a moving carousel on `index.php` only; the other 3 pages and admin stay unchanged.
      **Content sourcing**: fetched `starmediagroup.my` directly rather than inventing facts —
      real founding year (1971, from company registration 197101000523 (10894-D), giving an
      accurate "55 years" instead of my earlier guessed "54"), real portfolio of ~17 actual
      brands/platforms for the "family of brands" marquee (not third-party logos, which would
      have falsely implied a partnership that doesn't exist — their own corporate page confirms
      no partners are publicly listed), and the real Reuters Institute Digital News Report 2025
      recognition used as credibility content instead of a fabricated testimonial.
      **Assets**: real SMG logo (`smg-logo.png`, user-supplied) replaces the ★ text-glyph
      everywhere. Generated a second variant (`smg-logo-on-ink.png`) via a one-off GD script
      recoloring the gray "MEDIA GROUP" wordmark — sampled its actual color and measured ~3.2:1
      contrast against the dark header, below WCAG AA; the derived variant fixes this. Caught and
      fixed a real pre-existing bug in the same pass: the admin topbar's "Consent Admin" label
      was dark-on-dark (invisible) due to a wrongly-applied CSS modifier from step 5.
      **Architecture**: new `home.css`/`home.js`, loaded only on `index.php`. Every interactive
      piece degrades with JS off — carousels are native CSS scroll-snap (dots/arrows are
      JS-injected on top, never present-but-broken in raw HTML — verified via curl), scroll-reveal
      defaults to fully visible (only hidden once an inline script adds `html.js`, which never
      runs without JS), stat count-up reads its real final value from the server-rendered HTML.
      Verified live: carousel navigation (arrow clicks move the track and update the active dot),
      auto-advancing recognition carousel, marquee, count-up, reveal animations, mobile stacking
      at 375px, zero horizontal overflow, zero console errors, and the full no-JS HTML via curl.
      Hero/platform/about photos are wired to check for the file and render nothing if absent
      (`smg_asset_or_placeholder()`) — pending real photo files.
- [x] Post-submission — real photos, moved-up marquee, fixed a real scroll bug, humanized copy.
      **Photos**: retrieved directly from starmediagroup.my's live HTML (not invented) — the real
      HQ building (aerial photo with their rooftop signage, cropped for the hero), a real Star
      Education Fund scholarship photo (About teaser), a real Star Outstanding Business Awards
      event photo (the "Events & video" platform panel), and all 17 real brand logos for the
      marquee. The brand logos were flat JPEGs with solid white backgrounds; wrote a second GD
      script to convert them to transparent PNGs (feathered edge, not a hard cutout) so they float
      cleanly on the dark marquee band instead of showing white boxes. Redesigned the platform
      carousel into two panel variants (`--photo`: full-bleed + gradient scrim, for the one real
      photo; `--logo`: light background + contained mark + caption below, for the 3 brand-logo
      panels) rather than forcing one treatment on mismatched content types.
      **Layout**: moved the marquee to right after the hero (was after the stats strip) so it's
      visible without scrolling on first load, per your feedback.
      **Bug found and fixed**: the recognition carousel's 5-second auto-advance used
      `scrollIntoView()`, which scrolls the whole page vertically when its target isn't already
      in the viewport, not just the carousel horizontally — so the page would jump down on its
      own shortly after opening. Fixed by scrolling the track element directly
      (`track.scrollTo({left: panel.offsetLeft})`) in both carousels, and gated the auto-advance
      behind an IntersectionObserver so it only runs while the section is actually visible.
      **Copy**: removed every em dash from visible page copy (about/privacy/terms/legal-page too,
      not just the homepage) and rewrote the affected sentences in plainer, more conversational
      language rather than just swapping punctuation.
- [x] Post-submission — admin portal upgrade (v1.2, see `docs/ROADMAP.md` for the full scoped
      checklist). Login form: password show/hide toggle, field-level error states, live
      rate-limit countdown, submit loading spinner, entrance animation, real HQ photo background.
      Dashboard: `admin/record.php` detail page, status/date-range filters shared between the
      table and CSV export via a new `src/ConsentQuery.php` (avoids the two drifting out of
      sync), sortable columns, a 14-day CSS-bar trend chart, `src/AuditLog.php` +
      `admin/audit.php`, and `admin/change-password.php`.
      **Two real bugs found and fixed**: (1) MySQL's session `time_zone` defaulted to the
      server's system zone (UTC+8 here) rather than UTC, so any `TIMESTAMP` column
      (`login_attempts.attempted_at`, etc.) read back and parsed as UTC was off by the server's
      offset — surfaced by the new rate-limit countdown showing "28833s" instead of a sane
      value. Fixed at the root by pinning the PDO connection's session time zone to `+00:00`
      (version-safe across the PHP 8.4+ `Pdo\Mysql::ATTR_INIT_COMMAND` rename). (2) Assigning
      `.hidden` on an SVG element didn't reliably work in this environment — the password-toggle
      button's input-type switch worked but the icon swap silently failed; fixed via
      `setAttribute`/`removeAttribute('hidden')` instead. Also caught and fixed two design
      regressions during verification: GUID cells inheriting the global red link color across
      an entire 20-row column (now ink, red only on hover), and a broken mobile topbar layout
      once "Audit log"/"Change password" links were added (now stacks instead of wrapping badly).
      Verified live throughout: real login/failed-login/rate-limit flows, filter+sort+pagination
      against 40 seeded rows, the record detail page's IP/user-agent decoding, real audit-log
      capture, and change-password confirmed by logging back in with the new password.
