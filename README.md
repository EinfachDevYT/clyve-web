# clyve-web

Website und Kundenportal von **Clyve.dev** – Auftragsanfragen mit Angeboten, Kundenbereich, Support-Tickets, Belege, Script-Shop (Tebex) und ein separater Staff-Bereich.
Pures PHP 8.2+ ohne Framework und ohne Abhängigkeiten.

Design & Entwicklung: [EinfachDev](https://einfachdev.de) · Proprietär, siehe [LICENSE](LICENSE).

## Struktur

```
app/
  Core/           Router, Datenbank, Auth, Security, Views, Validierung, Rate-Limit
  Controllers/
    Site/         Hauptseite + Kundenbereich
    Staff/        Staff-Bereich
  Services/       Aufträge, Belege, Mailer (SMTP), Discord, Tebex-Sync, Einstellungen
  bootstrap.php   gemeinsamer Einstieg
  helpers.php
routes/           site.php, staff.php
resources/views/  Templates (site, staff, shared, errors)
public/           Webroot  clyve.dev
staff/            Webroot  staff.clyve.dev  (assets -> ../public/assets)
config/           config.example.php -> config.php (nicht im Repo)
database/         migrations/ (laufen automatisch beim ersten Seitenaufruf)
storage/          SQLite, Sessions, Rate-Limits (nicht im Repo, nicht öffentlich)
bin/              CLI-Tools
deploy/           Nginx-Vorlagen + DEPLOY.md
```

Nur `public/` bzw. `staff/` sind vom Webserver erreichbar. Code, Konfiguration und Daten liegen darüber.

## Im Browser testen (GitHub Codespaces)

Auf GitHub im Repo: **Code → Codespaces → Create codespace on main**.
Nach etwa einer Minute ist alles eingerichtet: PHP, `config/config.php`, ein Admin-Zugang und beide Server.
Zugangsdaten und Adressen stehen in `storage/DEV-LOGIN.txt` (öffnet sich automatisch), die Ports findest du im Tab **Ports**:

- 8080 – Website
- 8081 – Staff-Bereich

Server neu starten: `bash bin/dev-start.sh`

## Einrichtung

```bash
cp config/config.example.php config/config.php
php -r "echo bin2hex(random_bytes(32));"     # -> app_secret
php bin/create-staff.php                      # ersten Admin anlegen
```

Lokal testen:

```bash
php -S 127.0.0.1:8080 -t public bin/dev-router.php
php -S 127.0.0.1:8081 -t staff bin/dev-router.php
```

Dafür in `config.php` `app_env` auf `'local'` und die Origins auf `http://127.0.0.1:8080` bzw. `:8081` setzen.

Produktiv auf einem Server mit Nginx: siehe **[deploy/DEPLOY.md](deploy/DEPLOY.md)**.
`storage/` muss für den PHP-User beschreibbar sein.

## Auftragsablauf

1. Kunde fragt über das Formular in 4 Schritten an (Fragen je Kategorie) – mit oder ohne Konto.
   Ohne Konto bekommt er einen **privaten Link** (`/anfrage/…`), mit Konto sieht er alles unter `/konto/anfragen`.
2. Im Staff-Bereich: antworten (Schnellantworten, E-Mail an den Kunden), **interne Notizen**, **Angebot** senden (auch kostenlos).
3. Kunde nimmt das Angebot an oder lehnt ab → Status **In Arbeit** mit Fortschritt in % → Geliefert → Abgeschlossen.
4. Aus dem angenommenen Angebot wird mit einem Klick der Beleg erstellt (Kunde braucht dafür ein Konto mit derselben E-Mail).

Benachrichtigungen: Kunde per E-Mail (SMTP, optional), du per Discord-Webhook.

## Belege: Hobby, Kleinunternehmer, regelbesteuert

`business.mode` in der Config:

| Modus | Beleg | Nummer | Steuer |
|---|---|---|---|
| `hobby` (Standard) | Quittung einer Privatperson | `Q-2026-0001` | keine USt. |
| `kleinunternehmer` | Rechnung, Hinweis § 19 UStG | `CLY-2026-0001` | keine USt. |
| `regular` | Rechnung | `CLY-2026-0001` | mit `vat_rate` |

Ausgestellte Belege behalten ihren Modus und sind unveränderlich (Korrektur per Storno). PDF über die Druckansicht.
Keine Rechts- oder Steuerberatung: Wer regelmäßig bezahlte Aufträge annimmt, braucht in der Regel eine Gewerbeanmeldung.

## Verwaltung (Staff → Verwaltung, nur Admin)

Anfragen an/aus (mit eigener Meldung), einzelne Kategorien, Registrierung, Support-Tickets, Script-Shop,
Verfügbarkeitstext, Antwortzeit und ein Ankündigungsbanner. Die Schalter werden serverseitig durchgesetzt.

## Konten & Sicherheit

Kunden (Konto → Einstellungen) und Staff (Einstellungen) haben einen persönlichen Bereich mit Profil sowie
„Anmeldung & Sicherheit“: Passwort ändern, E-Mail-Adresse ändern (mit Bestätigungslink an die neue Adresse),
Zwei-Faktor-Anmeldung per Authenticator-App (TOTP, QR-Code, 10 Wiederherstellungscodes), „auf allen anderen Geräten abmelden“
und ein Verlauf der letzten Anmeldungen. „Passwort vergessen“ schickt einen Einmal-Link (60 Minuten gültig).

Admins verwalten unter **Mitarbeiter** das Team: einladen (Link per E-Mail oder zum Weitergeben), Rolle ändern,
Zugang sperren, 2FA zurücksetzen. Unter **Verwaltung → Sicherheit** lässt sich 2FA für Staff verpflichtend machen.
Sicherheitsrelevante Änderungen lösen eine Hinweis-E-Mail aus. 2FA-Schlüssel sind verschlüsselt, Codes und Links nur gehasht gespeichert.

## Bot-Hosting & Lizenzen

Staff → Hosting: Dienste pro Kunde mit Tarif, Laufzeit, Verlängerung (optional direkt mit Beleg-Entwurf),
manueller Sperre mit Grund, Kündigung und Verlauf. Nach Ablauf + Kulanzzeit wird automatisch gesperrt,
vorher gehen Erinnerungsmails raus (Tage einstellbar). Jeder Dienst hat einen Lizenzschlüssel (`CLY-…`, nur als Hash gespeichert),
den der Bot über `GET /api/v1/license` mit `Authorization: Bearer …` prüft. Kunden sehen ihre Dienste unter Konto → Dienste.
Cronjob: `bin/cron.php` alle 5 Minuten (siehe `deploy/DEPLOY.md`).

## Script-Shop (Tebex)

Seite `/scripts` mit Framework-Filter und „Auf Tebex kaufen“. Produkte pflegst du unter Staff → Scripts,
optional per Klick aus deinem Tebex-Shop synchronisiert (`tebex.webstore_token`, `tebex.store_url`).
Bezahlung, Lizenz/Escrow und Belege für Käufer laufen über Tebex.

## Sicherheit

- Prepared Statements überall, Output-Escaping in allen Templates
- CSRF-Token + Origin-Prüfung bei jedem POST
- Strikte CSP ohne Inline-Skripte, HSTS, Frame-Schutz
- Passwörter mit Argon2id, Session-Fixation-Schutz, Idle-Timeout
- Getrennte Sessions für Hauptseite und Staff-Bereich (Host-only-Cookies)
- Rate-Limits für Login, Registrierung, Anfragen, Tickets (IP nur als HMAC gespeichert)
- Staff-Bereich optional per IP-Allowlist, Mitarbeiter nur per CLI anlegbar, Audit-Log
- Private Anfrage-Links: 160 Bit, nur als Hash gespeichert, `no-referrer`, per Admin neu erzeugbar
- Tebex-Inhalte werden zu Text bereinigt, Bilder nur von erlaubten Hosts (CSP)

## Logo

Alle Varianten liegen als SVG in `public/assets/brand/` (jeweils mit `-light` für helle Hintergründe):

| Datei | Verwendung |
|---|---|
| `clyve-logo.svg` | Standard-Logo (Navigation, Footer) |
| `clyve-mark.svg` | nur das Zeichen |
| `clyve-icon.svg` | App-Icon / Favicon |
| `clyve-x-einfachdev.svg` | Kombi horizontal: Clyve.dev \| by EinfachDev |
| `clyve-by-einfachdev-stacked.svg` | Kombi gestapelt |
| `clyve-x-einfachdev-mark.svg` | Kombi-Symbol Clyve × EinfachDev |

Die Schrift ist in Pfade umgewandelt (Inter Display, JetBrains Mono – beide OFL), die Logos sehen also überall gleich aus.
Neu erzeugen: `pip install fonttools && python3 resources/brand/build.py public/assets/brand`
