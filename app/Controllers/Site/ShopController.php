<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Db;
use App\Core\View;
use App\Services\Settings;
use App\Services\Tebex;

final class ShopController
{
    public function index(): void
    {
        if (!Settings::on('shop_enabled')) {
            abort(404);
        }
        $framework = in_array($_GET['framework'] ?? '', Tebex::FRAMEWORKS, true) ? $_GET['framework'] : '';
        $products = Db::all('SELECT * FROM products WHERE active = 1 ORDER BY featured DESC, sort, title');
        if ($framework !== '') {
            $products = array_values(array_filter($products, static fn(array $p) => in_array($framework, explode(',', $p['frameworks']), true)));
        }
        View::render('site/shop', [
            'products'  => $products,
            'framework' => $framework,
            'fivemOpen' => in_array('fivem', Settings::openTypes(), true),
        ], 'Scripts');
    }
}
