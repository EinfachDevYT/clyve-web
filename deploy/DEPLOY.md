# Deployment auf einem Server mit Nginx

Diese Anleitung installiert Clyve.dev **neben** einer bestehenden Website.
Die vorhandene Seite in `/var/www/html` und ihre Nginx-Konfiguration werden nicht verändert.

| | Pfad / Adresse |
|---|---|
| Bestehende Website | `/var/www/html` (bleibt unverändert) |
| Clyve.dev Code | `/var/www/clyve-web` |
| Hauptseite | `https://clyve.dev` → Webroot `/var/www/clyve-web/public` |
| Staff-Bereich | `https://staff.clyve.dev` → Webroot `/var/www/clyve-web/staff` |
| Daten (DB, Sessions) | `/var/www/clyve-web/storage` (nicht öffentlich) |

Getestet mit Ubuntu 24.04, Nginx 1.24 und PHP 8.3 FPM. Andere Systeme funktionieren genauso, nur die PHP-Version im Paket- und Socket-Namen ist anders:

| System | PHP-Version |
|---|---|
| Debian 13 (trixie) | `8.4` |
| Ubuntu 24.04 | `8.3` |
| Debian 12 (bookworm) | `8.2` |
Alle Befehle als Benutzer mit `sudo`-Rechten.

> Andere Domain? Ersetze in dieser Anleitung und in `deploy/nginx/*.conf` überall `clyve.dev` durch deine Domain.

---

## 1. DNS

Beim Domain-Anbieter drei Einträge auf die IP deines Servers setzen:

| Typ | Name | Wert |
|---|---|---|
| A | `clyve.dev` (bzw. `@`) | IPv4 des Servers |
| A | `www` | IPv4 des Servers |
| A | `staff` | IPv4 des Servers |

Hat der Server IPv6, zusätzlich dieselben drei als **AAAA**-Eintrag.
Prüfen (kann ein paar Minuten dauern):

```bash
dig +short clyve.dev staff.clyve.dev www.clyve.dev
```

## 2. Pakete installieren

```bash
sudo apt update
```
Mit der PHP-Version deines Systems aus der Tabelle oben, hier für Ubuntu 24.04:

```bash
sudo apt install -y git php8.3-fpm php8.3-sqlite3 php8.3-curl php8.3-mbstring certbot python3-certbot-nginx
```

Für Debian 13:

```bash
sudo apt install -y git php8.4-fpm php8.4-sqlite3 php8.4-curl php8.4-mbstring certbot python3-certbot-nginx
```

> Meldet `sudo` „unable to resolve host …“, fehlt der eigene Hostname in `/etc/hosts`:
> `echo "127.0.1.1 $(hostname)" | sudo tee -a /etc/hosts`

Nginx läuft bereits. PHP-Version und Socket prüfen:

```bash
php -v
```
```bash
ls /run/php/
```

Steht dort **nicht** `php8.3-fpm.sock` (z. B. `php8.4-fpm.sock`), merk dir den Namen. Du brauchst ihn in Schritt 5.
Auch in den Befehlen weiter unten (`systemctl reload php8.3-fpm` usw.) die Version entsprechend ersetzen.

## 3. Code holen

```bash
sudo git clone https://github.com/EinfachDevYT/clyve-web.git /var/www/clyve-web
```

> Ist das Repo privat, braucht der Server Zugriff: am einfachsten einen **Deploy Key** anlegen
> (`ssh-keygen -t ed25519 -f ~/.ssh/clyve_deploy`, öffentlichen Schlüssel auf GitHub unter
> Repo → Settings → Deploy keys eintragen) und mit `git@github.com:EinfachDevYT/clyve-web.git` klonen.

Rechte setzen: Der Code gehört `root`, nur `storage/` darf PHP beschreiben.

```bash
sudo chown -R root:root /var/www/clyve-web
```
```bash
sudo chown -R www-data:www-data /var/www/clyve-web/storage && sudo chmod 700 /var/www/clyve-web/storage
```

## 4. Konfiguration

```bash
cd /var/www/clyve-web
```
```bash
sudo cp config/config.example.php config/config.php
```

