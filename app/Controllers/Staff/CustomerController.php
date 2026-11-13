<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

final class CustomerController
{
    public function index(): void
    {
        Auth::require();
        $q = trim((string)($_GET['q'] ?? ''));
        $q = mb_substr($q, 0, 80);

        $customers = $q === ''
            ? Db::all('SELECT * FROM customers ORDER BY id DESC LIMIT 200')
            : Db::all(
                "SELECT * FROM customers WHERE name LIKE ? ESCAPE '\\' OR email LIKE ? ESCAPE '\\' OR discord LIKE ? ESCAPE '\\' ORDER BY name LIMIT 200",
                array_fill(0, 3, '%' . addcslashes($q, '%_\\') . '%')
            );

        View::render('staff/customers/index', ['customers' => $customers, 'q' => $q], 'Kunden');
    }

    public function show(array $p): void
    {
        Auth::require();
        $customer = Db::one('SELECT * FROM customers WHERE id = ?', [id_param($p)]) ?? abort(404);
        $id = (int)$customer['id'];

        View::render('staff/customers/show', [
            'customer' => $customer,
            'requests' => Db::all('SELECT * FROM requests WHERE customer_id = ? ORDER BY id DESC', [$id]),
            'tickets'  => Db::all('SELECT * FROM tickets WHERE customer_id = ? ORDER BY updated_at DESC', [$id]),
            'invoices' => Db::all('SELECT * FROM invoices WHERE customer_id = ? ORDER BY id DESC', [$id]),
            'services' => Db::all("SELECT * FROM services WHERE customer_id = ? ORDER BY status = 'cancelled', id DESC", [$id]),
            'lastLogin' => \App\Services\Account::lastLogin('customer', $id),
            'resetLink' => $this->takeLink($id),
        ], $customer['name']);
    }

    private function takeLink(int $id): ?array
    {
        $link = $_SESSION['_customer_link'] ?? null;
        unset($_SESSION['_customer_link']);
        return is_array($link) && (int)$link['id'] === $id ? $link : null;
    }

    public function resetTwoFactor(array $p): void
    {
        $admin = Auth::requireAdmin();
        $customer = Db::one('SELECT * FROM customers WHERE id = ?', [id_param($p)]) ?? abort(404);
        \App\Services\Account::disableTwoFactor('customer', (int)$customer['id'], 'twofa_reset');
        \App\Services\Account::revokeSessions('customer', (int)$customer['id']);
        \App\Services\Account::notify($customer, 'Zwei-Faktor-Anmeldung zurückgesetzt', ['auf deine Anfrage hin wurde die Zwei-Faktor-Anmeldung deines Kontos zurückgesetzt. Du kannst sie in deinen Einstellungen neu einrichten.']);
        audit('staff:' . $admin['id'], 'customer_2fa_reset', '#' . $customer['id']);
        flash('success', 'Zwei-Faktor-Anmeldung des Kunden zurückgesetzt.');
        redirect('/kunden/' . $customer['id']);
    }

    public function verifyEmail(array $p): void
    {
        $admin = Auth::requireAdmin();
        $customer = Db::one('SELECT * FROM customers WHERE id = ?', [id_param($p)]) ?? abort(404);
        \App\Services\Account::markVerified((int)$customer['id']);
        audit('staff:' . $admin['id'], 'customer_email_verified', '#' . $customer['id']);
        flash('success', 'E-Mail-Adresse als bestätigt markiert.');
        redirect('/kunden/' . $customer['id']);
    }

    public function sendResetLink(array $p): void
    {
        $admin = Auth::requireAdmin();
        $customer = Db::one('SELECT * FROM customers WHERE id = ?', [id_param($p)]) ?? abort(404);
        $url = \App\Services\Account::baseUrl('customer') . '/passwort-zuruecksetzen/'
            . \App\Services\Account::createToken('reset', 'customer', (int)$customer['id'], '', 86400);
        $sent = \App\Services\Mailer::send($customer['email'], 'Passwort zurücksetzen', \App\Services\Mailer::compose(explode(' ', (string)$customer['name'])[0], [
            'über den folgenden Link kannst du ein neues Passwort für dein Kundenkonto festlegen. Er ist 24 Stunden gültig und funktioniert nur einmal.',
        ], 'Neues Passwort festlegen', $url));
        $_SESSION['_customer_link'] = ['id' => $customer['id'], 'url' => $url, 'sent' => $sent];
        audit('staff:' . $admin['id'], 'customer_reset_link', '#' . $customer['id']);
        flash('success', $sent ? 'Link per E-Mail an den Kunden gesendet.' : 'Link erstellt – gib ihn dem Kunden sicher weiter.');
        redirect('/kunden/' . $customer['id']);
    }
}
