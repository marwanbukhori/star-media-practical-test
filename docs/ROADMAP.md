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

## v1.1+ — post-submission enhancements

Grouped by category. None of these block v1.0; they're what I'd propose next if this became a
real production handoff.

### Testing & QA
- **Unit tests (PHPUnit)** for the pure-logic pieces: `Consent::sanitizeRedirect()` (open-redirect
  guard), GUID v4 format/uniqueness, the accept/decline/version-bump state table, `Csrf::verify()`
  timing-safe comparison.
- **Integration tests** against a real (test) MySQL database: the `consent_log` upsert-on-guid
  behavior, `login_attempts` rate-limit counting, CSV export filtering.
- **End-to-end tests (Playwright, formalized)** — the state matrix I walked through manually in
  step 3 turned into a real spec file: 6 consent states, no-JS mode (`javaScriptEnabled: false`),
  focus trap, mobile bottom sheet, admin login/export — so regressions get caught automatically
  instead of by hand each time.
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
- **Admin audit log** — who exported what, who logged in when, beyond just `last_login_at`.
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
