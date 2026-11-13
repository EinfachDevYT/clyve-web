<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\Hosting;
use App\Services\InvoiceService;
use App\Services\Settings;
use DateTimeImmutable;

final class HostingController
{
    public const FILTERS = [
        'aktiv'     => 'Aktiv',
        'bald'      => 'Läuft bald ab',
        'gesperrt'  => 'Gesperrt',
        'gekuendigt' => 'Gekündigt',
        'alle'      => 'Alle',
    ];

    public function index(): void
    {
        Auth::require();
        Hosting::tick();
        $filter = is_string($_GET['status'] ?? null) && array_key_exists($_GET['status'], self::FILTERS) ? $_GET['status'] : 'aktiv';
        $where = match ($filter) {
            'aktiv'      => "s.status = 'active'",
            'bald'       => "s.status = 'active' AND s.expires_on IS NOT NULL AND s.expires_on <= date('now', 'localtime', '+14 days')",
            'gesperrt'   => "s.status = 'suspended'",
            'gekuendigt' => "s.status = 'cancelled'",
            default      => '1 = 1',
        };
        View::render('staff/hosting/index', [
            'filter'   => $filter,
            'services' => Db::all(
                "SELECT s.*, c.name AS customer_name FROM services s JOIN customers c ON c.id = s.customer_id
                 WHERE {$where} ORDER BY s.expires_on IS NULL, s.expires_on, s.id DESC"
            ),
            'counts' => [
                'aktiv'    => (int)Db::value("SELECT COUNT(*) FROM services WHERE status = 'active'"),
                'bald'     => (int)Db::value("SELECT COUNT(*) FROM services WHERE status = 'active' AND expires_on IS NOT NULL AND expires_on <= date('now', 'localtime', '+14 days')"),
                'gesperrt' => (int)Db::value("SELECT COUNT(*) FROM services WHERE status = 'suspended'"),
            ],
        ], 'Hosting');
    }

    public function create(): void
    {
        Auth::require();
        $customerId = id_param($_GET, 'kunde');
        View::render('staff/hosting/create', [
            'customers' => Db::all('SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name'),
            'plans'     => Db::all('SELECT * FROM service_plans WHERE active = 1 ORDER BY price_cents, name'),
            'requests'  => Db::all("SELECT id, public_id, title, customer_id FROM requests WHERE customer_id IS NOT NULL AND type = 'bot' ORDER BY id DESC LIMIT 50"),
            'preselect' => $customerId,
        ], 'Neuer Dienst');
    }

    public function store(): void
    {
        $staff = Auth::require();
        $back = '/hosting/neu';
        $v = new Validator($_POST);
        $customerId = id_param($_POST, 'customer_id');
        if (!$customerId || !Db::value('SELECT 1 FROM customers WHERE id = ?', [$customerId])) {
            $v->addError('customer_id', 'Bitte einen Kunden wählen.');
        }
        $plan = null;
        if (($planId = id_param($_POST, 'plan_id')) > 0) {
            $plan = Db::one('SELECT * FROM service_plans WHERE id = ?', [$planId]);
            if (!$plan) {
                $v->addError('plan_id', 'Unbekannter Tarif.');
            }
        }
        $name = $v->text('name', 2, 120, 'Name');
        [$price, $term] = $this->priceAndTerm($v, $plan);
        $start = $v->date('starts_on') ?: Hosting::today()->format('Y-m-d');
        $requestId = id_param($_POST, 'request_id') ?: null;
        if ($requestId && !Db::value('SELECT 1 FROM requests WHERE id = ? AND customer_id = ?', [$requestId, $customerId])) {
            $v->addError('request_id', 'Die Anfrage gehört nicht zu diesem Kunden.');
        }
        $notes = $v->text('notes', 0, 2000, 'Notiz', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }

        $expires = $term > 0 ? Hosting::addMonths(new DateTimeImmutable($start), $term)->modify('-1 day')->format('Y-m-d') : null;
        $r = Hosting::create([
            'customer_id' => $customerId, 'plan_id' => $plan['id'] ?? null, 'request_id' => $requestId, 'name' => $name,
            'price_cents' => $price, 'term_months' => $term, 'starts_on' => $start, 'expires_on' => $expires, 'notes' => $notes,
        ], 'staff:' . $staff['name']);
        audit('staff:' . $staff['id'], 'service_created', '#' . $r['id']);
        $_SESSION['_license_once'] = ['id' => $r['id'], 'key' => $r['key']];
        flash('success', 'Dienst angelegt. Kopiere jetzt den Lizenzschlüssel – er wird nur einmal angezeigt.');
        redirect('/hosting/' . $r['id']);
    }

