<?php
declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    public static function hit(string $bucket, string $identity): bool
    {
        $limits = (array)config('rate_limits.' . $bucket, [['window' => 600, 'max' => 5]]);
        $dir = STORAGE_PATH . '/ratelimit';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        self::gc($dir);

        $file = $dir . '/' . Security::hmac(APP_CONTEXT . '|' . $bucket . '|' . $identity) . '.json';
        $fh = fopen($file, 'c+');
        if ($fh === false) {
            return false;
        }
        try {
            flock($fh, LOCK_EX);
            $stamps = json_decode((string)stream_get_contents($fh), true);
            $stamps = is_array($stamps) ? array_filter($stamps, 'is_int') : [];

            $now = time();
            $maxWindow = max(array_map(static fn($l) => (int)$l['window'], $limits));
            $stamps = array_values(array_filter($stamps, static fn(int $t) => $t > $now - $maxWindow));

            foreach ($limits as $l) {
                $recent = array_filter($stamps, static fn(int $t) => $t > $now - (int)$l['window']);
                if (count($recent) >= (int)$l['max']) {
                    return false;
                }
            }

            $stamps[] = $now;
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($stamps));
            return true;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public static function ip(string $bucket): bool
    {
        return self::hit($bucket, Security::clientIp());
    }

    private static function gc(string $dir): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }
        foreach (glob($dir . '/*.json') ?: [] as $f) {
            if (filemtime($f) < time() - 172800) {
                @unlink($f);
            }
        }
    }
}
