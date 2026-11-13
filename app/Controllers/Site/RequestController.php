<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\Notifier;
use App\Services\RequestService;
use App\Services\Settings;
use RuntimeException;

final class RequestController
{
    public const BUDGETS = ['Kostenlos / Hobby', 'unter 50 €', '50 – 150 €', '150 – 400 €', '400 € +'];

    public function submit(): void
    {
        $startedAt = (int)($_SESSION['_form_at'] ?? 0);
        if (!empty($_POST['website'])) {
            $this->answer(200, ['ok' => true, 'message' => 'Danke! Deine Anfrage ist angekommen.']);
        }
        if ($startedAt === 0 || time() - $startedAt < (int)config('min_fill_seconds', 4)) {
            $_SESSION['_form_at'] = $startedAt ?: time();
            $this->answer(429, ['ok' => false, 'message' => 'Das ging sehr schnell – bitte prüfe deine Angaben kurz und sende dann erneut.']);
        }
        if (!Settings::openTypes()) {
            $this->answer(403, ['ok' => false, 'message' => Settings::get('requests_closed_message')]);
        }
        if (!RateLimiter::ip('request')) {
            header('Retry-After: 600');
            $this->answer(429, ['ok' => false, 'message' => 'Zu viele Anfragen. Versuch es später erneut oder schreib mir auf Discord.']);
        }

        $customer = Auth::user();
        $v = new Validator($_POST);
        $type = $v->oneOf('type', Settings::openTypes(), 'Diese Kategorie nehme ich gerade nicht an.');
        $name = $v->text('name', 2, 80, 'Name');
        $title = $v->text('title', 0, 120, 'Titel');
        $discord = $v->discord('discord');
        $email = $v->email('email', false);
        $budget = ($_POST['budget'] ?? '') === '' ? '' : $v->oneOf('budget', self::BUDGETS, 'Ungültiges Budget.');
        $deadline = $v->date('deadline', true);
        $message = $v->text('message', 20, 4000, 'Beschreibung', true);
        $v->accepted('consent', 'Bitte stimme der Datenverarbeitung zu.');
        $details = $type !== '' ? $this->details($type, $v) : [];

        if ($customer && $email === '') {
            $email = $customer['email'];
        }
        $errors = $v->errors();
        if (!isset($errors['discord']) && !isset($errors['email']) && $discord === '' && $email === '') {
            $v->addError('contact', 'Bitte gib Discord oder E-Mail an.');
        }
        if ($v->fails()) {
            $this->answer(422, ['ok' => false, 'message' => 'Bitte überprüfe deine Eingaben: ' . implode(' ', array_values($v->errors())), 'errors' => $v->errors()]);
        }

        [$request, $token] = RequestService::create(compact('type', 'name', 'title', 'discord', 'email', 'budget', 'deadline', 'message', 'details'), $customer);
        $_SESSION['_form_at'] = time();

        $url = $customer ? '/konto/anfragen/' . $request['id'] : '/anfrage/' . $token;
        $this->answer(200, [
            'ok'      => true,
            'message' => 'Danke! Deine Anfrage ' . $request['public_id'] . ' ist angekommen.',
            'url'     => $url,
            'mailed'  => !$customer && $email !== '' && \App\Services\Mailer::enabled(),
        ]);
    }

    /** JSON for the form script, redirect with a message for plain form posts (JavaScript disabled). */
    private function answer(int $status, array $data): never
    {
        if (wants_json()) {
            json_out($status, $data);
        }
        flash($data['ok'] ? 'success' : 'error', (string)$data['message']);
        redirect($data['ok'] && !empty($data['url']) ? $data['url'] : '/#anfrage');
    }

