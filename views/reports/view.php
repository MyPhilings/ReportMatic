<?php

$report = $report ?? [];
$upload = $upload ?? [];
$summary = $summary ?? [];
$columns = $columns ?? [];
$rows = $rows ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0, 'per_page' => 25];
$metrics = $metrics ?? ['row_count' => 0, 'column_count' => 0, 'sheet_count' => 0];
?>

<div class="report-shell">
    <div class="report-summary-grid mb-4">
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Rows</div>
                <div class="summary-number"><?= e(number_format((int) $metrics['row_count'])) ?></div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Columns</div>
                <div class="summary-number"><?= e(number_format((int) $metrics['column_count'])) ?></div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Sheets</div>
                <div class="summary-number"><?= e(number_format((int) $metrics['sheet_count'])) ?></div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Source file</div>
                <div class="summary-number small"><?= e(truncate_text((string) ($upload['original_filename'] ?? ''), 28)) ?></div>
            </div>
        </div>
    </div>

    <div class="card section-card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                <div>
                    <div class="eyebrow text-uppercase small fw-semibold letter-spacing">Report details</div>
                    <h4 class="mb-2"><?= e($report['report_title'] ?? 'Untitled report') ?></h4>
                    <div class="text-secondary">Generated from <?= e($upload['original_filename'] ?? 'unknown file') ?> on <?= e(format_datetime($report['created_at'] ?? null)) ?></div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <span class="status-pill status-<?= e($report['report_status'] ?? 'generated') ?>"><?= e(ucfirst((string) ($report['report_status'] ?? 'generated'))) ?></span>
                    <a class="btn btn-outline-secondary rounded-pill" href="<?= e(asset_url('index.php?page=history')) ?>"><i class="bi bi-arrow-left me-1"></i> History</a>
                </div>
            </div>

            <?php if (!empty($summary['headers'])): ?>
                <div class="summary-chip-wrap mb-4">
                    <?php foreach ($summary['headers'] as $headerLabel): ?>
                        <span class="summary-chip"><?= e($headerLabel) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive report-table-wrap">
                <table class="table table-hover align-middle report-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Source sheet</th>
                            <th class="text-nowrap">Row #</th>
                            <?php foreach ($columns as $column): ?>
                                <th class="text-nowrap"><?= e($column['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="text-nowrap"><?= e($row['sheet_name']) ?></td>
                                    <td><?= e((string) $row['row_index']) ?></td>
                                    <?php foreach ($columns as $column): ?>
                                        <?php $value = $row['column_data'][$column['key']] ?? ''; ?>
                                        <td><?= e(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= e((string) (2 + count($columns))) ?>">
                                    <div class="empty-state text-center py-5">
                                        <div class="empty-icon mb-3"><i class="bi bi-inboxes"></i></div>
                                        <h6 class="mb-2">No rows available</h6>
                                        <p class="text-secondary mb-0">The report exists, but the current page has no visible row data.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination pagination-rounded mb-0">
                        <?php for ($pageNumber = 1; $pageNumber <= (int) $pagination['total_pages']; $pageNumber++): ?>
                            <li class="page-item <?= (int) ($pagination['page'] ?? 1) === $pageNumber ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(asset_url('index.php?page=view-report&id=' . (int) $report['id'] . '&p=' . $pageNumber)) ?>"><?= e((string) $pageNumber) ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>