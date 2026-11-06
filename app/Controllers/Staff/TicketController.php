<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Controllers\Site\TicketController as SiteTickets;
use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;

final class TicketController
{
    private const STATUSES = ['open', 'answered', 'closed'];

    public function index(): void
    {
        Auth::require();
        $status = in_array($_GET['status'] ?? '', self::STATUSES, true) ? $_GET['status'] : 'open';
        View::render('staff/tickets/index', [
            'status'  => $status,
            'tickets' => Db::all(
                'SELECT t.*, c.name AS customer_name FROM tickets t JOIN customers c ON c.id = t.customer_id
                 WHERE t.status = ? ORDER BY t.updated_at DESC LIMIT 200',
                [$status]
            ),
        ], 'Tickets');
    }

    public function show(array $p): void
    {
        Auth::require();
        $ticket = $this->find(id_param($p));
        View::render('staff/tickets/show', [
            'ticket'     => $ticket,
            'categories' => SiteTickets::CATEGORIES,
            'messages'   => Db::all(
                "SELECT m.*, CASE m.author_type WHEN 'staff' THEN s.name ELSE c.name END AS author_name
                 FROM ticket_messages m
                 LEFT JOIN staff s ON m.author_type = 'staff' AND s.id = m.author_id
                 LEFT JOIN customers c ON m.author_type = 'customer' AND c.id = m.author_id
                 WHERE m.ticket_id = ? ORDER BY m.id",
                [$ticket['id']]
            ),
        ], 'Ticket #' . $ticket['id']);
    }

    public function reply(array $p): void
    {
        $staff = Auth::require();
        $ticket = $this->find(id_param($p));
        $back = '/tickets/' . $ticket['id'];

        $v = new Validator($_POST);
        $body = $v->text('body', 2, 5000, 'Antwort', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        $close = ($_POST['close'] ?? '') === '1';

        Db::transaction(static function () use ($ticket, $staff, $body, $close): void {
            Db::exec("INSERT INTO ticket_messages (ticket_id, author_type, author_id, body) VALUES (?, 'staff', ?, ?)", [$ticket['id'], $staff['id'], $body]);
            Db::exec("UPDATE tickets SET status = ?, updated_at = datetime('now') WHERE id = ?", [$close ? 'closed' : 'answered', $ticket['id']]);
        });
        flash('success', 'Antwort gesendet.');
        redirect($back);
    }

    public function updateStatus(array $p): void
    {
        $staff = Auth::require();
        $ticket = $this->find(id_param($p));
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            abort(404);
        }
        Db::exec("UPDATE tickets SET status = ?, updated_at = datetime('now') WHERE id = ?", [$status, $ticket['id']]);
        audit('staff:' . $staff['id'], 'ticket_status', "#{$ticket['id']} {$status}");
        redirect('/tickets/' . $ticket['id']);
    }

    private function find(int $id): array
    {
        return Db::one(
            'SELECT t.*, c.name AS customer_name, c.email AS customer_email, c.id AS customer_id
             FROM tickets t JOIN customers c ON c.id = t.customer_id WHERE t.id = ?',
            [$id]
        ) ?? abort(404);
    }
}
