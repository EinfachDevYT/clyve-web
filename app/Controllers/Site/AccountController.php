<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
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
            'requests'     => Db::all('SELECT * FROM requests WHERE customer_id = ? ORDER BY updated_at DESC LIMIT 5', [$id]),
            'tickets'      => Db::all('SELECT * FROM tickets WHERE customer_id = ? ORDER BY updated_at DESC LIMIT 5', [$id]),
            'attention'    => \App\Services\Settings::on('hosting_customer_view') ? Db::all(
                "SELECT * FROM services WHERE customer_id = ? AND (status = 'suspended' OR (status = 'active' AND expires_on IS NOT NULL AND expires_on <= date('now', 'localtime', '+14 days'))) ORDER BY expires_on",
                [$id]
            ) : [],
        ], 'Übersicht');
    }

    public function requests(): void
    {
        $user = Auth::require();
        View::render('site/account/requests', [
            'requests' => Db::all('SELECT * FROM requests WHERE customer_id = ? ORDER BY updated_at DESC, id DESC', [$user['id']]),
        ], 'Meine Anfragen');
    }
}
