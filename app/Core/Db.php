<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }
        $dsn = (string)config('db.dsn');
        $fresh = !is_file(substr($dsn, 7));

        self::$pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
        if ($fresh) {
            @chmod(substr($dsn, 7), 0600);
        }
        self::migrate();
        return self::$pdo;
    }

    /** Applies pending files from database/migrations in order, each exactly once. */
    public static function migrate(): array
    {
        $pdo = self::$pdo;
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
        sort($files, SORT_STRING);

        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT (datetime(\'now\')))');
        $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $pending = array_filter($files, static fn(string $f) => !in_array(basename($f, '.sql'), $applied, true));
        if (!$pending) {
            return [];
        }

        $ran = [];
        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
            // Databases created before migrations existed already contain the initial schema.
            if (!$applied && $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'customers'")->fetchColumn()) {
                $pdo->exec("INSERT INTO schema_migrations (version) VALUES ('001_initial')");
                $applied[] = '001_initial';
            }
            foreach ($pending as $file) {
                $version = basename($file, '.sql');
                if (in_array($version, $applied, true)) {
                    continue;
                }
                $pdo->exec((string)file_get_contents($file));
                $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
                $ran[] = $version;
            }
            $pdo->exec('COMMIT');
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        }
        return $ran;
    }

    public static function exec(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::exec($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::exec($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        return self::exec($sql, $params)->fetchColumn();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::exec($sql, $params);
        return (int)self::pdo()->lastInsertId();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
