<?php

$reportData = is_array($reportData ?? null) ? $reportData : [];
$summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
$chartData = is_array($reportData['chart_data'] ?? null)
    ? $reportData['chart_data']
    : ['sheet_labels' => [], 'sheet_counts' => [], 'column_labels' => [], 'column_counts' => []];
$insights = is_array($reportData['insights'] ?? null) ? $reportData['insights'] : [];
$upload = is_array($reportData['upload'] ?? null) ? $reportData['upload'] : [];
$exportId = (int) ($upload['id'] ?? 0);
$reportJson = json_encode($chartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
$rowCount = (int) ($summary['row_count'] ?? 0);
$sheetCount = (int) ($summary['sheet_count'] ?? 0);
$columnCount = (int) ($summary['column_count'] ?? 0);
$completionPercentage = number_format((float) ($summary['completion_percentage'] ?? 0), 1);
$averageRowsPerSheet = number_format((float) ($summary['average_rows_per_sheet'] ?? 0), 1);
$largestSheetName = (string) ($summary['largest_sheet_name'] ?? 'Sheet');
$largestSheetRows = (int) ($summary['largest_sheet_rows'] ?? 0);
$bestFieldName = (string) ($summary['best_field_name'] ?? 'Field');
$bestFieldShare = number_format((float) ($summary['best_field_share'] ?? 0), 1);
$headerLabels = is_array($summary['headers'] ?? null) ? $summary['headers'] : [];
?>
<div id="generatedReport" class="report-section" data-report-data="<?= e($reportJson) ?>">
    <div class="report-hero">
        <div class="report-hero-copy">
            <div class="eyebrow report-eyebrow">Executive report</div>
            <h2><?= e($summary['file_name'] ?? 'Imported file') ?></h2>
            <p>
                <?= e((string) ($summary['file_name'] ?? 'Imported file')) ?> was imported and transformed into a corporate-style workbook summary with charted distribution, coverage insights, and executive metrics.
            </p>
            <div class="report-meta">
                <span class="report-meta-pill">Uploaded <?= e(format_datetime($summary['upload_date'] ?? null)) ?></span>
                <span class="report-meta-pill"><?= e(number_format($rowCount)) ?> rows</span>
                <span class="report-meta-pill"><?= e(number_format($sheetCount)) ?> sheets</span>
                <span class="report-meta-pill"><?= e(number_format($columnCount)) ?> columns</span>
            </div>
        </div>
        <div class="report-actions">
            <a class="btn btn-light border" href="<?= e(asset_url('index.php?action=print-report&upload_id=' . $exportId)) ?>" target="_blank" rel="noopener">Print</a>
            <a class="btn btn-primary" href="<?= e(asset_url('index.php?action=download-pdf&upload_id=' . $exportId)) ?>">PDF</a>
        </div>
    </div>

    <div class="report-metrics">
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Rows imported</div>
                <div class="summary-number"><?= e(number_format($rowCount)) ?></div>
                <div class="summary-subtext">Across <?= e(number_format($sheetCount)) ?> sheet<?= $sheetCount === 1 ? '' : 's' ?></div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Sheets parsed</div>
                <div class="summary-number"><?= e(number_format($sheetCount)) ?></div>
                <div class="summary-subtext">Average <?= e($averageRowsPerSheet) ?> rows per sheet</div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Columns mapped</div>
                <div class="summary-number"><?= e(number_format($columnCount)) ?></div>
                <div class="summary-subtext">Best field: <?= e($bestFieldName) ?></div>
            </div>
        </div>
        <div class="report-summary-card card border-0 shadow-sm">
            <div class="card-body">
                <div class="summary-label">Data completeness</div>
                <div class="summary-number"><?= e($completionPercentage) ?>%</div>
                <div class="summary-subtext">Filled cells across mapped columns</div>
            </div>
        </div>
    </div>

    <?php if ($headerLabels !== []): ?>
        <div class="summary-chip-wrap">
            <?php foreach ($headerLabels as $headerLabel): ?>
                <span class="summary-chip"><?= e($headerLabel) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="report-panel">
        <div class="report-panel-head">
            <div>
                <div class="section-kicker">Charts</div>
                <h3>Workbook distribution</h3>
                <p>Rows by sheet and field completeness presented as an executive dashboard.</p>
            </div>
            <div class="chart-note"><?= e(number_format($largestSheetRows)) ?> peak rows</div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-card-head">
                    <div>
                        <div class="section-kicker">Sheet volume</div>
                        <h3>Rows by sheet</h3>
                        <p><?= e($largestSheetName) ?> is the largest sheet by row volume.</p>
                    </div>
                    <div class="chart-note"><?= e(number_format($rowCount)) ?> rows</div>
                </div>
                <canvas id="sheetChart" height="220"></canvas>
            </div>

            <div class="chart-card">
                <div class="chart-card-head">
                    <div>
                        <div class="section-kicker">Field coverage</div>
                        <h3>Column completeness</h3>
                        <p><?= e($bestFieldName) ?> leads the workbook at <?= e($bestFieldShare) ?>% coverage.</p>
                    </div>
                    <div class="chart-note"><?= e($completionPercentage) ?>% complete</div>
                </div>
                <canvas id="columnChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-head">
            <div>
                <div class="section-kicker">Insights</div>
                <h3>Executive summary</h3>
                <p>Key signals derived from the imported workbook structure.</p>
            </div>
            <div class="chart-note"><?= e($averageRowsPerSheet) ?> avg rows / sheet</div>
        </div>

        <div class="insight-grid">
            <?php foreach ($insights as $insight): ?>
                <div class="insight-card">
                    <div class="insight-label"><?= e((string) ($insight['title'] ?? 'Insight')) ?></div>
                    <div class="insight-value"><?= e((string) ($insight['value'] ?? '-')) ?></div>
                    <div class="insight-note"><?= e((string) ($insight['note'] ?? '')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>