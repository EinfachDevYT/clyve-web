<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use DateTimeImmutable;
use RuntimeException;

final class InvoiceService
{
    public static function canIssue(): bool
    {
        return (bool)config('business.registered', false);
    }

    public static function find(int $id, ?int $customerId = null): ?array
    {
        $sql = 'SELECT i.*, c.name AS customer_name, c.email AS customer_email
                FROM invoices i JOIN customers c ON c.id = i.customer_id WHERE i.id = ?';
        $params = [$id];
        if ($customerId !== null) {
            $sql .= " AND i.customer_id = ? AND i.status <> 'draft'";
            $params[] = $customerId;
        }
        $invoice = Db::one($sql, $params);
        if (!$invoice) {
            return null;
        }
        $invoice['items'] = Db::all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY position, id', [$id]);
        $invoice['recipient'] = $invoice['recipient_json'] ? json_decode($invoice['recipient_json'], true) : null;
        $invoice['sender'] = $invoice['sender_json'] ? json_decode($invoice['sender_json'], true) : null;
        return $invoice + self::totals($invoice);
    }

    public static function totals(array $invoice): array
    {
        $net = 0;
        foreach ($invoice['items'] as $item) {
            $net += self::lineTotal($item);
        }
        $small = (bool)($invoice['sender']['kleinunternehmer'] ?? config('business.kleinunternehmer', true));
        $rate = $small ? 0 : (int)($invoice['sender']['vat_rate'] ?? config('business.vat_rate', 19));
        $vat = (int)round($net * $rate / 100);
        return ['net_cents' => $net, 'vat_rate' => $rate, 'vat_cents' => $vat, 'gross_cents' => $net + $vat, 'small_business' => $small];
    }

    public static function lineTotal(array $item): int
    {
        return (int)round((float)$item['quantity'] * (int)$item['unit_price_cents']);
    }

    public static function createDraft(int $customerId, string $title, int $staffId): int
    {
        return Db::insert(
            'INSERT INTO invoices (customer_id, title, created_by) VALUES (?, ?, ?)',
            [$customerId, $title, $staffId]
        );
    }

    public static function addItem(int $invoiceId, string $description, float $qty, string $unit, int $priceCents): void
    {
        self::assertDraft($invoiceId);
        $pos = (int)Db::value('SELECT COALESCE(MAX(position), 0) + 1 FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
        Db::exec(
            'INSERT INTO invoice_items (invoice_id, position, description, quantity, unit, unit_price_cents) VALUES (?, ?, ?, ?, ?, ?)',
            [$invoiceId, $pos, $description, $qty, $unit, $priceCents]
        );
    }

    public static function removeItem(int $invoiceId, int $itemId): void
    {
        self::assertDraft($invoiceId);
        Db::exec('DELETE FROM invoice_items WHERE id = ? AND invoice_id = ?', [$itemId, $invoiceId]);
    }

    public static function updateDraft(int $invoiceId, string $title, string $serviceDate, string $notes): void
    {
        self::assertDraft($invoiceId);
        Db::exec('UPDATE invoices SET title = ?, service_date = ?, notes = ? WHERE id = ?', [$title, $serviceDate, $notes, $invoiceId]);
    }

    public static function deleteDraft(int $invoiceId): void
    {
        self::assertDraft($invoiceId);
        Db::exec("DELETE FROM invoices WHERE id = ? AND status = 'draft'", [$invoiceId]);
    }

    /**
     * Assigns a gapless number and freezes sender/recipient/total. After this the invoice is immutable;
     * corrections are only possible by cancelling it.
     */
    public static function issue(int $invoiceId): string
    {
        if (!self::canIssue()) {
            throw new RuntimeException('Business not registered');
        }

        return Db::transaction(static function () use ($invoiceId): string {
            $invoice = self::find($invoiceId);
            if (!$invoice || $invoice['status'] !== 'draft') {
                throw new RuntimeException('Not a draft');
            }
            if (!$invoice['items']) {
                throw new RuntimeException('No items');
            }
            $customer = Db::one('SELECT * FROM customers WHERE id = ?', [$invoice['customer_id']]);
            if (!$customer || $customer['street'] === '' || $customer['zip'] === '' || $customer['city'] === '') {
                throw new RuntimeException('Customer address incomplete');
            }

            $now = new DateTimeImmutable('today');
            $year = (int)$now->format('Y');
            $last = Db::value('SELECT last FROM invoice_counter WHERE year = ?', [$year]);
            if ($last === false) {
                Db::exec('INSERT INTO invoice_counter (year, last) VALUES (?, 1)', [$year]);
                $next = 1;
            } else {
                $next = (int)$last + 1;
                Db::exec('UPDATE invoice_counter SET last = ? WHERE year = ?', [$next, $year]);
            }
            $number = sprintf('%s-%d-%04d', config('business.invoice_prefix', 'RE'), $year, $next);

            $b = (array)config('business');
            $sender = [
                'name' => $b['name'] ?? '', 'owner' => $b['owner'] ?? '', 'street' => $b['street'] ?? '',
                'zip' => $b['zip'] ?? '', 'city' => $b['city'] ?? '', 'email' => $b['email'] ?? '',
                'tax_number' => $b['tax_number'] ?? '', 'vat_id' => $b['vat_id'] ?? '',
                'iban' => $b['iban'] ?? '', 'bic' => $b['bic'] ?? '', 'bank' => $b['bank'] ?? '',
                'kleinunternehmer' => (bool)($b['kleinunternehmer'] ?? true), 'vat_rate' => (int)($b['vat_rate'] ?? 19),
            ];
            $recipient = [
                'name' => $customer['name'], 'company' => $customer['company'], 'street' => $customer['street'],
                'zip' => $customer['zip'], 'city' => $customer['city'], 'country' => $customer['country'],
            ];
            $totals = self::totals(['items' => $invoice['items'], 'sender' => $sender]);
            $due = $now->modify('+' . (int)($b['payment_days'] ?? 14) . ' days');

            Db::exec(
                "UPDATE invoices SET number = ?, status = 'issued', issue_date = ?, due_date = ?,
                 service_date = CASE WHEN service_date = '' THEN ? ELSE service_date END,
                 sender_json = ?, recipient_json = ?, total_cents = ? WHERE id = ? AND status = 'draft'",
                [
                    $number, $now->format('Y-m-d'), $due->format('Y-m-d'), $now->format('d.m.Y'),
                    json_encode($sender, JSON_UNESCAPED_UNICODE), json_encode($recipient, JSON_UNESCAPED_UNICODE),
                    $totals['gross_cents'], $invoiceId,
                ]
            );
            return $number;
        });
    }

    public static function markPaid(int $invoiceId): void
    {
        Db::exec("UPDATE invoices SET status = 'paid', paid_at = datetime('now') WHERE id = ? AND status = 'issued'", [$invoiceId]);
    }

    public static function cancel(int $invoiceId): void
    {
        Db::exec("UPDATE invoices SET status = 'cancelled', cancelled_at = datetime('now') WHERE id = ? AND status IN ('issued','paid')", [$invoiceId]);
    }

    private static function assertDraft(int $invoiceId): void
    {
        if (Db::value('SELECT status FROM invoices WHERE id = ?', [$invoiceId]) !== 'draft') {
            throw new RuntimeException('Invoice is locked');
        }
    }
}
