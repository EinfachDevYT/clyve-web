<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\InvoiceService;
use RuntimeException;

final class InvoiceController
{
    private const STATUSES = ['draft', 'issued', 'paid', 'cancelled'];

    public function index(): void
    {
        Auth::require();
        $status = in_array($_GET['status'] ?? '', self::STATUSES, true) ? $_GET['status'] : '';
        $sql = 'SELECT i.*, c.name AS customer_name FROM invoices i JOIN customers c ON c.id = i.customer_id';
        $invoices = $status === ''
            ? Db::all($sql . ' ORDER BY i.id DESC LIMIT 200')
            : Db::all($sql . ' WHERE i.status = ? ORDER BY i.id DESC LIMIT 200', [$status]);

        View::render('staff/invoices/index', [
            'invoices' => $invoices,
            'status'   => $status,
            'canIssue' => InvoiceService::canIssue(),
        ], 'Rechnungen');
    }

    public function create(): void
    {
        Auth::require();
        View::render('staff/invoices/create', [
            'customers' => Db::all('SELECT id, name, email FROM customers ORDER BY name'),
            'selected'  => (int)($_GET['customer'] ?? 0),
        ], 'Neue Rechnung');
    }

    public function store(): void
    {
        $staff = Auth::require();
        $v = new Validator($_POST);
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $title = $v->text('title', 0, 120, 'Titel');
        if (!Db::value('SELECT 1 FROM customers WHERE id = ?', [$customerId])) {
            $v->addError('customer_id', 'Bitte wähle einen Kunden.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), '/rechnungen/neu');
        }

        $id = InvoiceService::createDraft($customerId, $title, (int)$staff['id']);
        audit('staff:' . $staff['id'], 'invoice_draft', "#{$id}");
        redirect('/rechnungen/' . $id);
    }

    public function show(array $p): void
    {
        Auth::require();
        $invoice = $this->find($p);
        View::render('staff/invoices/show', [
            'invoice'  => $invoice,
            'canIssue' => InvoiceService::canIssue(),
            'customer' => Db::one('SELECT * FROM customers WHERE id = ?', [$invoice['customer_id']]),
        ], $invoice['number'] ?? 'Entwurf #' . $invoice['id']);
    }

    public function update(array $p): void
    {
        $invoice = $this->draft($p);
        $v = new Validator($_POST);
        $title = $v->text('title', 0, 120, 'Titel');
        $serviceDate = $v->text('service_date', 0, 60, 'Leistungszeitraum');
        $notes = $v->text('notes', 0, 1000, 'Hinweis', true);
        if ($v->fails()) {
            fail_back($v->errors(), $this->url($invoice));
        }
        InvoiceService::updateDraft((int)$invoice['id'], $title, $serviceDate, $notes);
        flash('success', 'Gespeichert.');
        redirect($this->url($invoice));
    }

    public function addItem(array $p): void
    {
        $invoice = $this->draft($p);
        $v = new Validator($_POST);
        $description = $v->text('description', 2, 300, 'Beschreibung');
        $unit = $v->text('unit', 1, 20, 'Einheit');
        $qtyRaw = str_replace(',', '.', (string)($_POST['quantity'] ?? ''));
        $qty = is_numeric($qtyRaw) ? round((float)$qtyRaw, 2) : 0.0;
        if ($qty <= 0 || $qty > 10000) {
            $v->addError('quantity', 'Ungültige Menge.');
        }
        $price = parse_money((string)($_POST['price'] ?? ''));
        if ($price === null) {
            $v->addError('price', 'Ungültiger Preis (z. B. 49,90).');
        }
        if ($v->fails()) {
            fail_back($v->errors(), $this->url($invoice));
        }
        InvoiceService::addItem((int)$invoice['id'], $description, $qty, $unit, (int)$price);
        redirect($this->url($invoice));
    }

    public function removeItem(array $p): void
    {
        $invoice = $this->draft($p);
        InvoiceService::removeItem((int)$invoice['id'], id_param($p, 'item'));
        redirect($this->url($invoice));
    }

    public function issue(array $p): void
    {
        $staff = Auth::requireAdmin();
        $invoice = $this->find($p);
        if (!InvoiceService::canIssue()) {
            flash('error', 'Ausstellen ist gesperrt, solange kein Gewerbe angemeldet ist (business.registered).');
            redirect($this->url($invoice));
        }
        try {
            $number = InvoiceService::issue((int)$invoice['id']);
        } catch (RuntimeException $e) {
            $messages = [
                'No items' => 'Der Beleg hat keine Positionen.',
                'Customer address incomplete' => 'Die Anschrift des Kunden ist unvollständig.',
            ];
            flash('error', $messages[$e->getMessage()] ?? InvoiceService::docLabel() . ' konnte nicht ausgestellt werden.');
            redirect($this->url($invoice));
        }
        audit('staff:' . $staff['id'], 'invoice_issued', $number);
        flash('success', InvoiceService::docLabel() . " {$number} ausgestellt.");
        redirect($this->url($invoice));
    }

    public function markPaid(array $p): void
    {
        $staff = Auth::requireAdmin();
        $invoice = $this->find($p);
        InvoiceService::markPaid((int)$invoice['id']);
        audit('staff:' . $staff['id'], 'invoice_paid', (string)$invoice['number']);
        flash('success', 'Als bezahlt markiert.');
        redirect($this->url($invoice));
    }

    public function cancel(array $p): void
    {
        $staff = Auth::requireAdmin();
        $invoice = $this->find($p);
        InvoiceService::cancel((int)$invoice['id']);
        audit('staff:' . $staff['id'], 'invoice_cancelled', (string)$invoice['number']);
        flash('info', InvoiceService::docLabel($invoice) . ' storniert.');
        redirect($this->url($invoice));
    }

    public function destroy(array $p): void
    {
        $staff = Auth::require();
        $invoice = $this->draft($p);
        InvoiceService::deleteDraft((int)$invoice['id']);
        audit('staff:' . $staff['id'], 'invoice_draft_deleted', '#' . $invoice['id']);
        flash('info', 'Entwurf gelöscht.');
        redirect('/rechnungen');
    }

    public function print(array $p): void
    {
        Auth::require();
        View::render('shared/invoice-print', ['invoice' => $this->find($p)], '', null);
    }

    private function find(array $p): array
    {
        return InvoiceService::find(id_param($p)) ?? abort(404);
    }

    private function draft(array $p): array
    {
        Auth::require();
        $invoice = $this->find($p);
        if ($invoice['status'] !== 'draft') {
            flash('error', 'Ausgestellte Belege können nicht mehr geändert werden.');
            redirect($this->url($invoice));
        }
        return $invoice;
    }

    private function url(array $invoice): string
    {
        return '/rechnungen/' . $invoice['id'];
    }
}
