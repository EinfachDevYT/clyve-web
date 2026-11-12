<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Controllers\Concerns\SecuritySettings;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\Account;

/** Personal settings of the signed-in staff member. */
final class ProfileController
{
    use SecuritySettings;

    protected function base(): string
    {
        return '/einstellungen';
    }

    public function profile(): void
    {
        $user = Auth::require();
        View::render('staff/profile', ['lastLogin' => Account::lastLogin('staff', (int)$user['id'])], 'Einstellungen');
    }

    public function updateProfile(): void
    {
        $user = Auth::require();
        $v = new Validator($_POST);
        $name = $v->text('name', 2, 80, 'Name');
        if ($v->fails()) {
            fail_back($v->errors(), '/einstellungen');
        }
        Db::exec('UPDATE staff SET name = ? WHERE id = ?', [$name, $user['id']]);
        flash('success', 'Gespeichert.');
        redirect('/einstellungen');
    }
}
