<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ReportMatix'),
    'url' => env('APP_URL', 'http://localhost/ReportMatic'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
    'upload_max_mb' => (int) env('UPLOAD_MAX_MB', 20),
    'preview_rows' => (int) env('PREVIEW_ROWS', 25),
    'page_size' => (int) env('PAGE_SIZE', 25),
];