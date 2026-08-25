# Star Media Group — 4-Page Consent Site

PHP 8.2+ / MySQL 8 practical-test build. Four public pages (Home, About/Contact, Privacy
Policy, Terms & Conditions), a blocking first-visit cookie-consent gate recorded to both a
cookie and the database, and a secured admin portal for reviewing consent acceptances.

Design system and functional spec: `docs/handoff/README.md`. Original requirements:
`docs/handoff/Practical Test - S. Web Developer.pdf`. Visual reference (open in a browser):
`docs/handoff/Star Theme Kit.dc.html`.

## Prerequisites
- PHP 8.2+ with the `pdo_mysql` extension
- MySQL 8

## Local setup
```bash
git clone <this-repo> smg-consent-site
cd smg-consent-site

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
Prompts for a username and password and inserts the bcrypt hash into `admin_users`.

## Local email testing
The contact form sends via PHP's native `mail()` (no Composer dependency). To capture
outgoing mail locally without a real SMTP server, point `sendmail_path` in `php.ini` (or
via `php -S -d sendmail_path=...`) at a local capture tool such as
[Mailpit](https://github.com/axllent/mailpit) or MailHog. Every submission is also persisted
to the `contact_messages` table regardless of whether delivery succeeds.

---

*This README will be expanded with the consent cookie and DB design rationale once the
build is complete.*
