<?php

$processedUploads = $processedUploads ?? [];
$reports = $reports ?? [];
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <h5 class="section-title mb-2">Create a report</h5>
                <p class="text-secondary mb-4">Choose a processed Excel upload and create a structured report snapshot.</p>

                <?php if (!empty($processedUploads)): ?>
                    <form id="reportBuilderForm" action="<?= e(asset_url('index.php?action=generate-report')) ?>" method="post">
                        <div class="mb-3">
                            <label class="form-label">Select upload</label>
                            <select class="form-select" name="upload_id" required>
                                <?php foreach ($processedUploads as $upload): ?>
                                    <option value="<?= e((string) $upload['id']) ?>"><?= e($upload['original_filename']) ?> (<?= e((string) $upload['row_count']) ?> rows)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Report title</label>
                            <input class="form-control" type="text" name="report_title" placeholder="e.g. Quarterly operations report" required>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4" type="submit">
                            <i class="bi bi-magic me-1"></i> Generate
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-state text-center py-4">
                        <div class="empty-icon mb-3"><i class="bi bi-folder-x"></i></div>
                        <h6 class="mb-2">No processed uploads yet</h6>
                        <p class="text-secondary mb-0">Upload and import an Excel workbook first, then return here to generate a report.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="section-title mb-1">Generated reports</h5>
                        <p class="text-secondary mb-0">Recent report snapshots grouped by upload.</p>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= e(asset_url('index.php?page=history')) ?>">History</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Report title</th>
                                <th>Source file</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reports)): ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($report['report_title']) ?></div>
                                            <div class="small text-secondary">Report #<?= e((string) $report['id']) ?></div>
                                        </td>
                                        <td><?= e($report['original_filename']) ?></td>
                                        <td><span class="status-pill status-<?= e($report['report_status']) ?>"><?= e(ucfirst((string) $report['report_status'])) ?></span></td>
                                        <td><?= e(format_datetime($report['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= e(asset_url('index.php?page=view-report&id=' . (int) $report['id'])) ?>">View</a>
                                            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e(asset_url('index.php?action=download-pdf&id=' . (int) $report['id'])) ?>">PDF</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state text-center py-5">
                                            <div class="empty-icon mb-3"><i class="bi bi-file-earmark-text"></i></div>
                                            <h6 class="mb-2">No reports created yet</h6>
                                            <p class="text-secondary mb-0">Create your first report from a processed upload to populate this table.</p>
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
</div>