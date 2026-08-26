# Roadmap — Star Media Group consent site

`v1.0` is the practical-test submission: everything in the requirements PDF, the handoff
README, and CLAUDE.md's non-negotiables, and nothing more. Everything past that is scoped as
`v1.1+` — enhancements that would matter for a real production handoff, listed here so the
ambition is visible even though they're out of scope for the graded submission.

Step-by-step build execution and per-step verification live in `docs/BUILD-PLAN.md`. This file
is the higher-level "what's in v1.0 vs what's next" view.

---

## v1.0 — practical test submission

Everything required to satisfy the PDF (`docs/handoff/Practical Test - S. Web Developer.pdf`)
and the handoff spec (`docs/handoff/README.md`), built per `PROMPTS.md`'s step sequence.

- [x] **Step 1 — Scaffold + tokens.** Directory layout, `config.example.php`, `src/Db.php`
      (PDO, prepared statements only), `src/Csrf.php`, `db/schema.sql` imported.
- [x] **Step 2 — Shared chrome.** `templates/header.php` / `footer.php`, `site.css` (token-only),
      responsive header with no-JS hamburger.
- [x] **Step 3 — Consent gate.** `src/Consent.php`, `consent-dialog.php`, `consent.php`,
      `consent.js`. All 6 states, no-JS path, focus trap, `inert` background, correct cookie
      flags — see `docs/BUILD-PLAN.md` for the verification log.
- [ ] **Step 4 — The four pages.** `index.php`, `about.php` (contact form + email), `privacy.php`
      / `terms.php` off one shared legal template with the consent-record callout.
- [ ] **Step 5 — Admin portal (bonus, requested in the PDF).** Login (bcrypt, session
      regeneration, CSRF, rate limit), dashboard with stat cards + searchable/paginated consent
      table, filterable CSV export, `bin/seed-admin.php`.
- [ ] **Step 6 — Harden and verify.** Audit against CLAUDE.md's non-negotiables (prepared
      statements, `htmlspecialchars` on every echo, CSRF on both POSTs, cookie flags); re-confirm
      all 6 consent states end to end on the real pages (not just the preview harness).
- [ ] **Step 7 — Deliverables.** Finalize root `README.md`, `git init`, one clean initial commit.

