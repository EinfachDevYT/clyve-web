<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use PDO;
use RuntimeException;

/** Daily, verified and compressed copies of the SQLite database. */
final class Backup
{
    private const PATTERN = '/^app-\d{4}-\d{2}-\d{2}-\d{6}\.sqlite\.gz$/';

    public static function dir(): string
    {
        return rtrim((string)config('backup.dir', STORAGE_PATH . '/backups'), '/');
    }

    public static function keepDays(): int
    {
        return max(1, (int)config('backup.keep_days', 14));
    }

    /** @return list<array{name: string, size: int, time: int}> newest first */
    public static function all(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/app-*.sqlite.gz') ?: [] as $file) {
            if (preg_match(self::PATTERN, basename($file))) {
                $out[] = ['name' => basename($file), 'size' => (int)filesize($file), 'time' => (int)filemtime($file)];
            }
        }
        usort($out, static fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    public static function latest(): ?array
    {
        return self::all()[0] ?? null;
    }

    /** Hours since the last backup, null if there is none. */
    public static function age(): ?float
    {
        $last = self::latest();
        return $last ? (time() - $last['time']) / 3600 : null;
    }

    /** True once per day after the configured hour (default 3 am). */
    public static function due(): bool
    {
        $last = self::latest();
        $hour = (int)config('backup.hour', 3);
        return (int)date('G') >= $hour && (!$last || date('Y-m-d', $last['time']) !== date('Y-m-d'));
    }

    public static function file(string $name): ?string
    {
        if (!preg_match(self::PATTERN, $name)) {
            return null;
        }
        $path = self::dir() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    /** Creates a backup and removes old ones. Returns the file name. */
    public static function create(): string
    {
        $dir = self::dir();
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('backup dir not writable: ' . $dir);
        }
        $name = 'app-' . date('Y-m-d-His') . '.sqlite';
        $tmp = $dir . '/.' . $name . '.tmp';
        @unlink($tmp);
        try {
            Db::pdo()->prepare('VACUUM INTO ?')->execute([$tmp]);
            $check = (new PDO('sqlite:' . $tmp))->query('PRAGMA integrity_check')->fetchColumn();
            if ($check !== 'ok') {
                throw new RuntimeException('integrity check failed: ' . $check);
            }
            $gz = gzencode((string)file_get_contents($tmp), 6);
            if ($gz === false || file_put_contents($dir . '/' . $name . '.gz', $gz, LOCK_EX) === false) {
                throw new RuntimeException('could not write backup');
            }
            @chmod($dir . '/' . $name . '.gz', 0600);
        } finally {
            @unlink($tmp);
        }
        self::prune();
        return $name . '.gz';
    }

    /** Keeps everything younger than keep_days, but never fewer than the last three. */
    private static function prune(): void
    {
        $limit = time() - self::keepDays() * 86400;
        foreach (array_slice(self::all(), 3) as $b) {
            if ($b['time'] < $limit) {
                @unlink(self::dir() . '/' . $b['name']);
            }
        }
    }
}
