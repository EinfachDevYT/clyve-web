<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\Notifier;

final class TicketController
{
    public const CATEGORIES = [
        'general' => 'Allgemein',
        'bug'     => 'Fehler / Bug',
        'change'  => 'Änderungswunsch',
        'billing' => 'Rechnung',
    ];

    public function index(): void
    {
        $user = Auth::require();
        View::render('site/tickets/index', [
            'tickets' => Db::all('SELECT * FROM tickets WHERE customer_id = ? ORDER BY updated_at DESC', [$user['id']]),
        ], 'Support');
    }

    public function create(): void
    {
        Auth::require();
        View::render('site/tickets/create', ['categories' => self::CATEGORIES], 'Neues Ticket');
    }

    public function store(): void
    {
        $user = Auth::require();
        if (!RateLimiter::hit('ticket', 'c' . $user['id'])) {
            fail_back([], '/konto/tickets/neu', 'Bitte warte kurz, bevor du weitere Tickets erstellst.');
        }

        $v = new Validator($_POST);
        $subject = $v->text('subject', 4, 120, 'Betreff');
        $category = $v->oneOf('category', array_keys(self::CATEGORIES), 'Bitte wähle eine Kategorie.');
        $body = $v->text('body', 10, 5000, 'Nachricht', true);
        if ($v->fails()) {
            fail_back($v->errors(), '/konto/tickets/neu');
        }

        $id = Db::transaction(static function () use ($user, $subject, $category, $body): int {
            $id = Db::insert('INSERT INTO tickets (customer_id, subject, category) VALUES (?, ?, ?)', [$user['id'], $subject, $category]);
            Db::exec("INSERT INTO ticket_messages (ticket_id, author_type, author_id, body) VALUES (?, 'customer', ?, ?)", [$id, $user['id'], $body]);
            return $id;
        });
        Notifier::newTicket(['id' => $id, 'subject' => $subject], $user, $body);

        flash('success', 'Ticket erstellt. Wir melden uns schnellstmöglich.');
        redirect('/konto/tickets/' . $id);
    }

    public function show(array $p): void
    {
        $user = Auth::require();
        $ticket = $this->owned(id_param($p), (int)$user['id']);

        View::render('site/tickets/show', [
            'ticket'     => $ticket,
            'categories' => self::CATEGORIES,
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
        $user = Auth::require();
        $ticket = $this->owned(id_param($p), (int)$user['id']);
        $back = '/konto/tickets/' . $ticket['id'];

        if (!RateLimiter::hit('ticket', 'c' . $user['id'])) {
            fail_back([], $back, 'Bitte warte kurz, bevor du erneut antwortest.');
        }
        $v = new Validator($_POST);
        $body = $v->text('body', 2, 5000, 'Nachricht', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        Db::transaction(static function () use ($ticket, $user, $body): void {
            Db::exec("INSERT INTO ticket_messages (ticket_id, author_type, author_id, body) VALUES (?, 'customer', ?, ?)", [$ticket['id'], $user['id'], $body]);
            Db::exec("UPDATE tickets SET status = 'open', updated_at = datetime('now') WHERE id = ?", [$ticket['id']]);
        });
        Notifier::newTicket($ticket, $user, $body);
        redirect($back);
    }

    public function close(array $p): void
    {
        $user = Auth::require();
        $ticket = $this->owned(id_param($p), (int)$user['id']);
        Db::exec("UPDATE tickets SET status = 'closed', updated_at = datetime('now') WHERE id = ?", [$ticket['id']]);
        flash('info', 'Ticket geschlossen.');
        redirect('/konto/tickets/' . $ticket['id']);
    }

    private function owned(int $id, int $customerId): array
    {
        $ticket = Db::one('SELECT * FROM tickets WHERE id = ? AND customer_id = ?', [$id, $customerId]);
        if (!$ticket) {
            abort(404);
        }
        return $ticket;
    }
}
