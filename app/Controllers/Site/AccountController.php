<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;

final class AccountController
{
    public function dashboard(): void
    {
        $user = Auth::require();
        $id = (int)$user['id'];

        View::render('site/account/dashboard', [
            'openTickets'  => (int)Db::value("SELECT COUNT(*) FROM tickets WHERE customer_id = ? AND status <> 'closed'", [$id]),
            'openInvoices' => (int)Db::value("SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND status = 'issued'", [$id]),
            'openAmount'   => (int)Db::value("SELECT COALESCE(SUM(total_cents), 0) FROM invoices WHERE customer_id = ? AND status = 'issued'", [$id]),
            'requests'     => Db::all('SELECT * FROM requests WHERE customer_id = ? ORDER BY id DESC LIMIT 5', [$id]),
            'tickets'      => Db::all('SELECT * FROM tickets WHERE customer_id = ? ORDER BY updated_at DESC LIMIT 5', [$id]),
        ], 'Übersicht');
    }

    public function requests(): void
    {
        $user = Auth::require();
        View::render('site/account/requests', [
            'requests' => Db::all('SELECT * FROM requests WHERE customer_id = ? ORDER BY id DESC', [$user['id']]),
        ], 'Meine Anfragen');
    }

    public function profile(): void
    {
        Auth::require();
        View::render('site/account/profile', [], 'Profil');
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
            fail_back($v->errors(), '/konto/profil');
        }

        Db::exec(
            'UPDATE customers SET name = ?, discord = ?, company = ?, street = ?, zip = ?, city = ?, country = ? WHERE id = ?',
            [...array_values($data), $user['id']]
        );
        flash('success', 'Profil gespeichert.');
        redirect('/konto/profil');
    }

    public function updatePassword(): void
    {
        $user = Auth::require();
        $hash = (string)Db::value('SELECT password_hash FROM customers WHERE id = ?', [$user['id']]);
        $current = (string)($_POST['password_current'] ?? '');
        $new = (string)($_POST['password'] ?? '');

        $errors = [];
        if (!password_verify($current, $hash)) {
            $errors['password_current'] = 'Das aktuelle Passwort ist falsch.';
        } elseif ($problem = Auth::passwordProblem($new, $user['email'])) {
            $errors['password'] = $problem;
        } elseif (!hash_equals($new, (string)($_POST['password_confirm'] ?? ''))) {
            $errors['password_confirm'] = 'Die Passwörter stimmen nicht überein.';
        }
        if ($errors) {
            fail_back($errors, '/konto/profil');
        }

        Db::exec('UPDATE customers SET password_hash = ? WHERE id = ?', [Auth::hash($new), $user['id']]);
        Auth::login((int)$user['id']);
        flash('success', 'Passwort geändert.');
        redirect('/konto/profil');
    }
}
