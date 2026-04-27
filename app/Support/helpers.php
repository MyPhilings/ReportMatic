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

if (!function_exists('build_report_visual_data')) {
    function build_report_visual_data(array $upload, array $summary, array $rows, array $columns = []): array
    {
        $columnMap = [];

        if (is_array($summary['column_map'] ?? null)) {
            foreach ($summary['column_map'] as $key => $label) {
                $normalizedKey = (string) $key;

                $columnMap[$normalizedKey] = trim((string) $label) !== ''
                    ? (string) $label
                    : ucfirst(str_replace('_', ' ', $normalizedKey));
            }
        }

        if ($columnMap === [] && $columns !== []) {
            foreach ($columns as $column) {
                $normalizedKey = (string) ($column['key'] ?? '');

                if ($normalizedKey === '') {
                    continue;
                }

                $label = trim((string) ($column['label'] ?? ''));

                $columnMap[$normalizedKey] = $label !== ''
                    ? $label
                    : ucfirst(str_replace('_', ' ', $normalizedKey));
            }
        }

        if ($columnMap === [] && !empty($rows[0]['column_data']) && is_array($rows[0]['column_data'])) {
            foreach (array_keys($rows[0]['column_data']) as $key) {
                $normalizedKey = (string) $key;
                $columnMap[$normalizedKey] = ucfirst(str_replace('_', ' ', $normalizedKey));
            }
        }

        $rowCount = count($rows);
        $sheetGroups = [];

        if (is_array($summary['sheets'] ?? null) && ($summary['sheets'] ?? []) !== []) {
            foreach ($summary['sheets'] as $sheet) {
                $sheetName = trim((string) ($sheet['sheet_name'] ?? 'Sheet')) ?: 'Sheet';
                $sheetGroups[$sheetName] = max(0, (int) ($sheet['row_count'] ?? 0));
            }
        }

        if ($sheetGroups === [] || array_sum($sheetGroups) === 0) {
            $sheetGroups = [];

            foreach ($rows as $row) {
                $sheetName = trim((string) ($row['sheet_name'] ?? 'Sheet 1')) ?: 'Sheet 1';
                $sheetGroups[$sheetName] = ($sheetGroups[$sheetName] ?? 0) + 1;
            }
        }

        $sheetLabels = array_keys($sheetGroups);
        $sheetCounts = array_values($sheetGroups);

        if ($sheetLabels === [] && $rowCount > 0) {
            $sheetLabels = ['Sheet 1'];
            $sheetCounts = [$rowCount];
        }

        $sheetPercentages = [];

        foreach ($sheetCounts as $count) {
            $sheetPercentages[] = $rowCount > 0 ? round(($count / $rowCount) * 100, 1) : 0.0;
        }

        $columnLabels = array_values($columnMap);
        $columnCounts = [];
        $columnPercentages = [];

        foreach (array_keys($columnMap) as $columnKey) {
            $count = 0;

            foreach ($rows as $row) {
                $value = $row['column_data'][$columnKey] ?? null;

                if ($value !== null && $value !== '') {
                    $count++;
                }
            }

            $columnCounts[] = $count;
            $columnPercentages[] = $rowCount > 0 ? round(($count / $rowCount) * 100, 1) : 0.0;
        }

        $filledCells = array_sum($columnCounts);
        $totalCells = max(1, $rowCount * max(1, count($columnLabels)));
        $completionPercentage = round(($filledCells / $totalCells) * 100, 1);
        $sheetCount = count($sheetLabels);
        $averageRowsPerSheet = $sheetCount > 0 ? round($rowCount / $sheetCount, 1) : 0.0;

        $topSheetIndex = $sheetCounts === [] ? false : array_search(max($sheetCounts), $sheetCounts, true);
        $topSheetName = $topSheetIndex !== false ? (string) ($sheetLabels[$topSheetIndex] ?? 'Sheet') : 'Sheet';
        $topSheetRows = $topSheetIndex !== false ? (int) ($sheetCounts[$topSheetIndex] ?? 0) : 0;
        $topSheetShare = $rowCount > 0 ? round(($topSheetRows / $rowCount) * 100, 1) : 0.0;

        $bestFieldIndex = $columnCounts === [] ? false : array_search(max($columnCounts), $columnCounts, true);
        $bestFieldLabel = $bestFieldIndex !== false ? (string) ($columnLabels[$bestFieldIndex] ?? 'Field') : 'Field';
        $bestFieldShare = $bestFieldIndex !== false && $rowCount > 0
            ? round(((int) ($columnCounts[$bestFieldIndex] ?? 0) / $rowCount) * 100, 1)
            : 0.0;

        $lowestFieldIndex = $columnCounts === [] ? false : array_search(min($columnCounts), $columnCounts, true);
        $lowestFieldLabel = $lowestFieldIndex !== false ? (string) ($columnLabels[$lowestFieldIndex] ?? 'Field') : 'Field';
        $lowestFieldShare = $lowestFieldIndex !== false && $rowCount > 0
            ? round(((int) ($columnCounts[$lowestFieldIndex] ?? 0) / $rowCount) * 100, 1)
            : 0.0;

        $sourceFile = (string) ($upload['original_filename'] ?? $upload['filename'] ?? 'Imported file');

        return [
            'summary' => array_merge($summary, [
                'file_name' => $sourceFile,
                'upload_date' => $upload['upload_date'] ?? null,
                'row_count' => $rowCount,
                'sheet_count' => $sheetCount,
                'column_count' => count($columnLabels),
                'headers' => array_values($columnMap),
                'column_map' => $columnMap,
                'filled_cells' => $filledCells,
                'total_cells' => $totalCells,
                'completion_percentage' => $completionPercentage,
                'average_rows_per_sheet' => $averageRowsPerSheet,
                'largest_sheet_name' => $topSheetName,
                'largest_sheet_rows' => $topSheetRows,
                'largest_sheet_share' => $topSheetShare,
                'best_field_name' => $bestFieldLabel,
                'best_field_share' => $bestFieldShare,
                'lowest_field_name' => $lowestFieldLabel,
                'lowest_field_share' => $lowestFieldShare,
            ]),
            'chart_data' => [
                'sheet_labels' => $sheetLabels,
                'sheet_counts' => $sheetCounts,
                'sheet_percentages' => $sheetPercentages,
                'column_labels' => $columnLabels,
                'column_counts' => $columnCounts,
                'column_percentages' => $columnPercentages,
            ],
            'insights' => [
                [
                    'title' => 'Largest sheet',
                    'value' => $topSheetName,
                    'note' => number_format($topSheetRows) . ' rows, ' . number_format($topSheetShare, 1) . '% of the workbook',
                ],
                [
                    'title' => 'Average rows / sheet',
                    'value' => number_format($averageRowsPerSheet, 1),
                    'note' => 'Across ' . number_format($sheetCount) . ' sheet' . ($sheetCount === 1 ? '' : 's'),
                ],
                [
                    'title' => 'Data completeness',
                    'value' => number_format($completionPercentage, 1) . '%',
                    'note' => number_format($filledCells) . ' filled cells across mapped columns',
                ],
                [
                    'title' => 'Best covered field',
                    'value' => $bestFieldLabel,
                    'note' => number_format($bestFieldShare, 1) . '% populated across imported rows',
                ],
            ],
        ];
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}