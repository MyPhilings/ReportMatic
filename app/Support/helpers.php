<?php

declare(strict_types=1);

use App\Core\View;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return $path === '' ? BASE_PATH : BASE_PATH . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path = ''): string
    {
        return base_path('views' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        $baseUrl = rtrim((string) config('app.url', 'http://localhost/ReportMatic'), '/');

        return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('load_environment_file')) {
    function load_environment_file(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $trimmed, 2));
            $value = trim($value, "\"'");

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $items = null;

        if ($items === null) {
            $items = [];

            foreach (glob(config_path('*.php')) ?: [] as $filePath) {
                $items[pathinfo($filePath, PATHINFO_FILENAME)] = require $filePath;
            }
        }

        if ($key === null) {
            return $items;
        }

        $segments = explode('.', $key);
        $value = $items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], bool $useLayout = true): string
    {
        return View::render($template, $data, $useLayout);
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower(trim($value))) ?? '';

        return trim($value, '-') ?: 'report';
    }
}

if (!function_exists('normalize_header_key')) {
    function normalize_header_key(string $value, int $index = 0): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower(trim($value))) ?? '';
        $value = trim($value, '_');

        return $value !== '' ? $value : 'column_' . ($index + 1);
    }
}

if (!function_exists('human_filesize')) {
    function human_filesize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return date('M d, Y h:i A', strtotime($value));
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed
    {
        if ($value === null) {
            $message = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);

            return $message;
        }

        $_SESSION['_flash'][$key] = $value;

        return null;
    }
}

if (!function_exists('request_method')) {
    function request_method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}

if (!function_exists('request_input')) {
    function request_input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}

if (!function_exists('safe_json_decode')) {
    function safe_json_decode(?string $json, array $default = []): array
    {
        if ($json === null || $json === '') {
            return $default;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : $default;
    }
}

if (!function_exists('truncate_text')) {
    function truncate_text(string $value, int $limit = 80): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit - 1) . '…';
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}