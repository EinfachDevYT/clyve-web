<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;
use App\Services\InvoiceService;

final class InvoiceController
{
    public function index(): void
    {
        $user = Auth::require();
        View::render('site/invoices/index', [
            'invoices' => Db::all(
                "SELECT * FROM invoices WHERE customer_id = ? AND status <> 'draft' ORDER BY issue_date DESC, id DESC",
                [$user['id']]
            ),
        ], 'Rechnungen', 'portal');
    }

    public function show(array $p): void
    {
        $user = Auth::require();
        $invoice = InvoiceService::find(id_param($p), (int)$user['id']);
        if (!$invoice) {
            abort(404);
        }
        View::render('site/invoices/show', ['invoice' => $invoice], 'Rechnung ' . $invoice['number'], 'portal');
    }

    public function print(array $p): void
    {
        $user = Auth::require();
        $invoice = InvoiceService::find(id_param($p), (int)$user['id']);
        if (!$invoice) {
            abort(404);
        }
        View::render('shared/invoice-print', ['invoice' => $invoice], '', null);
    }
}