Geheimen Schlüssel erzeugen und kopieren:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Config bearbeiten:

```bash
sudo nano config/config.php
```

Mindestens diese Werte setzen:

```php
'app_env'       => 'production',
'site_origins'  => ['https://clyve.dev'],
'staff_origins' => ['https://staff.clyve.dev'],
'app_secret'    => '…der erzeugte Schlüssel…',
```

Optional, aber empfohlen:

- `discord_webhook`: Webhook-URL für Benachrichtigungen über neue Anfragen und Tickets
- `staff_ip_allowlist`: z. B. `['203.0.113.10']`, dann ist der Staff-Bereich nur von dieser IP erreichbar
- `business`: Name, Anschrift, E-Mail für Impressum und Rechnungen. `registered` bleibt `false`, bis das Gewerbe angemeldet ist.
- Läuft die Domain über **Cloudflare** (orangene Wolke): `'trusted_ip_header' => 'HTTP_CF_CONNECTING_IP'`

Config schützen (nur root schreibt, nur PHP liest):

```bash
sudo chown root:www-data config/config.php && sudo chmod 640 config/config.php
```

Ersten Admin für den Staff-Bereich anlegen. Wichtig: als `www-data`, damit die Datenbank PHP gehört.

```bash
sudo -u www-data php bin/create-staff.php
```

Das Passwort braucht mindestens 12 Zeichen und darf den Teil vor dem `@` der E-Mail nicht enthalten.

## 5. Nginx

Die beiden Server-Blöcke kopieren. Die bestehende Konfiguration (`default` o. ä.) bleibt, wie sie ist.

```bash
sudo cp deploy/nginx/clyve.dev.conf /etc/nginx/sites-available/clyve.dev
```
```bash
sudo cp deploy/nginx/staff.clyve.dev.conf /etc/nginx/sites-available/staff.clyve.dev
```

Falls dein PHP-Socket in Schritt 2 anders hieß, in beiden Dateien anpassen (Beispiel für Debian 13 mit 8.4):

```bash
sudo sed -i 's/php8.3-fpm.sock/php8.4-fpm.sock/' /etc/nginx/sites-available/clyve.dev /etc/nginx/sites-available/staff.clyve.dev
```

Hat der Server **kein IPv6**, die Zeilen `listen [::]:80;` in beiden Dateien löschen.

Prüfen, dass keine andere Konfiguration schon dieselben Domains benutzt (sollte nur die zwei neuen Dateien zeigen):

```bash
grep -rn "clyve.dev" /etc/nginx/sites-enabled/ /etc/nginx/conf.d/
```

Aktivieren, testen, neu laden:

```bash
sudo ln -s /etc/nginx/sites-available/clyve.dev /etc/nginx/sites-enabled/
```
```bash
sudo ln -s /etc/nginx/sites-available/staff.clyve.dev /etc/nginx/sites-enabled/
```
```bash
sudo nginx -t
```
```bash
sudo systemctl reload nginx
```

> Nur neu laden, wenn `nginx -t` **„syntax is ok“ und „test is successful“** meldet.
> Sonst bleibt die alte Konfiguration aktiv und deine bestehende Seite läuft ungestört weiter.

Schnelltest über HTTP (noch ohne Zertifikat):

```bash
curl -sI http://clyve.dev | head -1
```
```bash
curl -sI http://staff.clyve.dev/login | head -1
```

Beide sollten `HTTP/1.1 200 OK` liefern. Deine bestehende Seite ist weiter unter ihrer bisherigen Adresse erreichbar.

## 6. HTTPS mit Let's Encrypt

```bash
sudo certbot --nginx -d clyve.dev -d www.clyve.dev -d staff.clyve.dev
```

E-Mail angeben, Bedingungen bestätigen. Certbot trägt die Zertifikate in die beiden Clyve-Dateien ein
und leitet HTTP automatisch auf HTTPS um. Die Konfiguration deiner bestehenden Seite fasst es dabei nicht an.

Automatische Verlängerung testen:

```bash
sudo certbot renew --dry-run
```

> Login und Formulare funktionieren **erst ab hier**: Die Config erlaubt nur `https://`-Adressen.

## 7. Abschluss-Check

