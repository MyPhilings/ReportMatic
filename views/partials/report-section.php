<?php

$reportData = $reportData ?? [];
$summary = $reportData['summary'] ?? [];
$chartData = $reportData['chart_data'] ?? ['sheet_labels' => [], 'sheet_counts' => [], 'column_labels' => [], 'column_counts' => []];
$insights = $reportData['insights'] ?? [];
$upload = $reportData['upload'] ?? [];
$exportId = (int) ($upload['id'] ?? 0);
$reportJson = json_encode($chartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<div id="generatedReport" class="report-section" data-report-data="<?= e((string) $reportJson) ?>">
    <div class="report-head">
        <div>
            <div class="eyebrow">Report</div>
            <h2>Import insights</h2>
            <p><?= e($summary['file_name'] ?? 'Imported file') ?> imported successfully. The charts below summarize the workbook structure.</p>
        </div>
        <div class="report-actions">
            <a class="btn btn-light border" href="<?= e(asset_url('index.php?action=print-report&upload_id=' . $exportId)) ?>" target="_blank" rel="noopener">Print</a>
            <a class="btn btn-primary" href="<?= e(asset_url('index.php?action=download-pdf&upload_id=' . $exportId)) ?>">PDF</a>
        </div>
    </div>

    <div class="report-metrics">
        <div class="result-stat">
            <span class="result-value"><?= e((string) ($summary['row_count'] ?? 0)) ?></span>
            <span class="result-label">Rows imported</span>
        </div>
        <div class="result-stat">
            <span class="result-value"><?= e((string) ($summary['sheet_count'] ?? 0)) ?></span>
            <span class="result-label">Sheets parsed</span>
        </div>
        <div class="result-stat">
            <span class="result-value"><?= e((string) ($summary['column_count'] ?? 0)) ?></span>
            <span class="result-label">Columns mapped</span>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h3>Rows per sheet</h3>
            <canvas id="sheetChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h3>Column completeness</h3>
            <canvas id="columnChart" height="180"></canvas>
        </div>
    </div>

    <div class="insight-grid">
        <?php foreach ($insights as $insight): ?>
            <div class="insight-card">
                <div class="insight-title"><?= e($insight['title'] ?? '') ?></div>
                <div class="insight-value"><?= e($insight['value'] ?? '') ?></div>
                <div class="insight-note"><?= e($insight['note'] ?? '') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>