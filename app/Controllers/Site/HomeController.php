<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\View;
use App\Services\RequestService;
use App\Services\Settings;

final class HomeController
{
    public function index(): void
    {
        $_SESSION['_form_at'] = time();
        View::render('site/home', [
            'budgets'   => RequestController::BUDGETS,
            'openTypes' => Settings::openTypes(),
            'details'   => RequestService::DETAILS,
            'settings'  => Settings::all(),
        ], '');
    }

    public function imprint(): void
    {
        View::render('site/imprint', [], 'Impressum');
    }

    public function privacy(): void
    {
        View::render('site/privacy', [], 'Datenschutz');
    }
}