        /** Validates the type specific answers against the whitelist in RequestService::DETAILS. */
    private function details(string $type, Validator $v): array
    {
        $out = [];
        $input = is_array($_POST['details'] ?? null) ? $_POST['details'] : [];
        foreach (RequestService::DETAILS[$type] ?? [] as $key => $field) {
            $value = $input[$key] ?? null;
            if ($field['kind'] === 'multi') {
                $value = is_array($value) ? array_values(array_unique($value)) : [];
                if (array_diff($value, $field['options'])) {
                    $v->addError('details_' . $key, 'Ungültige Auswahl bei ' . $field['label'] . '.');
                    continue;
                }
                $out[$key] = $value;
            } else {
                if ($value === null || $value === '') {
                    continue;
                }
                if (!is_string($value) || !in_array($value, $field['options'], true)) {
                    $v->addError('details_' . $key, 'Ungültige Auswahl bei ' . $field['label'] . '.');
                    continue;
                }
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function showByToken(array $p): void
    {
        $request = RequestService::findByToken((string)($p['token'] ?? '')) ?? abort(404);
        $this->render($request, '/anfrage/' . $p['token']);
    }

    public function showForCustomer(array $p): void
    {
        $user = Auth::require();
        $request = RequestService::findForCustomer(id_param($p), (int)$user['id']) ?? abort(404);
        $this->render($request, '/konto/anfragen/' . $request['id'], true);
    }

    private function render(array $request, string $base, bool $inAccount = false): void
    {
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
        RequestService::markRead((int)$request['id'], 'customer');

        $data = [
            'request'  => $request,
            'base'     => $base,
            'messages' => RequestService::messages((int)$request['id'], false),
            'offer'    => RequestService::pendingOffer((int)$request['id']),
            'accepted' => RequestService::acceptedOffer((int)$request['id']),
            'details'  => RequestService::detailLines($request),
            'invoices' => $request['customer_id']
                ? Db::all("SELECT id, number, status, total_cents FROM invoices WHERE request_id = ? AND status <> 'draft' ORDER BY id DESC", [$request['id']])
                : [],
            'inAccount' => $inAccount,
        ];
        View::render($inAccount ? 'site/account/request' : 'site/requests/show', $data, 'Anfrage ' . $request['public_id'], $inAccount ? 'portal' : 'layout');
    }

    public function replyByToken(array $p): void
    {
        $request = RequestService::findByToken((string)($p['token'] ?? '')) ?? abort(404);
        $this->reply($request, '/anfrage/' . $p['token']);
    }

    public function replyForCustomer(array $p): void
    {
        $user = Auth::require();
        $request = RequestService::findForCustomer(id_param($p), (int)$user['id']) ?? abort(404);
        $this->reply($request, '/konto/anfragen/' . $request['id']);
    }

    private function reply(array $request, string $back): void
    {
        if ($request['status'] === 'cancelled') {
            fail_back([], $back, 'Diese Anfrage ist storniert.');
        }
        if (!RateLimiter::hit('ticket', 'req' . $request['id'])) {
            fail_back([], $back, 'Bitte warte kurz, bevor du erneut schreibst.');
        }
        $v = new Validator($_POST);
        $body = $v->text('body', 2, 5000, 'Nachricht', true);
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        RequestService::customerMessage($request, $body);
        flash('success', 'Nachricht gesendet.');
        redirect($back);
    }

    public function offerByToken(array $p): void
    {
        $request = RequestService::findByToken((string)($p['token'] ?? '')) ?? abort(404);
        $this->respond($request, '/anfrage/' . $p['token']);
    }

    public function offerForCustomer(array $p): void
    {
        $user = Auth::require();
        $request = RequestService::findForCustomer(id_param($p), (int)$user['id']) ?? abort(404);
        $this->respond($request, '/konto/anfragen/' . $request['id']);
    }

    private function respond(array $request, string $back): void
    {
        $decision = (string)($_POST['decision'] ?? '');
        if (!in_array($decision, ['accept', 'decline'], true)) {
            abort(404);
        }
        try {
            RequestService::respondToOffer($request, $decision === 'accept');
        } catch (RuntimeException) {
            fail_back([], $back, 'Dieses Angebot ist nicht mehr offen.');
        }
        flash('success', $decision === 'accept' ? 'Angebot angenommen. Ich lege los!' : 'Angebot abgelehnt. Schreib mir gern, was ich anpassen soll.');
        redirect($back);
    }

    public static function typeLabel(string $type): string
    {
        return Notifier::TYPES[$type] ?? $type;
    }
}
