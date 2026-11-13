# clyve-web

Website and customer portal of **Clyve.dev** – project requests with offers, customer area, support tickets, receipts/invoices, script shop (Tebex), bot hosting, devlog and a separate staff area.
Plain PHP 8.2+, no framework, no dependencies.

Design & development: [EinfachDev](https://einfachdev.de) · Proprietary, see [LICENSE](LICENSE).

> The site itself is in German, so menu items and labels below are quoted in German.

## Structure

```
app/
  Core/           router, database, auth, security, views, validation, rate limits
  Controllers/
    Site/         main site + customer area
    Staff/        staff area
    Api/          license API for hosted bots
  Services/       orders, invoices, mailer (SMTP), Discord, Tebex sync, hosting, devlog, settings
  bootstrap.php   shared entry point
  helpers.php
routes/           site.php, staff.php
resources/views/  templates (site, staff, shared, errors)
public/           web root  clyve.dev
staff/            web root  staff.clyve.dev  (assets -> ../public/assets)
config/           config.example.php -> config.php (not in the repo)
database/         migrations/ (run automatically on the first request)
storage/          SQLite, sessions, rate limits (not in the repo, not public)
bin/              CLI tools
deploy/           nginx templates + DEPLOY.md
```

Only `public/` and `staff/` are reachable by the web server. Code, config and data live above them.

## Try it in the browser (GitHub Codespaces)

On GitHub: **Code → Codespaces → Create codespace on main**.
After about a minute everything is set up: PHP, `config/config.php`, an admin login and both servers.
Credentials and URLs are in `storage/DEV-LOGIN.txt` (opens automatically), the ports are in the **Ports** tab:

- 8080 – website
- 8081 – staff area

Restart the servers: `bash bin/dev-start.sh`

## Setup

```bash
cp config/config.example.php config/config.php
php -r "echo bin2hex(random_bytes(32));"     # -> app_secret
php bin/create-staff.php                      # create the first admin
```

Run locally:

```bash
php -S 127.0.0.1:8080 -t public bin/dev-router.php
php -S 127.0.0.1:8081 -t staff bin/dev-router.php
```

For that, set `app_env` to `'local'` in `config.php` and the origins to `http://127.0.0.1:8080` and `:8081`.

Production with nginx: see **[deploy/DEPLOY.md](deploy/DEPLOY.md)**.
`storage/` must be writable by the PHP user.

## Order workflow

1. A customer sends a request through the 4-step form (questions per category) – with or without an account.
   Without an account they get a **private link** (`/anfrage/…`), with an account everything is under `/konto/anfragen`.
2. In the staff area: reply (quick replies, e-mail to the customer), add **internal notes**, send an **offer** (free offers too).
3. The customer accepts or declines → status **In Arbeit** with progress in % → delivered → done.
4. One click turns the accepted offer into a receipt/invoice (the customer needs an account with the same e-mail).

Notifications: the customer by e-mail (SMTP, optional), you via Discord webhook.

E-mails are sent as HTML in the site's look (dark card, violet button, logo embedded inline, so it shows without
loading remote images) with a plain-text version for clients that don't render HTML. The template is
`resources/views/mail/html.php`; `Mailer::compose()` builds a message from greeting, paragraphs, an optional button,
key/value facts (e.g. price and delivery of an offer) and a quoted text. Send yourself a sample via
Verwaltung → E-Mail → „Test-E-Mail senden“.

## Receipts: hobby, small business, VAT registered

`business.mode` in the config:

| Mode | Document | Number | Tax |
|---|---|---|---|
| `hobby` (default) | receipt from a private person | `Q-2026-0001` | no VAT |
| `kleinunternehmer` | invoice with § 19 UStG note | `CLY-2026-0001` | no VAT |
| `regular` | invoice | `CLY-2026-0001` | with `vat_rate` |

Issued documents keep their mode and can't be changed (corrections via cancellation). PDF via the print view.
Not legal or tax advice: if you take paid jobs regularly, you usually need to register a business.

## Admin settings (Staff → „Verwaltung“, admins only)

Requests on/off (with a custom message), single categories, registration, e-mail verification, support tickets,
script shop, availability text, response time and an announcement banner. Switches are enforced server-side.
Business details, SMTP and integrations (Discord, Tebex) are edited here too; imprint and privacy policy are generated from them.
Optionally add a DSA contact point (Verwaltung → Betreiber → „Kontaktstelle nach DSA“): an e-mail address for authorities,
users and reports of illegal content (Art. 11, 12, 16 Regulation (EU) 2022/2065). Once set, the imprint gets a matching
section and the privacy policy explains how reports are processed.

## Devlog

Write update posts in the staff area under **Devlog**. Every line starting with a symbol becomes a changelog entry, like on GitHub:

```
A few intro sentences …

## Customer area
+ added
- removed
~ improved
! fixed
```

`## ` starts a section, `**bold**` and `` `code` `` work in text. Posts start as drafts and go live with „Veröffentlichen“.
Signed-in customers see them under **Devlog** in their account (with a counter for unread posts). Every published post has
its own link (`/devlog/<title>`) that is readable without login so Discord can show a preview – the list of all posts
stays in the customer area.