**Explicitly out of scope for v1.0** (per CLAUDE.md: consent rules are "the graded core — do
not improvise"): anything that changes the accept/decline mechanics, cookie shape, or
versioning behavior described in the spec. All v1.1+ ideas below that touch consent are opt-in
enhancements layered *around* that mechanism, never replacements for it.

---

## v1.2 — admin portal upgrade (in progress)

Scoped from a priority pass on 2026-08-25: which admin improvements to build now vs. leave as
"someday" ideas below. Multi-admin roles stayed a someday item (biggest lift, touches Auth.php's
core model); audit log and change-password came off the someday list into this one.

### Login form
- [x] **Show/hide password toggle** — small eye button inside the password field.
- [x] **Field-level error feedback** — `has-error` styling (already exists, reused from the
      contact form) on both fields when credentials are wrong.
- [x] **Live rate-limit countdown** — new `Auth::retryAfterSeconds()` computes remaining wait
      time from the oldest attempt in the current window; login.php distinguishes "wrong
      credentials" from "rate limited" (currently one generic message for both) and JS counts
      the rate-limit message down live.
- [x] **Loading state on submit** — button disables + shows a spinner on submit. Still a real
      form POST underneath (works with JS off, just no spinner) — same pattern as the rest of
      this project's progressive enhancement.
- [x] **Entrance animation** — reuses the existing `smg-rise-in` keyframe from the consent
      dialog. Unconditional CSS, no scroll-trigger needed (it's above the fold), respects
      `prefers-reduced-motion` via the existing global rule.
- [x] **Real background photo** — the real HQ building photo (already in `assets/images/hero.jpg`)
      as a full-bleed background behind the card, with a dark scrim for contrast/legibility.

### Dashboard features
- [x] **Consent record detail page** (`admin/record.php?guid=`) — full record including columns
      not shown in the table today (`ip_address`, `user_agent`, `created_at`). Real page, not a
      JS-only modal, so it stays linkable and works without JS. GUID cells in the table become
      links to it.
- [x] **Advanced filters** — status (Accepted/Declined/Expired) and a date range, added to the
      existing GUID search. Status filtering needs a SQL `CASE`-based WHERE clause since
      "expired" isn't a stored column, just `accepted_at`/`expires_at` compared to now.
      `export.php` honors the same filters as the table.
- [x] **Sortable columns** — `?sort=&dir=` on the table headers (Accepted at / Expires / Ver).
      Column name is whitelisted server-side (can't parameterize an identifier in SQL) rather
      than taken directly from the query string.
- [x] **Dashboard trend chart** — accepted vs. declined over the last 14 days. Plain CSS bars
      (no charting library, consistent with the project's no-dependency rule), grouped by day
      from a `GROUP BY DATE(accepted_at), action` query.

### Bigger commitments (real auth/security surface)
- [x] **Admin audit log** — new `admin_audit_log` table (admin id, action, detail, IP,
      timestamp). Logged on login, logout, export, and viewing a record's detail page. New
      `src/AuditLog.php` helper (kept separate from `Auth.php`, matching the project's
      one-class-per-concern pattern) and a new `admin/audit.php` viewer, paginated.
- [x] **Change-my-password page** (`admin/change-password.php`) — current password
      (`password_verify`), new password + confirm, CSRF-protected, updates
      `admin_users.password_hash`.

New shared file: `public/assets/css/admin.css` (parallel to `home.css`) for all of the above,
keeping `site.css` from growing further with admin-only styles. Login-form JS (password toggle,
loading state, countdown) goes in a new `public/assets/js/admin.js`; the dashboard features
above are all plain server-rendered links/forms, no JS required.

**Verified live** (2026-08-26): all of the above tested end-to-end against 40 seeded
`consent_log` rows spanning ~13 days — status filter, sort direction (confirmed via actual row
order, not just UI state), the detail page (including IP address decoding from `VARBINARY` and
the real `user_agent`), the audit log capturing real login/logout/view_record/change_password
events, and change-password verified by actually logging back in with the new password
afterward. Two real bugs found and fixed in the same pass (see `docs/BUILD-PLAN.md`): a MySQL
session-timezone mismatch, and GUID links inheriting the global red link color across an entire
20-row column.

---

## v1.3 — automated test suite

Scoped from a follow-up request on 2026-08-26 to add unit/integration/E2E coverage. Moved
straight out of the "someday" list below into a real, run `composer install && npm install`
suite.

- [x] **PHPUnit unit tests** (`tests/Unit/`, 42 tests) — `Csrf` (token format/stability,
      `verify()` accept/reject cases), `ConsentQuery` (status/sort/dir whitelisting, WHERE-clause
      building for every filter combination), `Consent` (GUID v4 format via reflection,
      `sanitizeRedirect()`'s whitelist/query-stripping, and the dialog-state logic across
      accept/decline cookie combinations — including a real quirk this uncovered: a falsy
      cookie version, e.g. `0`, is treated by `readAcceptCookie()`'s `empty()` checks as no
      cookie at all, not as "outdated").
- [x] **PHPUnit integration tests** (`tests/Integration/`, 17 tests) against a real, isolated
      `smg_consent_test` database — the `consent_log` upsert-on-guid behavior (repeat `accept()`
      reuses the same guid and updates the row instead of duplicating it), `login_attempts`
      rate-limit counting (threshold, IP scoping, the 60s window boundary) and
      `Auth::retryAfterSeconds()`, and the CSV export / admin dashboard query run through every
      status/search/date-range combination.
- [x] **Playwright E2E tests** (`tests/e2e/`, 16 tests) — the consent-gate state matrix (first
      visit, accept, decline, reload-persists, "Cookie settings" reopen + dismiss, a stale cookie
      version forcing reappearance, and the full flow with `javaScriptEnabled: false`), plus the
      admin portal (invalid login, successful login, the 5/min lockout, dashboard filter/sort,
      the record detail page, change-password including a reject case).

**Test isolation:** a shared `Smg\Config::get()` (new `src/Config.php`, replacing four
duplicated config-loading implementations) reads from `SMG_CONFIG_PATH` when the constant is
defined, falling back to the real `config.php` otherwise — so `tests/config.test.php` (no
secrets, safe to commit) can redirect every layer at `smg_consent_test` without touching
production behavior. PHPUnit sets the constant in `tests/bootstrap.php`; the Playwright web
server sets it via `php -d auto_prepend_file=tests/e2e/prepend.php`.

**A real bug this setup caught immediately:** the Playwright config's first draft had
`reuseExistingServer: !process.env.CI` on the conventional port 8000. An unrelated `php -S
localhost:8000` process left running from earlier manual smoke-testing was silently reused
instead of Playwright starting its own — meaning every E2E test ran against the real `smg_consent`
database (no `SMG_CONFIG_PATH`, no `e2e_admin` user) instead of the isolated test one, so every
admin-login test failed with a correct-looking "Invalid username or password". Fixed by moving
the E2E server to a dedicated port (8098) and hard-setting `reuseExistingServer: false` — this
suite must always own its server, never adopt whatever else happens to be listening.

**Verified live** (2026-08-26): `vendor/bin/phpunit` — 59 tests, 99 assertions, all green;
`npx playwright test` — 16 tests, all green, run twice to confirm the port fix held.

---

## v1.1+ — post-submission enhancements

Grouped by category. None of these block v1.0; they're what I'd propose next if this became a
real production handoff.

### Testing & QA
- ~~Unit tests (PHPUnit)~~ / ~~Integration tests~~ / ~~End-to-end tests (Playwright)~~ — **done**,
  see the v1.3 section above.
- **Accessibility audit** — automated `axe-core` pass across all 4 pages + the dialog in both
  its modes, on top of the manual focus/`inert`/`aria-*` work already in v1.0.
- **Load testing (k6 or Apache Bench)** against `consent.php` (POST under concurrency — validates
  the `guid` unique-index upsert doesn't deadlock) and `admin/index.php`'s paginated query (validates
  the `idx_accepted_at` / `idx_version` indexes actually get used via `EXPLAIN`).

### CI / DevEx
- **GitHub Actions pipeline**: `php -l` across all files, PHPUnit, PHP_CodeSniffer (PSR-12),
  and optionally PHPStan/Psalm for static analysis — currently all of this is done by hand per step.
- **Dockerfile + docker-compose** (PHP 8.2 + MySQL 8) so a reviewer doesn't need Homebrew/local
  installs to run it — trades away "no build step" but only for local dev convenience, the
  deployed app stays build-step-free.
- **Database migrations (e.g. Phinx)** instead of one `schema.sql`, so schema changes have a
  history instead of hand-editing the file (as I did adding `login_attempts`/`contact_messages`).

### Features
- **Cookie preference granularity** (necessary / performance / marketing categories) instead of
  a binary accept/decline — a common real-world CMP upgrade, but changes the graded consent
  mechanic, so this is explicitly v1.1+, not a v1.0 improvisation.
- **Self-service consent lookup** — let a visitor look up their own consent record by GUID
  (ties into the "Your consent record" callout already on the legal pages) without needing
  admin access — a real subject-rights nicety.
- ~~Admin audit log~~ — moved to the v1.2 admin-portal-upgrade section above.
- **Multi-admin roles** (viewer vs. exporter vs. superadmin) once there's more than one admin user.
- ~~Real Star Media Group brand assets in place of the ★ text-glyph placeholder~~ — **done**,
  see `docs/BUILD-PLAN.md`'s homepage-revamp entry. The real logo now replaces the ★ everywhere,
  and the homepage got real photos, animation, and a carousel (bonus, beyond the graded core).

### Security / compliance
- **Structured audit logging** of consent state transitions (currently just the DB row; a
  separate append-only log would survive a `consent_log` row being overwritten by the upsert).
- **SPF/DKIM setup notes** for the contact-form mail sender, since native `mail()` deliverability
  depends entirely on the host's outbound mail reputation.
- **CSP headers** and a security-headers audit (`X-Content-Type-Options`, `Referrer-Policy`, etc.)
  — not required by the spec, but a reasonable senior-level addition.

### Performance / ops
- **Lighthouse CI budget** on the 4 public pages (the design is lightweight enough this should
  be trivial to pass, but it's not currently measured).
- **Health-check endpoint** (`/healthz`) for uptime monitoring in a real deployment.
