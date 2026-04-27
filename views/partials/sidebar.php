<?php

$currentPage = $currentPage ?? 'dashboard';

$navigation = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'speedometer2', 'href' => asset_url('index.php?page=dashboard')],
    ['key' => 'upload', 'label' => 'Upload Excel', 'icon' => 'cloud-arrow-up', 'href' => asset_url('index.php?page=upload')],
    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'file-earmark-text', 'href' => asset_url('index.php?page=reports')],
    ['key' => 'history', 'label' => 'History', 'icon' => 'clock-history', 'href' => asset_url('index.php?page=history')],
];
?>
<div class="sidebar-shell h-100">
    <div class="sidebar-brand">
        <div class="sidebar-logo">R</div>
        <div>
            <div class="fw-bold fs-5 text-dark"><?= e(config('app.name')) ?></div>
            <div class="small text-secondary">Excel to report pipeline</div>
        </div>
    </div>

    <nav class="sidebar-nav nav flex-column gap-2">
        <?php foreach ($navigation as $item): ?>
            <a class="nav-link sidebar-link <?= $currentPage === $item['key'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                <i class="bi bi-<?= e($item['icon']) ?> me-2"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-card mt-auto">
        <div class="sidebar-card-label">Workflow</div>
        <h6 class="mb-2">Upload, preview, generate, export.</h6>
        <p class="mb-0 text-secondary small">All files are stored in MySQL and kept ready for report generation and PDF export.</p>
    </div>
</div>