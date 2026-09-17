# Interviewer feedback — legal pages without the gate, explicit try/catch

**Date:** 2026-09-17 · **Branch:** `fix/interviewer-feedback`

## Feedback being addressed

1. *"The accept/decline consent box still appears before the T&C and Privacy Statement pages,
   before clicking on the accept or decline button. Ideally, the box should not appear on either
   page, so users can read the content before deciding whether to accept or reject on the
   relevant pages."*
2. *"All backend code files do not currently have try/catch blocks."*

## Current state

- `Consent::dialogState()` forces the blocking dialog on every public page, including
  `privacy.php` and `terms.php` — the two pages the dialog's own copy links to.
- Error handling relies on one global `set_exception_handler` in `templates/bootstrap.php` and a
  duplicate in `templates/admin-bootstrap.php`. Local try/catch exists only in `Db.php`
  (connection retry), `ConsentQuery.php` and `legal-page.php` (date parsing).
- `public/consent.php` loads no bootstrap, so it has **no** exception handler.
- `Consent::accept()` writes the cookie **before** the `consent_log` insert; a DB failure leaves
  an accepted cookie with no row.

## Decisions

| Question | Decision |
|---|---|
| How does a visitor choose on a legal page? | Non-modal **sticky bottom bar** with the verbatim copy and Accept/Decline. |
| How does a page opt out of the gate? | An exempt-page list inside `Consent` (not a per-page flag). |
| Accept when the DB is down | **Fail closed** — row first; on failure no cookie, inline error, prompt stays open. |
| Decline when the DB is down | **Honour it** — set the 1-day cookie, `error_log` the failed insert. |
| try/catch style | **Targeted** at each failure point, each catch making a real decision; global handler kept as backstop. |

## Part 1 — Legal pages skip the blocking gate

### Contract — `src/Consent.php`

- `private const GATE_EXEMPT_PAGES = ['privacy.php', 'terms.php'];`
- `public static function isGateExemptPage(): bool` — `basename($_SERVER['SCRIPT_NAME'] ?? 'index.php')`
  is in the list.
- `dialogState()` returns `array{visible: bool, dismissible: bool, banner: bool}`:
  - `pending  = shouldShowDialog()` (unchanged — same cookies, versions, TTLs)
  - `blocking = pending && !isGateExemptPage()`
  - `visible  = blocking || isManageRequested()`
  - `dismissible = visible && !blocking`
  - `banner   = pending && isGateExemptPage()`

Consent *recording* is unchanged; only where the prompt is presented changes.

### Rendering

- `templates/bootstrap.php` destructures `banner` into `$showBanner`. `$consentGateOpen` keeps its
  definition (`$showDialog && !$dismissible`) and is therefore false on legal pages — no
  `smg-locked` on `<html>`, no `inert` on header/main/footer.
- **`templates/consent-form.php`** (new) — the two verbatim consent paragraphs and the `<form>`
  (CSRF field, `redirect_to`, Accept/Decline buttons), extracted from `consent-dialog.php`. Takes
  `$consentActionsClass` for the button-row class. Included by both the dialog and the bar, so the
  requirements-PDF copy exists in exactly one place.
- **`templates/consent-banner.php`** (new) — `<section class="smg-consent-banner"
  aria-label="Cookie consent" data-smg-consent-banner>` wrapping the partial.
- `templates/legal-page.php` renders the bar when `$showBanner`, as the last child of `<body>`
  after the footer, `position: sticky; bottom: 0`. It stays pinned while reading and settles below
  the footer at page end. The (hidden) dialog is still rendered so "Cookie settings" and
  `?consent=manage` work.

### Styling — `public/assets/css/site.css`

`.smg-consent-banner`: `--smg-*` tokens only, no new hex values. Copy and actions side by side at
≥768px, stacked below. Consent buttons 48px tall. The red Accept button falls under the existing
consent-UI red exception; the red `expires` chip only renders once a record exists, when the bar
is gone.

### Behaviour — `public/assets/js/consent.js`

- Submit handler binds to every `[data-smg-consent-form]` (dialog and bar), with per-form
  button/spinner state.
- On `ok: true`: close the dialog if open; hide every `[data-smg-consent-banner]`.
- No-JS: the bar's form POSTs to `consent.php`, which 303s back to the same legal page
  (`sanitizeRedirect` already allows both) — bar gone.

## Part 2 — Explicit try/catch

**Rule:** catch `PDOException` where DB work happens; every catch `error_log()`s the real
exception and never shows it to the visitor. `consent.php` (no bootstrap) catches `\Throwable`.
The global `set_exception_handler` stays as a last resort.

### Shared — `src/ErrorPage.php` (new)

`ErrorPage::render(int $status = 503): void` — sets the status and content type if headers aren't
sent, then echoes the "We hit a snag" page currently duplicated in both bootstraps. Both handlers
and every 503 catch below call it.

### Consent

