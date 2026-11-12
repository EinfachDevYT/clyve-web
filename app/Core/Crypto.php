<?php
declare(strict_types=1);

namespace App\Core;

/** Authenticated encryption for secrets stored in the database (key derived from app_secret). */
final class Crypto
{
    private static function key(string $purpose): string
    {
        return hash('sha256', 'clyve-' . $purpose . '|' . (string)config('app_secret'), true);
    }

    public static function encrypt(string $plain, string $purpose): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'enc:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key($purpose)));
    }

    public static function decrypt(string $stored, string $purpose): ?string
    {
        if (!str_starts_with($stored, 'enc:')) {
            return null;
        }
        $raw = base64_decode(substr($stored, 4), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::key($purpose));
        return $plain === false ? null : $plain;
    }
}
