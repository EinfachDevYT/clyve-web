-- Devlog posts shown to signed-in customers
CREATE TABLE IF NOT EXISTS devlog_posts (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  slug         TEXT    NOT NULL UNIQUE,
  title        TEXT    NOT NULL,
  tag          TEXT    NOT NULL DEFAULT 'feature' CHECK (tag IN ('feature','improvement','fix','news')),
  summary      TEXT    NOT NULL DEFAULT '',
  body         TEXT    NOT NULL DEFAULT '',
  status       TEXT    NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','published')),
  pinned       INTEGER NOT NULL DEFAULT 0,
  author_id    INTEGER REFERENCES staff(id) ON DELETE SET NULL,
  published_at TEXT,
  discord_posted_at TEXT,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_devlog_published ON devlog_posts(status, published_at);

ALTER TABLE customers ADD COLUMN devlog_seen_at TEXT;

-- First posts as drafts, published from the staff area
INSERT OR IGNORE INTO devlog_posts (slug, title, tag, summary, body, status, created_at, updated_at) VALUES ('anfragen-angebote-quittungen', 'Anfragen, Angebote & Belege an einem Ort', 'feature', 'Projektanfragen laufen jetzt komplett über die Website – vom ersten Formular bis zum Beleg.', 'Statt alles über Discord-DMs zu klären, hat jede Anfrage jetzt ihren eigenen Verlauf.

## Anfragen
+ Neues Anfrage-Formular in mehreren Schritten für FiveM-Scripts, Websites und Discord-Bots
+ Privater Link zu jeder Anfrage – auch ohne Kundenkonto
+ Antworten direkt im Verlauf der Anfrage
+ Angebote können mit einem Klick angenommen werden

## Belege
+ Quittungen und Rechnungen als PDF im Kundenbereich
+ Script-Shop mit allen Scripts unter **/scripts**', 'draft', '2026-11-11 10:00:00', '2026-11-11 10:00:00');
INSERT OR IGNORE INTO devlog_posts (slug, title, tag, summary, body, status, created_at, updated_at) VALUES ('bot-hosting', 'Bot-Hosting im Kundenbereich', 'feature', 'Gehostete Discord-Bots tauchen jetzt mit Laufzeit und Status in deinem Konto auf.', 'Wenn wir einen Bot für dich hosten, siehst du alles Wichtige jetzt selbst.

## Dienste
+ Neuer Bereich **Dienste** mit Status und Laufzeit jedes Bots
+ Erinnerung per E-Mail, bevor die Laufzeit endet
+ Hinweis in der Übersicht, wenn ein Dienst bald abläuft oder pausiert ist
~ Klare Bedingungen für Hosting und Verlängerung', 'draft', '2026-11-11 18:00:00', '2026-11-11 18:00:00');
INSERT OR IGNORE INTO devlog_posts (slug, title, tag, summary, body, status, created_at, updated_at) VALUES ('mehr-sicherheit-fuer-dein-konto', 'Mehr Sicherheit für dein Konto', 'feature', 'Zwei-Faktor-Anmeldung, Passwort zurücksetzen und mehr: dein Konto ist jetzt deutlich besser geschützt.', 'Unter **Einstellungen → Anmeldung & Sicherheit** findest du alles rund um dein Konto.

## Neu
+ Zwei-Faktor-Anmeldung mit Authenticator-App inklusive Backup-Codes
+ Passwort vergessen? Zurücksetzen per E-Mail-Link
+ E-Mail-Adresse ändern – mit Bestätigung an die neue Adresse
+ Auf allen anderen Geräten abmelden
+ Sicherheits-Mails bei wichtigen Änderungen an deinem Konto

## Verbessert
~ Persönliche Einstellungen sind jetzt getrennt von der restlichen Website', 'draft', '2026-11-12 18:00:00', '2026-11-12 18:00:00');
INSERT OR IGNORE INTO devlog_posts (slug, title, tag, summary, body, status, created_at, updated_at) VALUES ('neuer-kundenbereich', 'Dein Kundenbereich im neuen Look', 'improvement', 'Der Kundenbereich ist jetzt eine eigene App mit Seitenleiste – und neue Konten werden per E-Mail bestätigt.', 'Bisher sah der Kundenbereich aus wie ein Teil der Startseite. Das ist vorbei.

## Kundenbereich
+ Eigene Seitenleiste mit Übersicht, Anfragen, Support, Diensten und Rechnungen
+ Neue Login- und Registrierungsseiten im Vollbild
~ Auf dem Handy wird die Navigation zu einer Leiste oben
- Kopfzeile und Footer der Startseite im Kundenbereich entfernt

## Registrierung
+ E-Mail-Bestätigung für neue Konten – der Link ist 48 Stunden gültig
+ Frühere Anfragen mit derselben E-Mail werden nach der Bestätigung automatisch verknüpft

## Behoben
! Auf dem Handy hat der Name die obere Leiste verdeckt', 'draft', '2026-11-13 12:00:00', '2026-11-13 12:00:00');
INSERT OR IGNORE INTO devlog_posts (slug, title, tag, summary, body, status, created_at, updated_at) VALUES ('vollstaendige-kundendaten', 'Registrierung mit vollständigen Kundendaten', 'improvement', 'Bei der Registrierung gibst du jetzt direkt deine Anschrift an – damit Rechnungen sofort stimmen.', 'Damit Belege ohne Rückfragen ausgestellt werden können, fragen wir die Daten jetzt gleich bei der Registrierung ab.

## Neu
+ Anrede, Vor- und Nachname getrennt
+ Telefonnummer (optional)
+ Geschäftskunden können Firmenname und USt-IdNr. angeben
+ Adresse mit Straße, Hausnummer, Zusatz, PLZ, Stadt und Land
+ Passwort beim Eintippen anzeigen

## Verbessert
~ PLZ wird passend zum Land geprüft
~ Rechnungen zeigen Adresszusatz und USt-IdNr.
~ Hinweis in der Übersicht, falls deine Anschrift noch fehlt', 'draft', '2026-11-13 21:00:00', '2026-11-13 21:00:00');
