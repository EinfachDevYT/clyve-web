<?php
/**
 * Kopiere diese Datei nach config/config.php und trage deine Werte ein.
 * config/config.php wird NICHT committet (.gitignore).
 */
return [
    // 'production' auf dem Server, 'local' nur zum Entwickeln.
    'app_env' => 'production',

    // Erlaubte Origins (Schema + Host, ohne Slash). POSTs von anderen Seiten werden abgelehnt.
    'site_origins'  => ['https://clyve.dev'],
    'staff_origins' => ['https://staff.clyve.dev'],

    // Zufälliger, langer Schlüssel:  php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'CHANGE_ME',

    // Datenbank: SQLite-Datei außerhalb des Webroots. Wird beim ersten Aufruf angelegt und automatisch migriert.
    'db' => [
        'dsn'  => 'sqlite:' . dirname(__DIR__) . '/storage/app.sqlite',
        'user' => null,
        'pass' => null,
    ],

    // Benachrichtigungen an dich bei neuen Anfragen, Antworten und Tickets
    'discord_webhook' => '',
    'mail_to'         => '',   // optional zusätzlich per E-Mail (braucht SMTP unten)

    // Rate-Limits
    'rate_limits' => [
        'request' => [['window' => 600, 'max' => 3], ['window' => 86400, 'max' => 8]],
        'login'   => [['window' => 900, 'max' => 8]],
        'register'=> [['window' => 3600, 'max' => 3]],
        'ticket'  => [['window' => 600, 'max' => 6]],
        'license'      => [['window' => 60, 'max' => 240]],
        'license_fail' => [['window' => 600, 'max' => 30]],
    ],
    'min_fill_seconds' => 4,

    // Hinter Cloudflare/Reverse-Proxy? Dann z. B. 'HTTP_CF_CONNECTING_IP'.
    // NUR setzen, wenn der Server ausschließlich über diesen Proxy erreichbar ist!
    'trusted_ip_header' => '',

    // Mitarbeiterbereich nur von diesen IPs erreichbar (leer = keine Einschränkung).
    'staff_ip_allowlist' => [],

    // Session-Timeouts (Sekunden Inaktivität)
    'session_idle' => ['site' => 7200, 'staff' => 1800],

    // ── Rechnungen ──────────────────────────────────────────────
    // ── Belege & Rechnungen ─────────────────────────────────────
    'business' => [
        // hobby            = Privatperson: Angebote auch kostenlos, Belege heißen „Quittung“ (Nummern Q-2026-0001), keine USt.
        // kleinunternehmer = nach Gewerbeanmeldung: Rechnungen mit Hinweis auf § 19 UStG
        // regular          = Rechnungen mit Umsatzsteuer (vat_rate)
        'mode'           => 'hobby',
        'vat_rate'       => 19,
        'name'           => 'Clyve.dev',
        'owner'          => 'Vorname Nachname',
        'street'         => 'Straße 1',
        'zip'            => '66000',
        'city'           => 'Saarbrücken',
        'email'          => 'kontakt@clyve.dev',
        'phone'          => '',   // optional, erscheint im Impressum
        // Hosting-Anbieter deines Servers für die Datenschutzerklärung, z. B. 'netcup GmbH, Daimlerstraße 25, 76185 Karlsruhe'
        'hosting'        => '',
        'dsa_email'      => '',   // optional: Kontaktstelle nach DSA (Art. 11/12/16), erscheint im Impressum
        'dsa_languages'  => 'Deutsch, Englisch',
        'tax_number'     => '',   // Steuernummer (ab Gewerbe)
        'vat_id'         => '',   // USt-IdNr. (falls vorhanden)
        'iban'           => '',
        'bic'            => '',
        'bank'           => '',
        'invoice_prefix' => 'CLY',
        'payment_days'   => 14,
    ],

    // ── E-Mail (SMTP) ────────────────────────────────────────────
    // Für Antworten an Kunden. Leer lassen = keine E-Mails, Kunden sehen alles über ihren Link/Konto.
    // Mailcow: Postfach z. B. no-reply@clyve.dev anlegen, Host = dein Mailserver, Port 587 (STARTTLS).
    'smtp' => [
        'host'       => '',
        'port'       => 587,
        'encryption' => 'tls',   // tls = STARTTLS (587), ssl = 465
        'username'   => '',
        'password'   => '',
        'from'       => 'no-reply@clyve.dev',
        'from_name'  => 'Clyve.dev',
    ],

    // ── Tebex (Script-Shop) ──────────────────────────────────────
    // Datenbank-Backups (laufen über bin/cron.php). Standard: storage/backups, 14 Tage, ab 3 Uhr.
    'backup' => [
        'dir'       => dirname(__DIR__) . '/storage/backups',
        'keep_days' => 14,
        'hour'      => 3,
    ],

    'tebex' => [
        // Öffentlicher Webstore-Identifier aus dem Tebex-Panel (Integrations → Headless API). Leer = nur manuelle Produkte.
        'webstore_token' => '',
        // Adresse deines Tebex-Shops, z. B. https://clyve.tebex.io
        'store_url'      => '',
        // Hosts, von denen Produktbilder geladen werden dürfen
        'image_hosts'    => ['dunb17ur4ymx4.cloudfront.net'],
    ],
];
