<?php

$pageTitle = $pageTitle ?? config('app.name');
$flashSuccess = flash('success');
$flashError = flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(config('app.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="minimal-app">
    <main class="minimal-page">
        <div class="minimal-page-inner">
            <?php if ($flashSuccess || $flashError): ?>
                <div class="message-stack mb-4">
                    <?php if ($flashSuccess): ?>
                        <div class="notice notice-success"><?= e($flashSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($flashError): ?>
                        <div class="notice notice-error"><?= e($flashError) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>