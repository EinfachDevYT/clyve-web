<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Db;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

define('APP_CONTEXT', 'staff');
require dirname(__DIR__) . '/app/bootstrap.php';

function ask(string $label, bool $hidden = false): string
{
    fwrite(STDOUT, $label . ': ');
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty -echo');
        $value = trim((string)fgets(STDIN));
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
        return $value;
    }
    return trim((string)fgets(STDIN));
}

$email = strtolower($argv[1] ?? ask('E-Mail'));
$name = $argv[2] ?? ask('Name');
$role = in_array($argv[3] ?? '', ['admin', 'staff'], true) ? $argv[3] : (ask('Rolle [admin/staff]') === 'admin' ? 'admin' : 'staff');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) < 2) {
    fwrite(STDERR, "Ungültige E-Mail oder Name.\n");
    exit(1);
}

$password = getenv('STAFF_PASSWORD') ?: ask('Passwort (min. 12 Zeichen)', true);
if (mb_strlen($password) < 12 || ($problem = Auth::passwordProblem($password, $email))) {
    fwrite(STDERR, ($problem ?? 'Mindestens 12 Zeichen.') . "\n");
    exit(1);
}

$existing = Db::value('SELECT id FROM staff WHERE email = ?', [$email]);
if ($existing) {
    Db::exec('UPDATE staff SET name = ?, role = ?, password_hash = ?, active = 1 WHERE id = ?', [$name, $role, Auth::hash($password), $existing]);
    echo "Aktualisiert: {$email} ({$role})\n";
} else {
    Db::insert('INSERT INTO staff (email, name, password_hash, role) VALUES (?, ?, ?, ?)', [$email, $name, Auth::hash($password), $role]);
    echo "Angelegt: {$email} ({$role})\n";
}
