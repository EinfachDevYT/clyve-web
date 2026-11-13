<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * Closing customer accounts. Accounts without receipts or services are deleted completely.
 * Otherwise the account is anonymised: issued receipts keep the recipient data printed on them
 * (retention duty), everything else about the person is removed.
 */
final class CustomerDeletion
{
    /** Reasons the customer can't close the account themselves. */
    public static function blockers(array $customer): array
    {
        $id = (int)$customer['id'];
        $out = [];
        $unpaid = (int)Db::value("SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND status = 'issued'", [$id]);
        if ($unpaid) {
            $out[] = $unpaid === 1 ? 'Es ist noch eine Rechnung offen.' : "Es sind noch {$unpaid} Rechnungen offen.";
        }
        $services = (int)Db::value("SELECT COUNT(*) FROM services WHERE customer_id = ? AND status <> 'cancelled'", [$id]);
        if ($services) {
            $out[] = $services === 1 ? 'Ein Hosting-Dienst läuft noch.' : "{$services} Hosting-Dienste laufen noch.";
        }
        return $out;
    }

    /** Only running services stop an admin – unpaid receipts stay with the anonymised account. */
    public static function adminBlockers(array $customer): array
    {
        $n = (int)Db::value("SELECT COUNT(*) FROM services WHERE customer_id = ? AND status <> 'cancelled'", [$customer['id']]);
        return $n ? ['Erst die laufenden Hosting-Dienste kündigen (' . $n . ').'] : [];
    }

    public static function mustKeep(array $customer): bool
    {
        return (bool)Db::value(
            "SELECT 1 WHERE EXISTS (SELECT 1 FROM invoices WHERE customer_id = ? AND status <> 'draft')
                         OR EXISTS (SELECT 1 FROM services WHERE customer_id = ?)",
            [$customer['id'], $customer['id']]
        );
    }

    /** @return 'deleted'|'anonymised' */
    public static function close(array $customer): string
    {
        $id = (int)$customer['id'];
        $keep = self::mustKeep($customer);

        Db::transaction(static function () use ($id, $keep): void {
            // requests behind a receipt or service are business records; the rest goes
            $linked = 'SELECT request_id FROM invoices WHERE request_id IS NOT NULL UNION SELECT request_id FROM services WHERE request_id IS NOT NULL';
            Db::exec("DELETE FROM requests WHERE customer_id = ? AND id NOT IN ({$linked})", [$id]);
            Db::exec(
                "UPDATE requests SET name = 'Gelöschter Kunde', email = '', discord = '', details_json = '{}' WHERE customer_id = ?",
                [$id]
            );
            Db::exec("DELETE FROM invoices WHERE customer_id = ? AND status = 'draft'", [$id]);
            Db::exec('DELETE FROM tickets WHERE customer_id = ?', [$id]);
            foreach (['auth_tokens', 'recovery_codes', 'account_events'] as $table) {
                Db::exec("DELETE FROM {$table} WHERE account_type = 'customer' AND account_id = ?", [$id]);
            }

            if (!$keep) {
                Db::exec('UPDATE requests SET customer_id = NULL WHERE customer_id = ?', [$id]);
                Db::exec('DELETE FROM customers WHERE id = ?', [$id]);
                return;
            }
            Db::exec(
                "UPDATE customers SET
                   email = ?, name = ?, password_hash = ?, discord = '', company = '', vat_id = '', phone = '',
                   salutation = '', first_name = '', last_name = '', street = '', house_number = '', address_extra = '',
                   zip = '', city = '', country = '', totp_secret = NULL, totp_enabled_at = NULL, totp_last_step = 0,
                   email_verified_at = NULL, devlog_seen_at = NULL, password_changed_at = NULL,
                   session_version = session_version + 1, deleted_at = datetime('now')
                 WHERE id = ?",
                ['geloescht-' . $id . '-' . bin2hex(random_bytes(4)) . '@invalid', 'Gelöschter Kunde #' . $id, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $id]
            );
        });

        return $keep ? 'anonymised' : 'deleted';
    }

    /** Goodbye mail, sent before the address is gone. */
    public static function confirm(array $customer, string $result): void
    {
        $lines = ['dein Kundenkonto bei ' . config('business.name', 'Clyve.dev') . ' wurde geschlossen. Du kannst dich nicht mehr anmelden.'];
        $lines[] = $result === 'anonymised'
            ? 'Deine persönlichen Daten haben wir gelöscht. Ausgestellte Rechnungen bzw. Quittungen müssen wir gesetzlich aufbewahren – sie enthalten weiterhin die Angaben, die darauf stehen, und werden nach Ablauf der Frist gelöscht.'
            : 'Deine Daten haben wir vollständig gelöscht.';
        $contact = (string)config('business.email', '');
        $lines[] = 'Wenn du das nicht veranlasst hast, melde dich bitte umgehend' . ($contact !== '' ? ' unter ' . $contact : '') . '.';
        Mailer::send((string)$customer['email'], 'Dein Konto wurde geschlossen', Mailer::compose(explode(' ', (string)$customer['name'])[0], $lines));
    }
}
