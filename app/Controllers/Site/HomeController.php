<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\Notifier;

final class HomeController
{
    public const BUDGETS = ['unter 50 €', '50 – 150 €', '150 – 400 €', '400 € +'];

    public function index(): void
    {
        $_SESSION['_form_at'] = time();
        View::render('site/home', ['budgets' => self::BUDGETS], 'Auftrag anfragen');
    }

    public function imprint(): void
    {
        View::render('site/imprint', [], 'Impressum');
    }

    public function privacy(): void
    {
        View::render('site/privacy', [], 'Datenschutz');
    }

    public function submitRequest(): void
    {
        $startedAt = (int)($_SESSION['_form_at'] ?? 0);
        if (!empty($_POST['website']) || $startedAt === 0 || time() - $startedAt < (int)config('min_fill_seconds', 4)) {
            json_out(200, ['ok' => true, 'message' => 'Danke! Deine Anfrage ist angekommen.']);
        }

        if (!RateLimiter::ip('request')) {
            header('Retry-After: 600');
            json_out(429, ['ok' => false, 'message' => 'Zu viele Anfragen. Versuch es später erneut oder schreib mir auf Discord.']);
        }

        $customer = Auth::user();
        $v = new Validator($_POST);
        $type = $v->oneOf('type', array_keys(Notifier::TYPES), 'Bitte wähle eine Projektart.');
        $name = $v->text('name', 2, 80, 'Name');
        $title = $v->text('title', 0, 120, 'Titel');
        $discord = $v->discord('discord');
        $email = $v->email('email', false);
        $budgetRaw = $_POST['budget'] ?? '';
        $budget = $budgetRaw === '' ? '' : $v->oneOf('budget', self::BUDGETS, 'Ungültiges Budget.');
        $deadline = $v->date('deadline', true);
        $message = $v->text('message', 20, 4000, 'Beschreibung', true);
        $v->accepted('consent', 'Bitte stimme der Datenverarbeitung zu.');

        if ($customer && $email === '' && $discord === '') {
            $email = $customer['email'];
        }
        $errors = $v->errors();
        if (!isset($errors['discord']) && !isset($errors['email']) && $discord === '' && $email === '') {
            $v->addError('contact', 'Bitte gib Discord oder E-Mail an.');
        }
        if ($v->fails()) {
            json_out(422, ['ok' => false, 'message' => 'Bitte überprüfe deine Eingaben.', 'errors' => $v->errors()]);
        }

        $request = [
            'public_id' => strtoupper(bin2hex(random_bytes(4))),
            'type' => $type, 'name' => $name, 'title' => $title, 'discord' => $discord,
            'email' => $email, 'budget' => $budget, 'deadline' => $deadline, 'message' => $message,
        ];

        Db::insert(
            'INSERT INTO requests (public_id, customer_id, type, name, title, discord, email, budget, deadline, message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$request['public_id'], $customer['id'] ?? null, $type, $name, $title, $discord, $email, $budget, $deadline, $message]
        );
        Notifier::newRequest($request);

        $_SESSION['_form_at'] = time();
        json_out(200, ['ok' => true, 'message' => 'Danke! Deine Anfrage ist angekommen – ich melde mich bald.']);
    }
}
