<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Validator;
use App\Core\View;
use App\Services\InvoiceService;
use App\Services\Mailer;
use App\Services\Settings;
use App\Services\Tebex;

final class SettingsController
{
    public const TABS = [
        'allgemein'     => ['/einstellungen', 'Allgemein'],
        'betreiber'     => ['/einstellungen/betreiber', 'Betreiber & Rechtliches'],
        'email'         => ['/einstellungen/email', 'E-Mail'],
        'integrationen' => ['/einstellungen/integrationen', 'Integrationen'],
        'hosting'       => ['/einstellungen/hosting', 'Hosting & Lizenzen'],
    ];

    /** Fields that must be filled before Impressum and Datenschutz are complete. */
    public const LEGAL_REQUIRED = ['name', 'owner', 'street', 'zip', 'city', 'email'];
    private const PLACEHOLDERS = ['Vorname Nachname', 'Straße 1', '66000'];

    public static function legalMissing(): array
    {
        $missing = [];
        foreach (self::LEGAL_REQUIRED as $field) {
            $value = trim((string)config('business.' . $field, ''));
            if ($value === '' || in_array($value, self::PLACEHOLDERS, true)) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function render(string $tab, array $data, string $title): void
    {
        View::render('staff/settings/' . $tab, $data + ['tab' => $tab, 'tabs' => self::TABS], $title);
    }

    /* ---------- Allgemein ---------- */

    public function show(): void
    {
        Auth::requireAdmin();
        $this->render('general', ['settings' => Settings::all()], 'Einstellungen');
    }

    public function update(): void
    {
        $staff = Auth::requireAdmin();
        $v = new Validator($_POST);
        $values = [
            'availability_label'      => $v->text('availability_label', 2, 60, 'Verfügbarkeit'),
            'response_time'           => $v->text('response_time', 1, 20, 'Antwortzeit'),
            'requests_closed_message' => $v->text('requests_closed_message', 10, 300, 'Hinweis bei geschlossenen Anfragen', true),
            'announcement'            => $v->text('announcement', 0, 200, 'Ankündigung'),
        ];
        if ($v->fails()) {
            fail_back($v->errors(), '/einstellungen');
        }
        foreach (Settings::TOGGLES as $key) {
            $values[$key] = ($_POST[$key] ?? '') === '1' ? '1' : '0';
        }
        Settings::set($values);
        audit('staff:' . $staff['id'], 'settings_general');
        flash('success', 'Einstellungen gespeichert.');
        redirect('/einstellungen');
    }

    /* ---------- Betreiber & Rechtliches ---------- */

    public function business(): void
    {
        Auth::requireAdmin();
        $this->render('business', ['b' => (array)config('business', []), 'missing' => self::legalMissing()], 'Betreiber & Rechtliches');
    }

    public function updateBusiness(): void
    {
        $staff = Auth::requireAdmin();
        $back = '/einstellungen/betreiber';
        $v = new Validator($_POST);
        $d = [
            'name'           => $v->text('name', 2, 80, 'Name der Website / Marke'),
            'owner'          => $v->text('owner', 3, 80, 'Vor- und Nachname'),
            'street'         => $v->text('street', 3, 120, 'Straße und Hausnummer'),
            'zip'            => $v->text('zip', 4, 10, 'PLZ'),
            'city'           => $v->text('city', 2, 80, 'Ort'),
            'country'        => $v->text('country', 2, 60, 'Land'),
            'email'          => $v->email('email'),
            'phone'          => $v->text('phone', 0, 30, 'Telefon'),
            'hosting'        => $v->text('hosting', 0, 200, 'Hosting-Anbieter'),
            'mode'           => $v->oneOf('mode', array_keys(InvoiceService::MODES), 'Bitte einen Modus wählen.'),
            'tax_number'     => $v->text('tax_number', 0, 30, 'Steuernummer'),
            'vat_id'         => strtoupper(str_replace(' ', '', $v->text('vat_id', 0, 20, 'USt-IdNr.'))),
            'iban'           => strtoupper(str_replace(' ', '', $v->text('iban', 0, 42, 'IBAN'))),
            'bic'            => strtoupper(str_replace(' ', '', $v->text('bic', 0, 11, 'BIC'))),
            'bank'           => $v->text('bank', 0, 80, 'Bank'),
            'invoice_prefix' => strtoupper($v->text('invoice_prefix', 2, 6, 'Rechnungspräfix')),
            'payment_days'   => (int)($_POST['payment_days'] ?? 14),
            'vat_rate'       => (int)($_POST['vat_rate'] ?? 19),
        ];
        if ($d['zip'] !== '' && !preg_match('/^[A-Za-z0-9 \-]{4,10}$/', $d['zip'])) {
            $v->addError('zip', 'Ungültige PLZ.');
        }
        if ($d['phone'] !== '' && !preg_match('/^\+?[0-9 ()\/\-]{6,30}$/', $d['phone'])) {
            $v->addError('phone', 'Ungültige Telefonnummer.');
        }
        if ($d['vat_id'] !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9]{8,12}$/', $d['vat_id'])) {
            $v->addError('vat_id', 'Ungültige USt-IdNr. (z. B. DE123456789).');
        }
        if ($d['iban'] !== '' && !self::validIban($d['iban'])) {
            $v->addError('iban', 'Die IBAN ist ungültig (Prüfsumme stimmt nicht).');
        }
        if ($d['bic'] !== '' && !preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $d['bic'])) {
            $v->addError('bic', 'Ungültige BIC.');
        }
        if (!preg_match('/^[A-Z0-9]{2,6}$/', $d['invoice_prefix']) || $d['invoice_prefix'] === 'Q') {
            $v->addError('invoice_prefix', '2–6 Buchstaben/Ziffern, nicht „Q“ (reserviert für Quittungen).');
        }
        if ($d['payment_days'] < 0 || $d['payment_days'] > 90) {
            $v->addError('payment_days', 'Zahlungsziel: 0–90 Tage.');
        }
        if ($d['vat_rate'] < 0 || $d['vat_rate'] > 30) {
            $v->addError('vat_rate', 'Steuersatz: 0–30 %.');
        }
        if ($d['mode'] !== 'hobby' && $d['tax_number'] === '' && $d['vat_id'] === '') {
            $v->addError('tax_number', 'Für Rechnungen ist eine Steuernummer oder USt-IdNr. Pflicht.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        $values = [];
        foreach ($d as $key => $value) {
            $values['business.' . $key] = $value;
        }
        Settings::setConfig($values);
        audit('staff:' . $staff['id'], 'settings_business', $d['mode']);
        flash('success', 'Betreiberangaben gespeichert. Impressum, Datenschutz und neue Belege nutzen sie ab sofort.');
        redirect($back);
    }

    public static function validIban(string $iban): bool
    {
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string)(ord($ch) - 55) : $ch;
        }
        $mod = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $mod = (int)(($mod . $chunk) % 97);
        }
        return $mod === 1;
    }

    /* ---------- E-Mail ---------- */

    public function email(): void
    {
        Auth::requireAdmin();
        $this->render('email', [
            'smtp'        => (array)config('smtp', []),
            'mailTo'      => (string)config('mail_to', ''),
            'hasPassword' => Settings::hasSecret('smtp.password'),
            'enabled'     => Mailer::enabled(),
        ], 'E-Mail');
    }

    public function updateEmail(): void
    {
        $staff = Auth::requireAdmin();
        $back = '/einstellungen/email';
        $v = new Validator($_POST);
        $host = strtolower($v->text('host', 0, 120, 'Server'));
        $port = (int)($_POST['port'] ?? 587);
        $encryption = $v->oneOf('encryption', ['tls', 'ssl', 'none'], 'Bitte eine Verschlüsselung wählen.');
        $username = $v->text('username', 0, 160, 'Benutzername');
        $from = $v->email('from', $host !== '');
        $fromName = $v->text('from_name', 0, 80, 'Absendername');
        $mailTo = $v->email('mail_to', false);

        if ($host !== '' && !preg_match('/^(localhost|[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+|127\.0\.0\.1)$/', $host)) {
            $v->addError('host', 'Ungültiger Servername (z. B. mail.clyve.dev).');
        }
        if ($port < 1 || $port > 65535) {
            $v->addError('port', 'Ungültiger Port.');
        }
        if ($encryption === 'none' && !in_array($host, ['', 'localhost', '127.0.0.1'], true)) {
            $v->addError('encryption', 'Ohne Verschlüsselung nur für einen Mailserver auf demselben Server (localhost).');
        }
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        $values = [
            'smtp.host' => $host, 'smtp.port' => $port, 'smtp.encryption' => $encryption,
            'smtp.username' => $username, 'smtp.from' => $from, 'smtp.from_name' => $fromName, 'mail_to' => $mailTo,
        ];
        $password = (string)($_POST['password'] ?? '');
        if (($_POST['password_clear'] ?? '') === '1') {
            $values['smtp.password'] = '';
        } elseif ($password !== '') {
            if (strlen($password) > 200) {
                fail_back(['password' => 'Passwort zu lang.'], $back);
            }
            $values['smtp.password'] = $password;
        }
        Settings::setConfig($values);
        audit('staff:' . $staff['id'], 'settings_email');
        flash('success', $host === '' ? 'Gespeichert. E-Mail-Versand ist aus.' : 'E-Mail-Einstellungen gespeichert. Schick dir jetzt eine Test-E-Mail.');
        redirect($back);
    }

    public function testMail(): void
    {
        $staff = Auth::requireAdmin();
        $back = '/einstellungen/email';
        if (!Mailer::enabled()) {
            fail_back([], $back, 'Bitte zuerst Server und Absenderadresse speichern.');
        }
        $ok = Mailer::send(
            (string)$staff['email'],
            'Test-E-Mail von ' . config('business.name', 'Clyve.dev'),
            Mailer::compose((string)$staff['name'], ['das ist eine Test-E-Mail. Wenn du sie liest, funktioniert der Versand.'])
        );
        flash($ok ? 'success' : 'error', $ok
            ? 'Test-E-Mail an ' . $staff['email'] . ' gesendet. Schau auch im Spam-Ordner nach.'
            : 'Versand fehlgeschlagen. Prüfe Server, Port, Verschlüsselung und Zugangsdaten (Details im PHP-Fehlerlog).');
        redirect($back);
    }

    /* ---------- Hosting & Lizenzen ---------- */

    public function hosting(): void
    {
        Auth::requireAdmin();
        $this->render('hosting', ['settings' => Settings::all()], 'Hosting & Lizenzen');
    }

    public function updateHosting(): void
    {
        $staff = Auth::requireAdmin();
        $back = '/einstellungen/hosting';
        $v = new Validator($_POST);
        $grace = (string)($_POST['hosting_grace_days'] ?? '');
        if (!ctype_digit($grace) || (int)$grace > 60) {
            $v->addError('hosting_grace_days', 'Bitte 0 bis 60 Tage.');
        }
        $interval = (string)($_POST['hosting_check_interval'] ?? '');
        if (!ctype_digit($interval) || (int)$interval < 60 || (int)$interval > 86400) {
            $v->addError('hosting_check_interval', 'Bitte 60 bis 86400 Sekunden.');
        }
        $reminders = trim((string)($_POST['hosting_reminder_days'] ?? ''));
        $days = $reminders === '' ? [] : array_map('trim', explode(',', $reminders));
        foreach ($days as $d) {
            if (!ctype_digit($d) || (int)$d < 1 || (int)$d > 60) {
                $v->addError('hosting_reminder_days', 'Tage durch Komma getrennt, jeweils 1 bis 60 (z. B. 14,3).');
                break;
            }
        }
        if (count($days) > 5) {
            $v->addError('hosting_reminder_days', 'Maximal 5 Erinnerungen.');
        }
        $message = $v->text('hosting_suspend_message', 5, 200, 'Sperrhinweis');
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        $days = array_unique(array_map('intval', $days));
        rsort($days);
        $values = [
            'hosting_grace_days'      => (string)(int)$grace,
            'hosting_check_interval'  => (string)(int)$interval,
            'hosting_reminder_days'   => implode(',', $days),
            'hosting_suspend_message' => $message,
        ];
        foreach (Settings::HOSTING_TOGGLES as $key) {
            $values[$key] = ($_POST[$key] ?? '') === '1' ? '1' : '0';
        }
        Settings::set($values);
        audit('staff:' . $staff['id'], 'settings_hosting');
        flash('success', 'Hosting-Einstellungen gespeichert.');
        redirect($back);
    }

    /* ---------- Integrationen ---------- */

    public function integrations(): void
    {
        Auth::requireAdmin();
        $this->render('integrations', [
            'hasWebhook' => Settings::hasSecret('discord_webhook'),
            'tebex'      => (array)config('tebex', []),
            'imageHosts' => Tebex::imageHosts(),
        ], 'Integrationen');
    }

    public function updateIntegrations(): void
    {
        $staff = Auth::requireAdmin();
        $back = '/einstellungen/integrationen';
        $v = new Validator($_POST);
        $values = [];

        $webhook = trim((string)($_POST['discord_webhook'] ?? ''));
        if (($_POST['discord_webhook_clear'] ?? '') === '1') {
            $values['discord_webhook'] = '';
        } elseif ($webhook !== '') {
            if (!preg_match('#^https://(?:ptb\.|canary\.)?discord(?:app)?\.com/api/webhooks/\d+/[\w-]+$#', $webhook)) {
                $v->addError('discord_webhook', 'Das ist keine gültige Discord-Webhook-URL.');
            }
            $values['discord_webhook'] = $webhook;
        }

        $token = $v->text('webstore_token', 0, 80, 'Webstore-Identifier');
        if ($token !== '' && !preg_match('/^[a-z0-9-]{4,80}$/i', $token)) {
            $v->addError('webstore_token', 'Ungültiger Webstore-Identifier.');
        }
        $store = rtrim($v->text('store_url', 0, 120, 'Shop-Adresse'), '/');
        if ($store !== '' && (parse_url($store, PHP_URL_SCHEME) !== 'https' || !parse_url($store, PHP_URL_HOST))) {
            $v->addError('store_url', 'Bitte die https-Adresse deines Tebex-Shops angeben.');
        }
        $hosts = array_values(array_unique(array_filter(array_map(
            static fn($h) => strtolower(trim($h)),
            preg_split('/[\s,]+/', (string)($_POST['image_hosts'] ?? '')) ?: []
        ))));
        foreach ($hosts as $h) {
            if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $h)) {
                $v->addError('image_hosts', 'Ungültiger Host: ' . $h);
            }
        }
        if (count($hosts) > 5) {
            $v->addError('image_hosts', 'Maximal 5 Bild-Hosts.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        $values += ['tebex.webstore_token' => $token, 'tebex.store_url' => $store, 'tebex.image_hosts' => $hosts ?: Tebex::DEFAULT_IMAGE_HOSTS];
        Settings::setConfig($values);
        audit('staff:' . $staff['id'], 'settings_integrations');
        flash('success', 'Integrationen gespeichert.');
        redirect($back);
    }

    public function testDiscord(): void
    {
        $staff = Auth::requireAdmin();
        $ok = \App\Services\Notifier::event('Test-Nachricht', 'Wenn du das liest, kommen Benachrichtigungen von ' . config('business.name', 'Clyve.dev') . ' an.', ['Von' => (string)$staff['name']], 'Test');
        flash($ok ? 'success' : 'error', $ok ? 'Test-Nachricht an Discord gesendet.' : 'Discord hat die Nachricht nicht angenommen. Ist der Webhook richtig?');
        redirect('/einstellungen/integrationen');
    }
}
