<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\InvoiceService;
use App\Services\Mailer;
use App\Services\RequestService;
use RuntimeException;

final class RequestController
{
    public function index(): void
    {
        Auth::require();
        $filter = (string)($_GET['status'] ?? 'open');
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);

        $where = [];
        $params = [];
        if ($filter === 'open') {
            $where[] = "r.status IN ('" . implode("','", RequestService::OPEN_STATUSES) . "')";
        } elseif (isset(RequestService::STATUSES[$filter])) {
            $where[] = 'r.status = ?';
            $params[] = $filter;
        } else {
            $filter = 'all';
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = "(r.public_id LIKE ? ESCAPE '\\' OR r.name LIKE ? ESCAPE '\\' OR r.title LIKE ? ESCAPE '\\' OR r.email LIKE ? ESCAPE '\\' OR r.discord LIKE ? ESCAPE '\\')";
            array_push($params, $like, $like, $like, $like, $like);
        }

        $requests = Db::all(
            'SELECT r.* FROM requests r' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY r.unread_staff DESC, r.updated_at DESC LIMIT 200',
            $params
        );
        View::render('staff/requests/index', ['requests' => $requests, 'status' => $filter, 'q' => $q], 'Anfragen');
    }

    public function show(array $p): void
    {
        Auth::require();
        $request = $this->find($p);
        RequestService::markRead((int)$request['id'], 'staff');

        View::render('staff/requests/show', [
            'request'   => $request,
            'messages'  => RequestService::messages((int)$request['id'], true),
            'offers'    => RequestService::offers((int)$request['id']),
            'pending'   => RequestService::pendingOffer((int)$request['id']),
            'accepted'  => RequestService::acceptedOffer((int)$request['id']),
            'details'   => RequestService::detailLines($request),
            'link'      => ($t = RequestService::tokenFor($request)) ? RequestService::trackingUrl($t) : null,
            'invoices'  => Db::all('SELECT id, number, status, total_cents FROM invoices WHERE request_id = ? ORDER BY id DESC', [$request['id']]),
            'mailReady' => Mailer::enabled() && $request['contact_email'] !== '',
        ], 'Anfrage ' . $request['public_id']);
    }

    public function reply(array $p): void
    {
        $staff = Auth::require();
        $request = $this->find($p);
        $back = $this->url($request);

        $v = new Validator($_POST);
        $body = $v->text('body', 2, 5000, 'Nachricht', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        $internal = ($_POST['internal'] ?? '') === '1';
        $mailed = RequestService::staffMessage($request, (int)$staff['id'], $body, $internal);

        flash('success', $internal ? 'Interne Notiz gespeichert.' : ($mailed ? 'Antwort gesendet und per E-Mail benachrichtigt.' : 'Antwort gespeichert. Keine E-Mail möglich – der Kunde sieht sie über Link/Konto.'));
        redirect($back);
    }

    public function offer(array $p): void
    {
        $staff = Auth::require();
        $request = $this->find($p);
        $back = $this->url($request);

        $v = new Validator($_POST);
        $free = ($_POST['free'] ?? '') === '1';
        $amount = $free ? 0 : parse_money((string)($_POST['amount'] ?? ''));
        if ($amount === null || (!$free && $amount === 0)) {
            $v->addError('amount', 'Bitte einen Preis angeben (z. B. 49,90) oder „Kostenlos“ wählen.');
        }
        $delivery = $v->text('delivery', 0, 80, 'Lieferzeit');
        $description = $v->text('description', 10, 3000, 'Leistungsbeschreibung', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        try {
            $mailed = RequestService::sendOffer($request, (int)$staff['id'], (int)$amount, $delivery, $description);
        } catch (RuntimeException) {
            fail_back([], $back, 'Für abgeschlossene Anfragen kann kein Angebot mehr gesendet werden.');
        }
        audit('staff:' . $staff['id'], 'offer_sent', $request['public_id'] . ' ' . $amount);
        flash('success', 'Angebot gesendet' . ($mailed ? ' und per E-Mail angekündigt.' : '. Der Kunde sieht es über Link/Konto.'));
        redirect($back);
    }

    public function updateStatus(array $p): void
    {
        $staff = Auth::require();
        $request = $this->find($p);
        $status = (string)($_POST['status'] ?? '');
        if (!isset(RequestService::STATUSES[$status])) {
            abort(404);
        }
        $progressRaw = (string)($_POST['progress'] ?? '');
        $progress = ctype_digit($progressRaw) ? (int)$progressRaw : null;

        $mailed = RequestService::setStatus($request, $status, $progress);
        audit('staff:' . $staff['id'], 'request_status', $request['public_id'] . ' ' . $status);
        flash('success', 'Status aktualisiert' . ($mailed ? ', Kunde per E-Mail informiert.' : '.'));
        redirect($this->url($request));
    }

    public function rotateLink(array $p): void
    {
        $staff = Auth::requireAdmin();
        $request = $this->find($p);
        RequestService::rotateToken((int)$request['id']);
        audit('staff:' . $staff['id'], 'request_link_rotated', $request['public_id']);
        flash('info', 'Neuer privater Link erstellt. Der alte funktioniert nicht mehr.');
        redirect($this->url($request));
    }

    public function createInvoice(array $p): void
    {
        $staff = Auth::require();
        $request = $this->find($p);
        $offer = RequestService::acceptedOffer((int)$request['id']);
        if (!$offer) {
            fail_back([], $this->url($request), 'Es gibt kein angenommenes Angebot.');
        }
        if (!$request['customer_id']) {
            fail_back([], $this->url($request), 'Der Kunde hat noch kein Konto. Bitte ihn, sich mit derselben E-Mail zu registrieren – dann wird die Anfrage automatisch verknüpft.');
        }
        if (Db::value("SELECT 1 FROM invoices WHERE request_id = ? AND status <> 'cancelled'", [$request['id']])) {
            fail_back([], $this->url($request), 'Für diese Anfrage gibt es schon einen Beleg.');
        }
        if ((int)$offer['amount_cents'] === 0) {
            fail_back([], $this->url($request), 'Das Angebot ist kostenlos – dafür ist kein Beleg nötig.');
        }
        $id = InvoiceService::draftFromOffer($request, $offer, (int)$staff['id']);
        audit('staff:' . $staff['id'], 'invoice_from_offer', $request['public_id'] . ' #' . $id);
        flash('success', InvoiceService::docLabel() . '-Entwurf aus dem Angebot erstellt.');
        redirect('/rechnungen/' . $id);
    }

    private function find(array $p): array
    {
        return RequestService::find(id_param($p)) ?? abort(404);
    }

    private function url(array $request): string
    {
        return '/anfragen/' . $request['id'];
    }
}
