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
        \App\Services\Hosting::tick();
        View::render('staff/dashboard', [
            'expiring' => Db::all(
                "SELECT s.*, c.name AS customer_name FROM services s JOIN customers c ON c.id = s.customer_id
                 WHERE s.status = 'active' AND s.expires_on IS NOT NULL AND s.expires_on <= date('now', 'localtime', '+14 days') ORDER BY s.expires_on LIMIT 6"
            ),
            'stats' => [
                'requests'  => (int)Db::value("SELECT COUNT(*) FROM requests WHERE unread_staff = 1 OR status = 'new'"),
                'tickets'   => (int)Db::value("SELECT COUNT(*) FROM tickets WHERE status = 'open'"),
                'customers' => (int)Db::value('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL'),
                'unpaid'    => (int)Db::value("SELECT COALESCE(SUM(total_cents), 0) FROM invoices WHERE status = 'issued'"),
            ],
            'requests' => Db::all("SELECT * FROM requests WHERE status IN ('new','in_review','offer_sent','accepted','in_progress') ORDER BY unread_staff DESC, updated_at DESC LIMIT 6"),
            'tickets'  => Db::all(
                "SELECT t.*, c.name AS customer_name FROM tickets t JOIN customers c ON c.id = t.customer_id
                 WHERE t.status = 'open' ORDER BY t.updated_at LIMIT 6"
            ),
        ], 'Dashboard');
    }
}
