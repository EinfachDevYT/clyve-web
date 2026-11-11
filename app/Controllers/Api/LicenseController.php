<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\RateLimiter;
use App\Services\Hosting;
use App\Services\Settings;

/** GET /api/v1/license, polled by hosted bots with "Authorization: Bearer CLY-…". */
final class LicenseController
{
    public function check(): void
    {
        header('X-Robots-Tag: noindex');
        if (!Settings::on('hosting_api_enabled')) {
            $this->respond(503, ['valid' => null, 'status' => 'unavailable', 'message' => 'Lizenzprüfung vorübergehend nicht verfügbar.', 'check_interval' => Hosting::checkInterval()]);
        }
        if (!RateLimiter::ip('license', [['window' => 60, 'max' => 240]])) {
            header('Retry-After: 60');
            $this->respond(429, ['valid' => null, 'status' => 'rate_limited', 'message' => 'Zu viele Anfragen.', 'check_interval' => Hosting::checkInterval()]);
        }

        $auth = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        $key = preg_match('/^Bearer\s+(\S+)$/i', $auth, $m) ? $m[1] : (string)($_SERVER['HTTP_X_LICENSE_KEY'] ?? '');
        $version = (string)($_SERVER['HTTP_X_BOT_VERSION'] ?? '');
        $version = preg_match('/^[\w.+-]{1,32}$/', $version) ? $version : '';

        $service = $key !== '' ? Hosting::findByKey($key) : null;
        if (!$service) {
            if (!RateLimiter::ip('license_fail', [['window' => 600, 'max' => 30]])) {
                header('Retry-After: 600');
                $this->respond(429, ['valid' => null, 'status' => 'rate_limited', 'message' => 'Zu viele ungültige Versuche.', 'check_interval' => 600]);
            }
            $this->respond(401, ['valid' => false, 'status' => 'invalid', 'message' => 'Unbekannter Lizenzschlüssel.', 'check_interval' => Hosting::checkInterval()]);
        }
        $this->respond(200, Hosting::check($service, $version), true);
    }

    private function respond(int $status, array $data, bool $tick = false): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($tick && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            Hosting::tick();
        }
        exit;
    }
}
