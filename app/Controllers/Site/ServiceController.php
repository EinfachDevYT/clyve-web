<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;
use App\Services\Hosting;
use App\Services\Settings;

final class ServiceController
{
    public function index(): void
    {
        $user = $this->guard();
        View::render('site/account/services', [
            'services' => Db::all("SELECT * FROM services WHERE customer_id = ? ORDER BY status = 'cancelled', expires_on IS NULL, expires_on, id DESC", [$user['id']]),
        ], 'Meine Dienste', 'portal');
    }

    public function show(array $p): void
    {
        $user = $this->guard();
        $s = Hosting::find(id_param($p), (int)$user['id']);
        if (!$s) {
            abort(404);
        }
        View::render('site/account/service', ['s' => $s, 'events' => Hosting::events((int)$s['id'], false)], $s['name'], 'portal');
    }

    private function guard(): array
    {
        $user = Auth::require();
        if (!Settings::on('hosting_customer_view')) {
            abort(404);
        }
        return $user;
    }
}
