<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

final class RequestController
{
    public const STATUSES = ['new', 'contacted', 'accepted', 'declined', 'done'];

    public function index(): void
    {
        Auth::require();
        $status = in_array($_GET['status'] ?? '', self::STATUSES, true) ? $_GET['status'] : '';
        $requests = $status === ''
            ? Db::all('SELECT * FROM requests ORDER BY id DESC LIMIT 200')
            : Db::all('SELECT * FROM requests WHERE status = ? ORDER BY id DESC LIMIT 200', [$status]);

        View::render('staff/requests/index', ['requests' => $requests, 'status' => $status], 'Anfragen');
    }

    public function show(array $p): void
    {
        Auth::require();
        $request = Db::one(
            'SELECT r.*, c.name AS customer_name FROM requests r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.id = ?',
            [id_param($p)]
        ) ?? abort(404);

        View::render('staff/requests/show', ['request' => $request, 'statuses' => self::STATUSES], 'Anfrage ' . $request['public_id']);
    }

    public function updateStatus(array $p): void
    {
        $staff = Auth::require();
        $id = id_param($p);
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            abort(404);
        }
        Db::exec('UPDATE requests SET status = ? WHERE id = ?', [$status, $id]);
        audit('staff:' . $staff['id'], 'request_status', "#{$id} {$status}");
        flash('success', 'Status aktualisiert.');
        redirect('/anfragen/' . $id);
    }
}
