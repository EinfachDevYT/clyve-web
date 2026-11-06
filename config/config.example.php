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

    // Datenbank. Standard: SQLite-Datei außerhalb des Webroots.
    // MySQL: 'dsn' => 'mysql:host=127.0.0.1;dbname=commissions;charset=utf8mb4', 'user' => '...', 'pass' => '...'
    'db' => [
        'dsn'  => 'sqlite:' . dirname(__DIR__) . '/storage/app.sqlite',
        'user' => null,
        'pass' => null,
    ],

    // Benachrichtigungen bei neuen Anfragen / Tickets
    'discord_webhook' => '',
    'mail_to'         => '',
    'mail_from'       => 'no-reply@clyve.dev',

    // Rate-Limits
    'rate_limits' => [
        'request' => [['window' => 600, 'max' => 3], ['window' => 86400, 'max' => 8]],
        'login'   => [['window' => 900, 'max' => 8]],
        'register'=> [['window' => 3600, 'max' => 3]],
        'ticket'  => [['window' => 600, 'max' => 6]],
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
    'business' => [
        // Solange false: Rechnungen können nur als ENTWURF angelegt, aber nicht ausgestellt werden.
        // Erst nach Gewerbeanmeldung auf true setzen.
        'registered'       => false,
        // Kleinunternehmerregelung (§ 19 UStG): keine Umsatzsteuer ausweisen.
        'kleinunternehmer' => true,
        'vat_rate'         => 19,
        'name'             => 'Clyve.dev',
        'owner'            => 'Vorname Nachname',
        'street'           => 'Straße 1',
        'zip'              => '66000',
        'city'             => 'Saarbrücken',
        'email'            => 'kontakt@clyve.dev',
        'tax_number'       => '',   // Steuernummer vom Finanzamt
        'vat_id'           => '',   // USt-IdNr. (falls vorhanden)
        'iban'             => '',
        'bic'              => '',
        'bank'             => '',
        'invoice_prefix'   => 'CLY',
        'payment_days'     => 14,
    ],
];