| Location | Behaviour on failure |
|---|---|
| `Consent::accept()` | Reordered: `logConsent()` runs **before** `writeAcceptCookie()` / `clearDeclineCookie()`. The exception propagates; no cookie is written. |
| `Consent::decline()` | try/catch around `logConsent()` → `error_log`; the decline cookie is still written and the accept cookie cleared. |
| `public/consent.php` | `catch (\Throwable)` around accept/decline → `error_log`; HTTP 503; fetch gets `{"ok":false,"error":"server_error"}`, non-fetch gets `ErrorPage::render(503)`. |
| `consent.js` | On `server_error`: re-enable buttons, show inline `role="alert"` text "We couldn't save your choice right now. Please try again." `invalid_request` keeps the `form.submit()` fallback. |

### Public site

| Location | Behaviour on failure |
|---|---|
| `public/about.php` — `contact_messages` INSERT | `$errors['form'] = "We couldn't send your message right now. Please try again shortly."`; form re-renders with input preserved. |

### Admin

| Location | Behaviour on failure |
|---|---|
| `Auth::attemptLogin()` — `last_login_at` UPDATE + login audit | Best-effort: `error_log`, login still succeeds. |
| `Auth::logout()` — logout audit | Best-effort: `error_log`, session still destroyed. |
| `public/admin/login.php` — rate-limit + attempt chain | `$error = 'Sign-in is temporarily unavailable. Please try again shortly.'` |
| `public/admin/change-password.php` — DB block | `$error = "We couldn't update your password right now. Please try again."` |
| `public/admin/export.php` — audit + query | Caught before any `header()` → `ErrorPage::render(503)`. Fails closed: no audit, no export. |
| `public/admin/index.php`, `audit.php`, `record.php` — data-loading preamble | `ErrorPage::render(503)` + `exit`. |

### Config and CLI

- `Config::fromFile()`: `is_file()` check → `RuntimeException('config.php not found — copy config.example.php to config.php')`
  instead of a fatal `require`.
- `bin/seed-admin.php`: try/catch around the INSERT → STDERR message, `exit(1)`.

### Deliberately unchanged

`Db.php` (already retries inside try/catch), `ConsentQuery.php` and `legal-page.php` (already
catch date parsing), `Csrf.php`, `Mailer.php` (`mail()` returns a bool; nothing throws).

## Testing

### Unit — `tests/Unit/ConsentTest.php`

- `/privacy.php`, `/terms.php`, no cookies → `visible=false`, `banner=true`.
- `/index.php`, `/about.php`, no cookies → `visible=true`, `dismissible=false`, `banner=false`
  (regression guard).
- Legal page + `?consent=manage`, no consent → `visible=true`, `dismissible=true`, `banner=true`.
- Legal page with a valid accept cookie → `banner=false`.
- Existing `dialogState` assertions updated for the `banner` key.

### Unit — `tests/Unit/ConsentFailureTest.php` (new)

- Reflection sets `Db::$instance` to an in-memory SQLite PDO (`ERRMODE_EXCEPTION`) with no
  `consent_log` table, so the insert throws a real `PDOException`; `tearDown` resets it to null.
- `accept()` throws `PDOException` and `$_COOKIE` has no `smg_consent`.
- `decline()` does not throw and `$_COOKIE` has `smg_consent_declined`.
- Marked skipped if `pdo_sqlite` is unavailable.

### Manual

1. `vendor/bin/phpunit` passes.
2. `php -S localhost:8000 -t public`, fresh browser profile:
   - `/terms.php`, `/privacy.php`: no overlay, scrollable, header clickable, bar visible. Accept →
     bar hides, `smg_consent` set, `consent_log` row present, reload shows "Your consent record".
     Clear cookies, Decline → `smg_consent_declined` set, bar gone.
   - `/index.php`, `/about.php`: blocking gate still appears; the dialog's "Terms & Conditions" link
     opens a readable terms page with the bar.
   - `?consent=manage` on a legal page opens a dismissible dialog.
   - Repeat the legal-page checks with JS disabled.
   - Bar at 375 / 768 / 1440: 48px buttons, footer not covered at page end.
3. DB unreachable (wrong port in `config.php`; expect ~4s of `Db` retries): Accept shows the inline
   error and sets no cookie; Decline sets its cookie; contact form keeps input with a form error;
   admin login shows "temporarily unavailable"; dashboard/audit/record/export show the 503 page;
   `error_log` has each exception; no stack trace reaches the browser.

## Docs

- `CLAUDE.md` consent rules: legal pages are exempt from the blocking gate and show the bar; accept
  writes the row before the cookie.
- `README.md`: consent-gate bullet; `src/` and `templates/` table rows (`ErrorPage.php`,
  `consent-form.php`, `consent-banner.php`).
- `docs/BUILD-PLAN.md`: decision entry for this feedback.

## Delivery

- Branch `fix/interviewer-feedback` off `main`.
- Commits: this spec → gate exemption + bar + tests → try/catch + `ErrorPage` + tests → docs.
  Conventional commit messages (`feat:` / `fix:` / `docs:`).
- Push and open a PR against `main` with a summary and test plan.
