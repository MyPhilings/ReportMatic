<?php

$reports = $reports ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1];
?>
<div class="card section-card border-0 shadow-sm">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h5 class="section-title mb-1">Report history</h5>
                <p class="text-secondary mb-0">All generated reports with view, export, and delete actions.</p>
            </div>
            <a class="btn btn-outline-primary rounded-pill" href="<?= e(asset_url('index.php?page=reports')) ?>"><i class="bi bi-plus-circle me-1"></i> New report</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Report title</th>
                        <th>Status</th>
                        <th>Generated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($report['original_filename']) ?></div>
                                    <div class="small text-secondary">Upload #<?= e((string) $report['upload_id']) ?></div>
                                </td>
                                <td><?= e($report['report_title']) ?></td>
                                <td><span class="status-pill status-<?= e($report['report_status']) ?>"><?= e(ucfirst((string) $report['report_status'])) ?></span></td>
                                <td><?= e(format_datetime($report['created_at'])) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e(asset_url('index.php?page=view-report&id=' . (int) $report['id'])) ?>">View</a>
                                    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e(asset_url('index.php?action=download-pdf&id=' . (int) $report['id'])) ?>">PDF</a>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill js-delete-report" type="button" data-report-id="<?= e((string) $report['id']) ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state text-center py-5">
                                    <div class="empty-icon mb-3"><i class="bi bi-clock-history"></i></div>
                                    <h6 class="mb-2">No report history yet</h6>
                                    <p class="text-secondary mb-0">Generated reports will appear here once they are created.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <nav class="mt-4">
                <ul class="pagination pagination-rounded justify-content-end mb-0">
                    <?php for ($pageNumber = 1; $pageNumber <= (int) $pagination['total_pages']; $pageNumber++): ?>
                        <li class="page-item <?= (int) ($pagination['page'] ?? 1) === $pageNumber ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(asset_url('index.php?page=history&p=' . $pageNumber)) ?>"><?= e((string) $pageNumber) ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>