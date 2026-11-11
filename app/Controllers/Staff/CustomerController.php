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
        ], $customer['name']);
    }
}
