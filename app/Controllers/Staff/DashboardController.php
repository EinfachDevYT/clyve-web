<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::require();
        View::render('staff/dashboard', [
            'stats' => [
                'requests'  => (int)Db::value("SELECT COUNT(*) FROM requests WHERE status = 'new'"),
                'tickets'   => (int)Db::value("SELECT COUNT(*) FROM tickets WHERE status = 'open'"),
                'customers' => (int)Db::value('SELECT COUNT(*) FROM customers'),
                'unpaid'    => (int)Db::value("SELECT COALESCE(SUM(total_cents), 0) FROM invoices WHERE status = 'issued'"),
            ],
            'requests' => Db::all("SELECT * FROM requests WHERE status IN ('new','contacted') ORDER BY id DESC LIMIT 6"),
            'tickets'  => Db::all(
                "SELECT t.*, c.name AS customer_name FROM tickets t JOIN customers c ON c.id = t.customer_id
                 WHERE t.status = 'open' ORDER BY t.updated_at LIMIT 6"
            ),
        ], 'Dashboard');
    }
}
