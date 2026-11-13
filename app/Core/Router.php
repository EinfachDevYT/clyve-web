<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, array $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method = $method === 'HEAD' ? 'GET' : $method;
        $path = rtrim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/') ?: '/';

        if ($method === 'POST' && APP_CONTEXT !== 'api') {
            if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) {
                http_response_code(413);
                exit;
            }
            Security::guardPost();
        }

        $allowed = [];
        foreach ($this->routes as [$verb, $pattern, [$class, $action]]) {
            $regex = '#^' . preg_replace_callback(
                '#\{([a-z]+)\}#',
                static fn(array $m) => match ($m[1]) { 'token' => '(?P<token>[a-f0-9]{40})', 'slug' => '(?P<slug>[a-z0-9-]{1,70})', default => '(?P<' . $m[1] . '>\d+)' },
                $pattern
            ) . '$#';
            if (!preg_match($regex, $path, $m)) {
                continue;
            }
            if ($verb !== $method) {
                $allowed[] = $verb;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            (new $class())->$action($params);
            return;
        }

        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            http_response_code(405);
            exit;
        }
        abort(404);
    }
}
