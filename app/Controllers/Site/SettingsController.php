<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Concerns\SecuritySettings;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\CustomerDeletion;
use App\Services\CustomerProfile;

/** Personal settings in the customer area: profile and security. */
final class SettingsController
{
    use SecuritySettings;

    protected function base(): string
    {
        return '/konto/einstellungen';
    }

    public function legacyProfile(): void
    {
        redirect('/konto/einstellungen', 301);
    }

    public function closeAccount(): void
    {
        $user = Auth::require();
        $back = '/konto/einstellungen/sicherheit';
        $customer = \App\Services\Account::find('customer', (int)$user['id']);
        if ($blockers = CustomerDeletion::blockers($customer)) {
            fail_back([], $back, $blockers[0]);
        }
        if (!\App\Core\RateLimiter::hit('reauth', 'customer:' . $user['id'], [['window' => 900, 'max' => 10]])) {
            fail_back([], $back, 'Zu viele Versuche. Bitte warte ein paar Minuten.');
        }
        if (!Auth::checkPassword((int)$user['id'], (string)($_POST['password_close'] ?? ''))) {
            fail_back(['password_close' => 'Das Passwort ist falsch.'], $back);
        }
        if ($user['has_2fa'] && !\App\Services\Account::verifySecondFactor('customer', $customer, (string)($_POST['code_close'] ?? ''))) {
            fail_back(['code_close' => 'Der Code stimmt nicht.'], $back);
        }
        if (($_POST['understood'] ?? '') !== '1') {
            fail_back(['understood' => 'Bitte bestätige, dass du dein Konto schließen möchtest.'], $back);
        }

        $result = CustomerDeletion::close($customer);
        CustomerDeletion::confirm($customer, $result);
        audit('customer:' . $user['id'], 'customer_closed_self', '#' . $user['id'] . ' ' . $result);
        Auth::logout();
        flash('success', 'Dein Konto wurde geschlossen. Eine Bestätigung ist unterwegs – mach\'s gut! 🌸');
        redirect('/');
    }

    public function profile(): void
    {
        Auth::require();
        View::render('site/account/settings', [], 'Einstellungen', 'portal');
    }

    public function updateProfile(): void
    {
        $user = Auth::require();
        $v = new Validator($_POST);
        $data = CustomerProfile::validate($v, $_POST) + ['discord' => $v->discord('discord')];
        if ($v->fails()) {
            fail_back($v->errors(), '/konto/einstellungen');
        }
        Db::exec(
            'UPDATE customers SET ' . implode(', ', array_map(static fn(string $c) => "{$c} = ?", array_keys($data))) . ' WHERE id = ?',
            [...array_values($data), $user['id']]
        );
        flash('success', 'Profil gespeichert.');
        redirect('/konto/einstellungen');
    }
}