```bash
curl -sI https://clyve.dev | grep -iE "^HTTP|strict-transport|engineered"
```

Im Browser:

1. `https://clyve.dev` öffnen und unter `/registrieren` ein Test-Kundenkonto anlegen
2. `https://staff.clyve.dev` öffnen und mit dem Admin aus Schritt 4 anmelden
3. Ein Ticket als Kunde erstellen und es im Staff-Bereich beantworten

Diese Adressen müssen **404** liefern, sonst stimmt der Webroot nicht:

```bash
for p in /config/config.php /storage/app.sqlite /app/bootstrap.php /.git/config; do echo "$p $(curl -s -o /dev/null -w '%{http_code}' https://clyve.dev$p)"; done
```

(`/.git/config` darf auch 403 sein.)

## 8. Firewall (falls `ufw` aktiv ist)

```bash
sudo ufw allow 'Nginx Full'
```
```bash
sudo ufw status
```

---

## Updates einspielen

```bash
cd /var/www/clyve-web && sudo git pull --ff-only
```
```bash
sudo systemctl reload php8.3-fpm
```

Haben sich die Dateien in `deploy/nginx/` geändert, die Änderungen in `/etc/nginx/sites-available/` übernehmen
(dabei die von Certbot ergänzten SSL-Zeilen behalten), dann `sudo nginx -t` und `sudo systemctl reload nginx`.

## Backup

Wichtig sind nur zwei Dinge: die Datenbank und die Config.

```bash
sudo mkdir -p /var/backups/clyve && sudo chown www-data:www-data /var/backups/clyve
```
```bash
sudo -u www-data php -r '$s=new SQLite3("/var/www/clyve-web/storage/app.sqlite"); $s->backup(new SQLite3("/var/backups/clyve/app-".date("Y-m-d").".sqlite"));'
```
```bash
sudo cp /var/www/clyve-web/config/config.php /var/backups/clyve/config.php
```

Täglich automatisch um 3:17 Uhr, 30 Tage aufbewahren. Mit `sudo crontab -u www-data -e` diese Zeile eintragen:

```
17 3 * * * php -r '$s=new SQLite3("/var/www/clyve-web/storage/app.sqlite"); $s->backup(new SQLite3("/var/backups/clyve/app-".date("Y-m-d").".sqlite"));' && find /var/backups/clyve -name 'app-*.sqlite' -mtime +30 -delete
```

Die Backups sollten zusätzlich regelmäßig auf einen anderen Rechner kopiert werden.

## Rückgängig machen

Clyve abschalten, ohne die bestehende Seite zu berühren:

```bash
sudo rm /etc/nginx/sites-enabled/clyve.dev /etc/nginx/sites-enabled/staff.clyve.dev
```
```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## Fehlersuche

| Problem | Ursache / Lösung |
|---|---|
| **502 Bad Gateway** | PHP-FPM läuft nicht oder der Socket-Name stimmt nicht: `ls /run/php/`, `sudo systemctl status php8.3-fpm` |
| **„Service unavailable“** | `config/config.php` fehlt, ist für `www-data` nicht lesbar, oder `app_secret` ist nicht gesetzt |
| **403 beim Login/Absenden** | `site_origins` / `staff_origins` passen nicht exakt zur Adresse im Browser (inkl. `https://`), oder HTTPS ist noch nicht eingerichtet |
| **500 / leere Seite** | `storage/` ist nicht für `www-data` beschreibbar: Rechte aus Schritt 3 erneut setzen |
| **Staff-Bereich zeigt nur 404** | `staff_ip_allowlist` ist gesetzt und deine IP steht nicht drin |
| **Statt Clyve erscheint die alte Seite** | DNS zeigt noch nicht auf den Server, oder die Symlinks in `sites-enabled` fehlen |
| **Alte Seite plötzlich weg** | `sudo nginx -t` ausführen, die Meldung zeigt die fehlerhafte Datei. Notfalls Schritt „Rückgängig machen“ |

Logs:

```bash
sudo tail -f /var/log/nginx/clyve.error.log /var/log/nginx/clyve-staff.error.log
```
```bash
sudo journalctl -u php8.3-fpm -n 50
```
