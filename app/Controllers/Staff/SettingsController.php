<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Validator;
use App\Core\View;
use App\Services\InvoiceService;
use App\Services\Mailer;
use App\Services\Settings;

final class SettingsController
{
    public function show(): void
    {
        Auth::requireAdmin();
        View::render('staff/settings', [
            'settings'    => Settings::all(),
            'mode'        => InvoiceService::mode(),
            'mailEnabled' => Mailer::enabled(),
            'tebexReady'  => (string)config('tebex.webstore_token', '') !== '',
        ], 'Einstellungen');
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
        audit('staff:' . $staff['id'], 'settings_updated');
        flash('success', 'Einstellungen gespeichert.');
        redirect('/einstellungen');
    }

    public function testMail(): void
    {
        $staff = Auth::requireAdmin();
        if (!Mailer::enabled()) {
            fail_back([], '/einstellungen', 'SMTP ist nicht eingerichtet (config.php → smtp).');
        }
        $ok = Mailer::send(
            (string)$staff['email'],
            'Test-E-Mail von Clyve.dev',
            Mailer::compose((string)$staff['name'], ['das ist eine Test-E-Mail. Wenn du sie liest, funktioniert der Versand.'])
        );
        flash($ok ? 'success' : 'error', $ok ? 'Test-E-Mail an ' . $staff['email'] . ' gesendet.' : 'Versand fehlgeschlagen – Details im PHP-Fehlerlog.');
        redirect('/einstellungen');
    }
}