For sharing, each post shows a ready-made Discord message with a ```` ```diff ```` block (green/red like on GitHub) and
the link to copy. With a devlog webhook under Verwaltung → Integrationen you can post it straight to your announcement
channel, optionally mentioning a role. The first posts about past updates ship as drafts.

## Accounts & security

The customer area (`/konto`) is its own app with a sidebar, like the staff area – separate from the landing page,
its navigation and footer. Login, registration and verification have their own full-screen pages.

New customers confirm their e-mail address via link (valid 48 hours, can be resent) before they can sign in.
Only then are earlier guest requests with the same address linked to the account. Can be turned off under
Verwaltung → Allgemein („E-Mail-Bestätigung“); without e-mail sending set up it is off automatically. A password reset
by mail also confirms the address, and admins can confirm it manually in the customer view.

Registration asks for full customer details: salutation, first and last name, phone (optional), address
(street, house number, extra line, zip, city, country) and for business customers company name and VAT ID. The zip code
is validated per country. Customers edit their details under „Einstellungen“ → „Profil“; existing customers without an
address see a notice on their dashboard. Invoices show the extra address line and – for companies – the VAT ID.

Customers („Konto → Einstellungen“) and staff („Einstellungen“) have a personal area with profile and
„Anmeldung & Sicherheit“: change password, change e-mail (with a confirmation link to the new address),
two-factor login with an authenticator app (TOTP, QR code, 10 recovery codes), sign out of all other devices
and a history of recent sign-ins. „Passwort vergessen“ sends a one-time link (valid 60 minutes).

Admins manage the team under **Mitarbeiter**: invite (link by e-mail or to pass on), change role, block access,
reset 2FA. Under **Verwaltung → Sicherheit** 2FA can be made mandatory for staff. Security-relevant changes trigger
a notification e-mail. Admins can view their own recovery codes again after password + app code (stored encrypted for
that, every view is logged); for all other accounts they are only stored hashed. 2FA secrets are stored encrypted,
codes and links only hashed.

## Closing accounts

Customers can close their account themselves under „Einstellungen → Anmeldung & Sicherheit → Konto schließen“
(password, plus 2FA code if enabled). This is blocked while an invoice is unpaid or a hosting service is still running.
Admins can delete a customer on the customer page (type `LÖSCHEN` to confirm); only running services block that.

- Without receipts or services the account is deleted completely, including tickets and requests.
- Otherwise it is anonymised: login, contact details, profile, tickets and requests without a receipt are removed,
  the name becomes „Gelöschter Kunde #id“. Issued receipts keep the recipient data printed on them, as required by
  retention rules (§ 147 AO / § 257 HGB, currently 8 years for receipts). Requests behind a receipt or service stay as
  business records without contact details.
- The customer gets a confirmation e-mail, all sessions end immediately and the action is written to the audit log.

## Bot hosting & licenses

Staff → Hosting: services per customer with plan, term, renewal (optionally with an invoice draft), manual suspension
with reason, cancellation and history. After expiry + grace period services are suspended automatically; reminder
e-mails go out before that (days configurable). Every service has a license key (`CLY-…`, stored only as a hash) that
the bot checks via `GET /api/v1/license` with `Authorization: Bearer …`. Customers see their services under
„Konto → Dienste“. Cron job: `bin/cron.php` every 5 minutes (see `deploy/DEPLOY.md`).

## Script shop (Tebex)

Page `/scripts` with framework filter and „Auf Tebex kaufen“. Products are managed under Staff → Scripts, optionally
synced from your Tebex store with one click (`tebex.webstore_token`, `tebex.store_url`).
Payment, licensing/escrow and receipts for buyers are handled by Tebex.

## Security

- Prepared statements everywhere, output escaping in all templates
- CSRF token + origin check on every POST
- Strict CSP without inline scripts, HSTS, frame protection
- Passwords with Argon2id, session fixation protection, idle timeout
- Separate sessions for main site and staff area (host-only cookies)
- Rate limits for login, registration, requests, tickets (IPs stored only as HMAC)
- Staff area optionally behind an IP allowlist, first admin only via CLI, further staff by invitation, audit log
- Private request links: 160 bit, stored only as a hash, `no-referrer`, can be regenerated by an admin
- Tebex content is sanitized to plain text, images only from allowed hosts (CSP)

## Logo

All variants are SVGs in `public/assets/brand/` (each with a `-light` version for light backgrounds):

| File | Use |
|---|---|
| `clyve-logo.svg` | default logo (navigation, footer) |
| `clyve-mark.svg` | mark only |
| `clyve-icon.svg` | app icon / favicon |
| `clyve-x-einfachdev.svg` | combo horizontal: Clyve.dev \| by EinfachDev |
| `clyve-by-einfachdev-stacked.svg` | combo stacked |
| `clyve-x-einfachdev-mark.svg` | combo symbol Clyve × EinfachDev |

Text is converted to outlines (Inter Display, JetBrains Mono – both OFL), so the logos look the same everywhere.
Rebuild: `pip install fonttools && python3 resources/brand/build.py public/assets/brand`
