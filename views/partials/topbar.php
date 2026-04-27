<?php

$pageTitle = $pageTitle ?? config('app.name');
$pageSubtitle = $pageSubtitle ?? '';
$topbarActions = $topbarActions ?? '';
?>
<section class="page-hero card border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="eyebrow text-uppercase small fw-semibold letter-spacing">Enterprise Reporting</div>
            <h1 class="page-title mb-2"><?= e($pageTitle) ?></h1>
            <?php if ($pageSubtitle !== ''): ?>
                <p class="page-subtitle mb-0 text-secondary"><?= e($pageSubtitle) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($topbarActions !== ''): ?>
            <div class="topbar-actions d-flex flex-wrap gap-2">
                <?= $topbarActions ?>
            </div>
        <?php endif; ?>
    </div>
</section>