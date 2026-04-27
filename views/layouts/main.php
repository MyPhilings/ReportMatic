<?php

$pageTitle = $pageTitle ?? config('app.name');
$pageSubtitle = $pageSubtitle ?? '';
$currentPage = $currentPage ?? 'dashboard';
$topbarActions = $topbarActions ?? '';
$flashSuccess = flash('success');
$flashError = flash('error');
$flashInfo = flash('info');

$navData = [
    'currentPage' => $currentPage,
];

$shellData = [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'topbarActions' => $topbarActions,
];

$appConfig = [
    'name' => config('app.name'),
    'baseUrl' => config('app.url'),
    'page' => $currentPage,
    'csrf' => $_SESSION['_csrf'] ?? null,
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f8fc">
    <title><?= e($pageTitle) ?> | <?= e(config('app.name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body class="app-shell">
<div class="app-backdrop"></div>

<div class="app-frame">
    <aside class="app-sidebar d-none d-lg-flex">
        <?= view('partials/sidebar', $navData, false) ?>
    </aside>

    <div class="app-main">
        <header class="mobile-topbar d-lg-none">
            <button class="btn btn-light border-0 rounded-pill shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="text-end">
                <div class="fw-semibold text-dark"><?= e(config('app.name')) ?></div>
                <div class="small text-secondary">Internal reporting workspace</div>
            </div>
        </header>

        <main class="app-content container-fluid px-3 px-lg-4 py-4 py-lg-4">
            <?= view('partials/topbar', $shellData, false) ?>

            <?php if ($flashSuccess || $flashError || $flashInfo): ?>
                <div class="mb-4">
                    <?php if ($flashSuccess): ?>
                        <div class="alert alert-success alert-soft border-0 rounded-4 shadow-sm mb-0"><?= e($flashSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($flashError): ?>
                        <div class="alert alert-danger alert-soft border-0 rounded-4 shadow-sm mb-0"><?= e($flashError) ?></div>
                    <?php endif; ?>
                    <?php if ($flashInfo): ?>
                        <div class="alert alert-info alert-soft border-0 rounded-4 shadow-sm mb-0"><?= e($flashInfo) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div id="appAlerts"></div>

            <?= $content ?>
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-start app-offcanvas d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mobileSidebarLabel"><?= e(config('app.name')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?= view('partials/sidebar', $navData, false) ?>
    </div>
</div>

<script>
window.ReportMatix = <?= json_encode($appConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>