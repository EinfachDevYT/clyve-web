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

## Einrichtung

```bash
cp config/config.example.php config/config.php
php -r "echo bin2hex(random_bytes(32));"     # -> app_secret
php bin/create-staff.php                      # ersten Admin anlegen
```

Lokal testen:

```bash
php -S 127.0.0.1:8080 -t public public/index.php
php -S 127.0.0.1:8081 -t staff staff/index.php
```

Dafür in `config.php` die Origins auf `http://127.0.0.1:8080` bzw. `:8081` setzen.

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
