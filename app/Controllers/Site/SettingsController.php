<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Concerns\SecuritySettings;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
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
