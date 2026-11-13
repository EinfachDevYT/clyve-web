<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\Account;
use App\Services\Mailer;

/** Admin: team members, invitations, roles, access. */
final class StaffController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $members = Db::all('SELECT id, name, email, role, active, totp_enabled_at, created_at FROM staff ORDER BY active DESC, role, name');
        foreach ($members as &$m) {
            $m['last_login'] = Account::lastLogin('staff', (int)$m['id']);
        }
        View::render('staff/team/index', ['members' => $members], 'Mitarbeiter');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('staff/team/create', ['mail' => Mailer::enabled()], 'Mitarbeiter einladen');
    }

    public function store(): void
    {
        $admin = Auth::requireAdmin();
        $v = new Validator($_POST);
        $name = $v->text('name', 2, 80, 'Name');
        $email = $v->email('email');
        $role = $v->oneOf('role', ['staff', 'admin'], 'Bitte eine Rolle wählen.');
        if (!$v->fails() && Account::emailTaken('staff', $email)) {
            $v->addError('email', 'Zu dieser E-Mail gibt es bereits ein Staff-Konto.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), '/mitarbeiter/neu');
        }
        $id = Db::insert('INSERT INTO staff (email, name, password_hash, role) VALUES (?, ?, ?, ?)', [$email, $name, Auth::hash(bin2hex(random_bytes(32))), $role]);
        audit('staff:' . $admin['id'], 'staff_invited', '#' . $id . ' ' . $email);
        $this->issueLink($id, 'invite');
        redirect('/mitarbeiter/' . $id);
    }

    public function show(array $p): void
    {
        $admin = Auth::requireAdmin();
        $m = $this->find($p);
        $link = $_SESSION['_staff_link'] ?? null;
        unset($_SESSION['_staff_link']);
        View::render('staff/team/show', [
            'm'      => $m,
            'self'   => (int)$m['id'] === (int)$admin['id'],
            'events' => Account::events('staff', (int)$m['id'], 15),
            'link'   => is_array($link) && (int)$link['id'] === (int)$m['id'] ? $link : null,
            'mail'   => Mailer::enabled(),
        ], $m['name']);
    }

    public function update(array $p): void
    {
        $admin = Auth::requireAdmin();
        $m = $this->find($p);
        $v = new Validator($_POST);
        $name = $v->text('name', 2, 80, 'Name');
        $role = $v->oneOf('role', ['staff', 'admin'], 'Bitte eine Rolle wählen.');
        if (!$v->fails() && $m['role'] === 'admin' && $role !== 'admin' && $this->lastAdmin((int)$m['id'])) {
            $v->addError('role', 'Es muss mindestens ein aktiver Admin bleiben.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), '/mitarbeiter/' . $m['id']);
        }
        Db::exec('UPDATE staff SET name = ?, role = ? WHERE id = ?', [$name, $role, $m['id']]);
        if ($m['role'] === 'admin' && $role !== 'admin') {
            Account::dropStoredRecoveryCodes('staff', (int)$m['id']);
        }
        audit('staff:' . $admin['id'], 'staff_updated', '#' . $m['id'] . ' role=' . $role);
        flash('success', 'Gespeichert.');
        redirect('/mitarbeiter/' . $m['id']);
    }

    public function toggleActive(array $p): void
    {
        $admin = Auth::requireAdmin();
        $m = $this->find($p);
        if ((int)$m['id'] === (int)$admin['id']) {
            fail_back([], '/mitarbeiter/' . $m['id'], 'Du kannst dein eigenes Konto nicht deaktivieren.');
        }
        if ($m['active'] && $m['role'] === 'admin' && $this->lastAdmin((int)$m['id'])) {
            fail_back([], '/mitarbeiter/' . $m['id'], 'Es muss mindestens ein aktiver Admin bleiben.');
        }
        Db::exec('UPDATE staff SET active = 1 - active, session_version = session_version + 1 WHERE id = ?', [$m['id']]);
        audit('staff:' . $admin['id'], $m['active'] ? 'staff_deactivated' : 'staff_activated', '#' . $m['id']);
        flash('success', $m['active'] ? 'Zugang deaktiviert und alle Sitzungen beendet.' : 'Zugang wieder aktiviert.');
        redirect('/mitarbeiter/' . $m['id']);
    }

    public function resetTwoFactor(array $p): void
    {
        $admin = Auth::requireAdmin();
        $m = $this->find($p);
        if ((int)$m['id'] === (int)$admin['id']) {
            fail_back([], '/mitarbeiter/' . $m['id'], 'Deine eigene Zwei-Faktor-Anmeldung verwaltest du unter Einstellungen → Sicherheit.');
        }
        Account::disableTwoFactor('staff', (int)$m['id'], 'twofa_reset');
        Account::revokeSessions('staff', (int)$m['id']);
        Account::notify($m, 'Zwei-Faktor-Anmeldung zurückgesetzt', ['ein Admin hat die Zwei-Faktor-Anmeldung deines Staff-Kontos zurückgesetzt. Richte sie nach der nächsten Anmeldung bitte neu ein.']);
        audit('staff:' . $admin['id'], 'staff_2fa_reset', '#' . $m['id']);
        flash('success', 'Zwei-Faktor-Anmeldung zurückgesetzt, Sitzungen beendet.');
        redirect('/mitarbeiter/' . $m['id']);
    }

    public function sendLink(array $p): void
    {
        $admin = Auth::requireAdmin();
        $m = $this->find($p);
        if (!$m['active']) {
            fail_back([], '/mitarbeiter/' . $m['id'], 'Das Konto ist deaktiviert.');
        }
        audit('staff:' . $admin['id'], 'staff_link', '#' . $m['id']);
        $this->issueLink((int)$m['id'], Account::lastLogin('staff', (int)$m['id']) ? 'reset' : 'invite');
        redirect('/mitarbeiter/' . $m['id']);
    }

    /** Creates an invite/reset link, mails it if possible and shows it once to the admin. */
    private function issueLink(int $id, string $purpose): void
    {
        $m = Account::find('staff', $id);
        $ttl = $purpose === 'invite' ? 604800 : 86400;
        $url = Account::baseUrl('staff') . '/passwort-zuruecksetzen/' . Account::createToken($purpose, 'staff', $id, '', $ttl);
        $sent = Mailer::send($m['email'], $purpose === 'invite' ? 'Dein Zugang zum Staff-Bereich' : 'Neues Passwort für den Staff-Bereich', Mailer::compose(explode(' ', (string)$m['name'])[0], [
            $purpose === 'invite'
                ? 'du wurdest zum Staff-Bereich von ' . config('business.name', 'Clyve.dev') . ' eingeladen. Leg über den Link dein Passwort fest (7 Tage gültig).'
                : 'über den Link kannst du ein neues Passwort für den Staff-Bereich festlegen (24 Stunden gültig).',
        ], 'Passwort festlegen', $url));
        $_SESSION['_staff_link'] = ['id' => $id, 'url' => $url, 'sent' => $sent, 'purpose' => $purpose];
        flash('success', $sent ? 'Link per E-Mail an ' . $m['email'] . ' gesendet.' : 'Link erstellt – gib ihn sicher weiter (z. B. per Discord-DM).');
    }

    private function lastAdmin(int $exceptId): bool
    {
        return !Db::value("SELECT 1 FROM staff WHERE role = 'admin' AND active = 1 AND id <> ?", [$exceptId]);
    }

    private function find(array $p): array
    {
        $m = Db::one('SELECT * FROM staff WHERE id = ?', [id_param($p)]);
        if (!$m) {
            abort(404);
        }
        unset($m['password_hash'], $m['totp_secret']);
        return $m;
    }
}
