<?php
?>
<div class="row g-4 fade-in-up">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-cloud-upload"></i></div>
                    <span class="stat-badge">Uploads</span>
                </div>
                <div class="stat-value"><?= e((string) ($stats['uploads'] ?? 0)) ?></div>
                <div class="stat-label">Excel files imported</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-info-soft text-info"><i class="bi bi-file-earmark-text"></i></div>
                    <span class="stat-badge">Reports</span>
                </div>
                <div class="stat-value"><?= e((string) ($stats['reports'] ?? 0)) ?></div>
                <div class="stat-label">Generated report records</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-success-soft text-success"><i class="bi bi-table"></i></div>
                    <span class="stat-badge">Rows</span>
                </div>
                <div class="stat-value"><?= e(number_format((int) ($stats['rows'] ?? 0))) ?></div>
                <div class="stat-label">Structured row records stored</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="stat-icon bg-warning-soft text-warning"><i class="bi bi-clock-history"></i></div>
                    <span class="stat-badge">Latest</span>
                </div>
                <div class="stat-value small">
                    <?= e(truncate_text((string) ($stats['latest_upload'] ?? 'No uploads yet'), 28)) ?>
                </div>
                <div class="stat-label">Most recent file</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-7">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="section-title mb-1">Recent uploads</h5>
                        <p class="text-secondary mb-0">Latest Excel imports and their processing status.</p>
                    </div>
                    <a class="btn btn-outline-primary btn-sm rounded-pill" href="<?= e(asset_url('index.php?page=upload')) ?>">
                        <i class="bi bi-plus-circle me-1"></i> Upload Excel
                    </a>
                </div>

                <div class="table-responsive dashboard-table">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th class="text-nowrap">Rows</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentUploads)): ?>
                                <?php foreach ($recentUploads as $upload): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($upload['original_filename']) ?></div>
                                            <div class="small text-secondary"><?= e($upload['filename']) ?></div>
                                        </td>
                                        <td><?= e(number_format((int) $upload['row_count'])) ?></td>
                                        <td>
                                            <span class="status-pill status-<?= e($upload['status']) ?>"><?= e(ucfirst((string) $upload['status'])) ?></span>
                                        </td>
                                        <td><?= e(format_datetime($upload['upload_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state text-center py-5">
                                            <div class="empty-icon mb-3"><i class="bi bi-inbox"></i></div>
                                            <h6 class="mb-2">No uploads yet</h6>
                                            <p class="text-secondary mb-0">Start by importing an Excel workbook to build your first report.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="section-title mb-1">Recent reports</h5>
                        <p class="text-secondary mb-0">Generated report snapshots ready for export.</p>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= e(asset_url('index.php?page=reports')) ?>">
                        View all
                    </a>
                </div>

                <div class="list-group list-group-flush report-list">
                    <?php if (!empty($recentReports)): ?>
                        <?php foreach ($recentReports as $report): ?>
                            <a class="list-group-item list-group-item-action report-list-item" href="<?= e(asset_url('index.php?page=view-report&id=' . (int) $report['id'])) ?>">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($report['report_title']) ?></div>
                                        <div class="small text-secondary"><?= e($report['original_filename']) ?></div>
                                    </div>
                                    <span class="status-pill status-<?= e($report['report_status']) ?>"><?= e(ucfirst((string) $report['report_status'])) ?></span>
                                </div>
                                <div class="small text-secondary mt-2"><?= e(format_datetime($report['created_at'])) ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon mb-3"><i class="bi bi-file-earmark-text"></i></div>
                            <h6 class="mb-2">No reports generated</h6>
                            <p class="text-secondary mb-0">Generate a report from a processed upload to populate this area.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>