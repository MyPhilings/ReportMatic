<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], bool $useLayout = true): string
    {
        $templateFile = view_path($template . '.php');

        if (!file_exists($templateFile)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $templateFile;
        $content = ob_get_clean();

        if ($useLayout === false) {
            return $content;
        }

        $layoutFile = view_path('layouts/main.php');

        if (!file_exists($layoutFile)) {
            throw new RuntimeException('Layout not found: layouts/main');
        }

        $contentForLayout = $content;
        unset($content);

        extract(array_merge($data, ['content' => $contentForLayout]), EXTR_SKIP);

        ob_start();
        include $layoutFile;

        return (string) ob_get_clean();
    }
}