<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Concerns\SecuritySettings;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;

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
        View::render('site/account/settings', [], 'Einstellungen');
    }

    public function updateProfile(): void
    {
        $user = Auth::require();
        $v = new Validator($_POST);
        $data = [
            'name'    => $v->text('name', 2, 80, 'Name'),
            'discord' => $v->discord('discord'),
            'company' => $v->text('company', 0, 120, 'Firma'),
            'street'  => $v->text('street', 0, 120, 'Straße'),
            'zip'     => $v->text('zip', 0, 10, 'PLZ'),
            'city'    => $v->text('city', 0, 80, 'Ort'),
            'country' => $v->text('country', 2, 60, 'Land'),
        ];
        if ($data['zip'] !== '' && !preg_match('/^[A-Za-z0-9 \-]{3,10}$/', $data['zip'])) {
            $v->addError('zip', 'Ungültige PLZ.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), '/konto/einstellungen');
        }
        Db::exec(
            'UPDATE customers SET name = ?, discord = ?, company = ?, street = ?, zip = ?, city = ?, country = ? WHERE id = ?',
            [...array_values($data), $user['id']]
        );
        flash('success', 'Profil gespeichert.');
        redirect('/konto/einstellungen');
    }
}
