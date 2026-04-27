<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

$autoloadPath = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    spl_autoload_register(static function (string $className): void {
        $prefix = 'App\\';

        if (!str_starts_with($className, $prefix)) {
            return;
        }

        $relativeClass = substr($className, strlen($prefix));
        $filePath = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
        }
    });
}

require_once BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'helpers.php';

load_environment_file(BASE_PATH . DIRECTORY_SEPARATOR . '.env');

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Manila'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}