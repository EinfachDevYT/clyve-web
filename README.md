# clyve-web

Website und Kundenportal von **Clyve.dev** – Anfragen, Kundenbereich, Support-Tickets, Rechnungen und ein separater Teambereich.
Pures PHP 8.2+ ohne Framework und ohne Abhängigkeiten.

Design & Entwicklung: [EinfachDev](https://einfachdev.de) · Proprietär, siehe [LICENSE](LICENSE).

## Struktur

```
app/
  Core/           Router, Datenbank, Auth, Security, Views, Validierung, Rate-Limit
  Controllers/
    Site/         Hauptseite + Kundenbereich
    Staff/        Teambereich
  Services/       Rechnungslogik, Benachrichtigungen (Discord / Mail)
  bootstrap.php   gemeinsamer Einstieg
  helpers.php
routes/           site.php, staff.php
resources/views/  Templates (site, staff, shared, errors)
public/           Webroot  clyve.dev
staff/            Webroot  team.clyve.dev   (assets -> ../public/assets)
config/           config.example.php -> config.php (nicht im Repo)
database/         schema.sql
storage/          SQLite, Sessions, Rate-Limits (nicht im Repo, nicht öffentlich)
bin/              CLI-Tools
deploy/           nginx.conf
```

Nur `public/` bzw. `staff/` sind vom Webserver erreichbar. Code, Konfiguration und Daten liegen darüber.

## Im Browser testen (GitHub Codespaces)

Auf GitHub im Repo: **Code → Codespaces → Create codespace on main**.
Nach etwa einer Minute ist alles eingerichtet: PHP, `config/config.php`, ein Admin-Zugang und beide Server.
Zugangsdaten und Adressen stehen in `storage/DEV-LOGIN.txt` (öffnet sich automatisch), die Ports findest du im Tab **Ports**:

- 8080 – Website
- 8081 – Teambereich

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

Produktiv: zwei vHosts laut `deploy/nginx.conf`, HTTPS ist Pflicht (Cookies laufen dann mit `__Host-`-Präfix).
`storage/` muss für den PHP-User beschreibbar sein.

## Rechnungen & Gewerbe

Solange `business.registered = false` ist, können Rechnungen nur als **Entwurf** angelegt werden (Vorschau mit Wasserzeichen).
Nach der Gewerbeanmeldung Steuernummer, Anschrift und Bankdaten in `business` eintragen und `registered` auf `true` setzen.

- Nummernkreis lückenlos pro Jahr: `CLY-2026-0001`
- Ausgestellte Rechnungen sind unveränderlich, Korrektur nur per Storno
- Kleinunternehmer (§ 19 UStG) standardmäßig aktiv, sonst USt. mit `vat_rate`
- PDF über die Druckansicht des Browsers

## Sicherheit

- Prepared Statements überall, Output-Escaping in allen Templates
- CSRF-Token + Origin-Prüfung bei jedem POST
- Strikte CSP ohne Inline-Skripte, HSTS, Frame-Schutz
- Passwörter mit Argon2id, Session-Fixation-Schutz, Idle-Timeout
- Getrennte Sessions für Hauptseite und Teambereich (Host-only-Cookies)
- Rate-Limits für Login, Registrierung, Anfragen, Tickets (IP nur als HMAC gespeichert)
- Teambereich optional per IP-Allowlist, Mitarbeiter nur per CLI anlegbar, Audit-Log

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