    public function show(array $p): void
    {
        Auth::require();
        $s = $this->find($p);
        $once = $_SESSION['_license_once'] ?? null;
        unset($_SESSION['_license_once']);
        View::render('staff/hosting/show', [
            's'       => $s,
            'key'     => is_array($once) && (int)$once['id'] === (int)$s['id'] ? (string)$once['key'] : null,
            'events'  => Hosting::events((int)$s['id'], true),
        ], $s['name']);
    }

    public function update(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        $back = '/hosting/' . $s['id'];
        $v = new Validator($_POST);
        $name = $v->text('name', 2, 120, 'Name');
        [$price, $term] = $this->priceAndTerm($v, null);
        $expires = ($_POST['unlimited'] ?? '') === '1' ? null : ($v->date('expires_on') ?: null);
        if (($_POST['unlimited'] ?? '') !== '1' && $expires === null) {
            $v->addError('expires_on', 'Ablaufdatum fehlt (oder „unbefristet“ wählen).');
        }
        $notes = $v->text('notes', 0, 2000, 'Notiz', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        Db::exec(
            "UPDATE services SET name = ?, price_cents = ?, term_months = ?, expires_on = ?, notes = ?, updated_at = datetime('now') WHERE id = ?",
            [$name, $price, $term, $expires, $notes, $s['id']]
        );
        if ($expires !== $s['expires_on']) {
            Hosting::log((int)$s['id'], 'edited', 'Ablaufdatum geändert: ' . ($expires ? date_de($expires) : 'unbefristet') . '.', 'staff:' . $staff['name']);
        }
        audit('staff:' . $staff['id'], 'service_updated', '#' . $s['id']);
        flash('success', 'Gespeichert.');
        redirect($back);
    }

    public function renew(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        $months = (int)($_POST['months'] ?? 0);
        if ($s['status'] === 'cancelled' || !in_array($months, [1, 3, 6, 12, 24], true)) {
            fail_back([], '/hosting/' . $s['id'], 'Verlängerung nicht möglich.');
        }
        $until = Hosting::renew($s, $months, 'staff:' . $staff['name']);
        $msg = 'Verlängert bis ' . date_de($until) . '.';
        if (($_POST['invoice'] ?? '') === '1' && (int)$s['price_cents'] > 0 && (int)$s['term_months'] > 0) {
            $periods = $months / (int)$s['term_months'];
            $id = InvoiceService::createDraft((int)$s['customer_id'], $s['name'] . ' – Verlängerung', (int)$staff['id'], $s['request_id'] ? (int)$s['request_id'] : null);
            InvoiceService::addItem($id, mb_substr($s['name'] . ' – Hosting bis ' . date_de($until), 0, 300), round($periods, 2), Hosting::termLabel((int)$s['term_months']), (int)$s['price_cents']);
            $msg .= ' ' . InvoiceService::docLabel() . '-Entwurf erstellt.';
            audit('staff:' . $staff['id'], 'service_renewed', '#' . $s['id'] . ' draft #' . $id);
            flash('success', $msg);
            redirect('/rechnungen/' . $id);
        }
        audit('staff:' . $staff['id'], 'service_renewed', '#' . $s['id']);
        flash('success', $msg);
        redirect('/hosting/' . $s['id']);
    }

    public function suspend(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        $reason = Validator::clean($_POST['reason'] ?? '', 200) ?? '';
        if ($s['status'] !== 'active') {
            fail_back([], '/hosting/' . $s['id'], 'Nur aktive Dienste können gesperrt werden.');
        }
        Hosting::suspend($s, $reason, 'staff', 'staff:' . $staff['name']);
        audit('staff:' . $staff['id'], 'service_suspended', '#' . $s['id']);
        flash('success', 'Gesperrt. Der Bot erfährt es bei seiner nächsten Prüfung (spätestens in ' . (int)ceil(Hosting::checkInterval() / 60) . ' Min.).');
        redirect('/hosting/' . $s['id']);
    }

    public function unsuspend(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        if ($s['status'] !== 'suspended') {
            redirect('/hosting/' . $s['id']);
        }
        Hosting::unsuspend($s, 'staff:' . $staff['name']);
        audit('staff:' . $staff['id'], 'service_unsuspended', '#' . $s['id']);
        $fresh = Hosting::find((int)$s['id']);
        flash($fresh && Hosting::state($fresh) === 'expired' ? 'info' : 'success', $fresh && Hosting::state($fresh) === 'expired'
            ? 'Sperre aufgehoben – die Laufzeit ist aber abgelaufen. Verlängere den Dienst, sonst wird er gleich wieder gesperrt.'
            : 'Sperre aufgehoben.');
        redirect('/hosting/' . $s['id']);
    }

    public function rotateKey(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        $_SESSION['_license_once'] = ['id' => $s['id'], 'key' => Hosting::rotateKey((int)$s['id'], 'staff:' . $staff['name'])];
        audit('staff:' . $staff['id'], 'service_key_rotated', '#' . $s['id']);
        flash('success', 'Neuer Lizenzschlüssel erzeugt. Der alte funktioniert ab sofort nicht mehr.');
        redirect('/hosting/' . $s['id']);
    }

    public function cancel(array $p): void
    {
        $staff = Auth::require();
        $s = $this->find($p);
        if ($s['status'] === 'cancelled') {
            Hosting::reactivate($s, 'staff:' . $staff['name']);
            audit('staff:' . $staff['id'], 'service_reactivated', '#' . $s['id']);
            flash('success', 'Dienst reaktiviert.');
        } else {
            Hosting::cancel($s, 'staff:' . $staff['name']);
            audit('staff:' . $staff['id'], 'service_cancelled', '#' . $s['id']);
            flash('success', 'Dienst gekündigt. Der Lizenzschlüssel ist ungültig.');
        }
        redirect('/hosting/' . $s['id']);
    }

    /* ---------- Tarife ---------- */

    public function plans(): void
    {
        Auth::requireAdmin();
        View::render('staff/hosting/plans', [
            'plans' => Db::all('SELECT p.*, (SELECT COUNT(*) FROM services s WHERE s.plan_id = p.id AND s.status <> \'cancelled\') AS in_use FROM service_plans p ORDER BY p.active DESC, p.price_cents, p.name'),
            'edit'  => ($id = id_param($_GET, 'bearbeiten')) ? Db::one('SELECT * FROM service_plans WHERE id = ?', [$id]) : null,
        ], 'Tarife');
    }

    public function storePlan(): void
    {
        $staff = Auth::requireAdmin();
        $id = id_param($_POST, 'id');
        $back = '/hosting/tarife' . ($id ? '?bearbeiten=' . $id : '');
        $v = new Validator($_POST);
        $name = $v->text('name', 2, 80, 'Name');
        $description = $v->text('description', 0, 300, 'Beschreibung');
        [$price, $term] = $this->priceAndTerm($v, null);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        if ($id) {
            Db::exec("UPDATE service_plans SET name = ?, description = ?, price_cents = ?, term_months = ?, updated_at = datetime('now') WHERE id = ?", [$name, $description, $price, $term, $id]);
        } else {
            $id = Db::insert('INSERT INTO service_plans (name, description, price_cents, term_months) VALUES (?, ?, ?, ?)', [$name, $description, $price, $term]);
        }
        audit('staff:' . $staff['id'], 'plan_saved', '#' . $id);
        flash('success', 'Tarif gespeichert. Bestehende Dienste behalten ihren Preis.');
        redirect('/hosting/tarife');
    }

    public function togglePlan(array $p): void
    {
        $staff = Auth::requireAdmin();
        Db::exec("UPDATE service_plans SET active = 1 - active, updated_at = datetime('now') WHERE id = ?", [id_param($p)]);
        audit('staff:' . $staff['id'], 'plan_toggled', '#' . id_param($p));
        redirect('/hosting/tarife');
    }

    /* ---------- helpers ---------- */

    private function priceAndTerm(Validator $v, ?array $plan): array
    {
        $raw = trim((string)($_POST['price'] ?? ''));
        $termRaw = (string)($_POST['term_months'] ?? '');
        if ($plan && $raw === '') {
            $price = (int)$plan['price_cents'];
        } else {
            $price = $raw === '' ? 0 : parse_money($raw);
            if ($price === null) {
                $v->addError('price', 'Ungültiger Preis.');
                $price = 0;
            }
        }
        if ($plan && $termRaw === '') {
            $term = (int)$plan['term_months'];
        } elseif (array_key_exists((int)$termRaw, Hosting::TERMS) && ctype_digit($termRaw)) {
            $term = (int)$termRaw;
        } else {
            $v->addError('term_months', 'Bitte eine Laufzeit wählen.');
            $term = 1;
        }
        return [$price, $term];
    }

    private function find(array $p): array
    {
        $s = Hosting::find(id_param($p));
        if (!$s) {
            abort(404);
        }
        return $s;
    }
}
