<?php

$report = $report ?? [];
$reportData = is_array($reportData ?? null) ? $reportData : [];
$upload = $upload ?? [];
$summary = is_array($summary ?? null) ? $summary : ($reportData['summary'] ?? []);
$columns = is_array($columns ?? null) ? $columns : ($reportData['columns'] ?? []);
$rows = is_array($rows ?? null) ? $rows : ($reportData['rows'] ?? []);
$metrics = $metrics ?? ['row_count' => 0, 'column_count' => 0, 'sheet_count' => 0];
$chartData = is_array($chart_data ?? null) ? $chart_data : ($reportData['chart_data'] ?? []);
$insights = is_array($insights ?? null) ? $insights : ($reportData['insights'] ?? []);
$autoPrint = $autoPrint ?? true;

$sheetLabels = is_array($chartData['sheet_labels'] ?? null) ? $chartData['sheet_labels'] : [];
$sheetCounts = is_array($chartData['sheet_counts'] ?? null) ? $chartData['sheet_counts'] : [];
$columnLabels = is_array($chartData['column_labels'] ?? null)
    ? $chartData['column_labels']
    : array_map(static function ($column): string {
        return (string) ($column['label'] ?? $column);
    }, $columns);
$columnCounts = is_array($chartData['column_counts'] ?? null) ? $chartData['column_counts'] : [];
$completionPercentage = number_format((float) ($summary['completion_percentage'] ?? 0), 1);

$sheetBars = [];
$sheetMax = 1;

if ($sheetCounts !== []) {
    $sheetMax = max(1, max(array_map('intval', $sheetCounts)));
}

foreach ($sheetLabels as $index => $label) {
    $count = (int) ($sheetCounts[$index] ?? 0);
    $sheetBars[] = [
        'label' => (string) $label,
        'count' => $count,
        'width' => $sheetMax > 0 ? (int) round(($count / $sheetMax) * 100) : 0,
    ];
}

$columnBars = [];
$rowTotal = max(1, (int) ($metrics['row_count'] ?? count($rows)));

foreach ($columnLabels as $index => $label) {
    $count = (int) ($columnCounts[$index] ?? 0);
    $columnBars[] = [
        'label' => (string) $label,
        'count' => $count,
        'percentage' => (int) round(($count / $rowTotal) * 100),
        'width' => (int) round(($count / $rowTotal) * 100),
    ];
}

$sheetSeries = [];

foreach ($sheetLabels as $index => $label) {
    $count = (int) ($sheetCounts[$index] ?? 0);

    $sheetSeries[] = [
        'label' => (string) $label,
        'count' => $count,
        'share' => $rowTotal > 0 ? round(($count / $rowTotal) * 100, 1) : 0.0,
    ];
}

$sortedSheetSeries = $sheetSeries;
usort($sortedSheetSeries, static fn (array $left, array $right): int => $right['count'] <=> $left['count']);

$sheetTotal = array_sum(array_map(static fn (array $segment): int => (int) $segment['count'], $sortedSheetSeries));
$pieSource = $sortedSheetSeries;

if (count($pieSource) > 5) {
    $otherCount = 0;

    foreach (array_slice($pieSource, 5) as $segment) {
        $otherCount += (int) ($segment['count'] ?? 0);
    }

    $pieSource = array_slice($pieSource, 0, 5);

    if ($otherCount > 0) {
        $pieSource[] = [
            'label' => 'Other',
            'count' => $otherCount,
            'share' => $rowTotal > 0 ? round(($otherCount / $rowTotal) * 100, 1) : 0.0,
        ];
    }
}

$buildPiePath = static function (float $centerX, float $centerY, float $radius, float $startAngle, float $endAngle): string {
    $startRadians = deg2rad($startAngle);
    $endRadians = deg2rad($endAngle);

    $startX = $centerX + ($radius * cos($startRadians));
    $startY = $centerY + ($radius * sin($startRadians));
    $endX = $centerX + ($radius * cos($endRadians));
    $endY = $centerY + ($radius * sin($endRadians));
    $largeArcFlag = ($endAngle - $startAngle) > 180 ? 1 : 0;

    return sprintf(
        'M %.4f %.4f L %.4f %.4f A %.4f %.4f 0 %d 1 %.4f %.4f Z',
        $centerX,
        $centerY,
        $startX,
        $startY,
        $radius,
        $radius,
        $largeArcFlag,
        $endX,
        $endY
    );
};

$piePalette = ['#0f766e', '#2563eb', '#7c3aed', '#f59e0b', '#14b8a6', '#64748b'];
$pieSlices = [];
$runningAngle = -90.0;

foreach ($pieSource as $index => $segment) {
    $count = (int) ($segment['count'] ?? 0);

    if ($sheetTotal <= 0 || $count <= 0) {
        continue;
    }

    $share = $count / $sheetTotal;
    $endAngle = $runningAngle + (360.0 * $share);

    $pieSlices[] = [
        'label' => (string) ($segment['label'] ?? 'Sheet'),
        'count' => $count,
        'share' => round($share * 100, 1),
        'color' => $piePalette[$index % count($piePalette)],
        'path' => $buildPiePath(110.0, 110.0, 84.0, $runningAngle, $endAngle),
    ];

    $runningAngle = $endAngle;
}

if ($pieSlices === [] && $sheetTotal <= 0) {
    $pieSlices[] = [
        'label' => 'No data',
        'count' => 0,
        'share' => 0.0,
        'color' => '#dbe3f0',
        'path' => 'M 110 110 L 194 110 A 84 84 0 1 1 194 109.9 Z',
    ];
}

$barSeries = array_slice($sortedSheetSeries, 0, 6);
$barMax = $barSeries !== [] ? max(1, max(array_map(static fn (array $segment): int => (int) $segment['count'], $barSeries))) : 1;
// Derive summary metrics to avoid undefined variable warnings when parts of
// the chart/summary data are missing.
$sheetCount = (int) ($metrics['sheet_count'] ?? count($sheetLabels ?? []));
$columnCount = (int) ($metrics['column_count'] ?? count($columnLabels ?? []));

$largestSheetName = 'N/A';
$largestSheetRows = 0;
if ($sortedSheetSeries !== []) {
    $largestSheetName = (string) ($sortedSheetSeries[0]['label'] ?? 'N/A');
    $largestSheetRows = (int) ($sortedSheetSeries[0]['count'] ?? 0);
}

$bestFieldName = 'N/A';
$bestFieldShare = 0;
if ($columnBars !== []) {
    $best = $columnBars[0];
    foreach ($columnBars as $cb) {
        if (($cb['percentage'] ?? 0) > ($best['percentage'] ?? 0)) {
            $best = $cb;
        }
    }
    $bestFieldName = (string) ($best['label'] ?? 'N/A');
    $bestFieldShare = $best['percentage'] ?? 0;
}
$executiveSummary = (string) ($summary['executive_summary'] ?? $reportData['executive_summary'] ?? ($summary['note'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($report['report_title'] ?? 'Report') ?></title>
    <style>
        :root {
            color-scheme: light;
        }

        @page {
            margin: 18mm 14mm 18mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #ffffff;
        }

        .report-page {
            max-width: 100%;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 1px solid #dbe3f0;
        }

        .brand {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #2563eb;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 26px;
            line-height: 1.15;
        }

        .subtext {
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-end;
            margin: 6px 0 0;
        }

        .section-kicker {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #2563eb;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
            line-height: 1.2;
        }

        .section-note {
            max-width: 320px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
            text-align: right;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .metric-card {
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            padding: 14px 16px;
            background: #f8fbff;
        }

        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 6px 11px;
            border-radius: 999px;
            background: #edf4ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
        }

        .visual-section {
            display: grid;
            gap: 14px;
            margin-bottom: 18px;
        }

        .insight-grid,
        .chart-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .insight-card,
        .chart-card {
            border: 1px solid #dbe3f0;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.03);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .insight-card {
            flex: 1 1 180px;
            padding: 14px 16px;
            background: #f8fbff;
        }

        .insight-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .insight-value {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
            margin-bottom: 6px;
        }

        .insight-note {
            font-size: 12px;
            line-height: 1.5;
            color: #64748b;
        }

        .chart-card {
            flex: 1 1 280px;
            padding: 16px;
        }

        .chart-card-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 14px;
        }

        .chart-card-head h3 {
            margin: 4px 0 0;
            font-size: 16px;
            line-height: 1.2;
        }

        .chart-card-head p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .chart-note {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1d4ed8;
            white-space: nowrap;
        }

        .bar-list {
            display: grid;
            gap: 12px;
        }

        .bar-row {
            display: grid;
            gap: 8px;
        }

        .bar-row-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: baseline;
            font-size: 12px;
            line-height: 1.4;
        }

        .bar-row-label {
            font-weight: 700;
            color: #0f172a;
        }

        .bar-row-meta {
            font-weight: 700;
            color: #64748b;
            white-space: nowrap;
        }

        .bar-track {
            height: 10px;
            border-radius: 999px;
            background: #e8eef8;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 999px;
            background: #2563eb;
        }

        .bar-fill.alt {
            background: #0f766e;
        }

        .bar-subtext {
            font-size: 11px;
            color: #64748b;
        }

        .empty-state {
            padding: 14px;
            border: 1px dashed #c7d2e5;
            border-radius: 14px;
            color: #64748b;
            font-size: 12px;
            background: #f8fbff;
        }

        .table-wrap {
            border: 1px solid #dbe3f0;
            border-radius: 16px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        thead {
            display: table-header-group;
            background: #f8fafc;
        }

        th, td {
            border-bottom: 1px solid #e5ebf4;
            padding: 10px 12px;
            font-size: 11px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #475569;
        }

        tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        tr {
            page-break-inside: avoid;
        }

        .footer {
            margin-top: 18px;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid #dbe3f0;
            padding-top: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="report-page">
    <div class="report-header">
        <div>
            <div class="brand"><?= e(config('app.name')) ?></div>
            <h1><?= e($report['report_title'] ?? 'Report') ?></h1>
            <div class="subtext">Generated on: <?= e(format_datetime($report['created_at'] ?? null)) ?></div>
        </div>
    </div>

    <div class="visual-section">
        <div class="section-heading">
            <div>
                <div class="section-kicker">Highlights</div>
                <h2>Executive summary & charts</h2>
            </div>
            <div class="section-note"><?= e($summary['subtitle'] ?? '') ?></div>
        </div>

        <?php if (!empty($executiveSummary)): ?>
            <div style="max-width:1100px;margin:8px auto 12px;color:#425a4d;font-size:13px;"><?= e($executiveSummary) ?></div>
        <?php endif; ?>

        <?php if ($insights !== []): ?>
            <div class="insight-grid">
                <?php foreach ($insights as $insight): ?>
                    <div class="insight-card">
                        <div class="insight-label"><?= e((string) ($insight['title'] ?? 'Insight')) ?></div>
                        <div class="insight-value"><?= e((string) ($insight['value'] ?? '-')) ?></div>
                        <div class="insight-note"><?= e((string) ($insight['note'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-card-head">
                    <div>
                        <div class="section-kicker">Distribution</div>
                        <h3>Category share</h3>
                        <p>Proportional distribution of top categories.</p>
                    </div>
                    <div class="chart-note"></div>
                </div>

                <?php if ($pieSlices !== []): ?>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <svg viewBox="0 0 220 220" width="220" height="220" role="img" aria-label="Category distribution">
                            <?php foreach ($pieSlices as $slice): ?>
                                <path d="<?= e($slice['path']) ?>" fill="<?= e($slice['color']) ?>"></path>
                            <?php endforeach; ?>
                        </svg>
                        <div style="flex:1;">
                            <?php foreach ($pieSlices as $slice): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:13px;">
                                    <div style="display:flex;gap:8px;align-items:center;"><span style="width:12px;height:12px;background:<?= e($slice['color']) ?>;display:inline-block;border-radius:2px;"></span><span><?= e($slice['label']) ?></span></div>
                                    <div style="color:#475569"><?= e((string)$slice['share']) ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No chart data available</div>
                <?php endif; ?>
            </div>

            <div class="chart-card">
                <div class="chart-card-head">
                    <div>
                        <div class="section-kicker">Trends</div>
                        <h3>Top categories</h3>
                        <p>Leading categories by volume or coverage.</p>
                    </div>
                    <div class="chart-note"></div>
                </div>

                <?php if ($columnBars !== []): ?>
                    <div class="bar-list">
                        <?php foreach ($columnBars as $bar): ?>
                            <div class="bar-row">
                                <div class="bar-row-head">
                                    <span class="bar-row-label"><?= e($bar['label']) ?></span>
                                    <span class="bar-row-meta"><?= e((string) $bar['percentage']) ?>%</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill alt" style="width: <?= e((string) $bar['width']) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($barSeries !== []): ?>
                    <div class="bar-list">
                        <?php foreach ($barSeries as $segment): ?>
                            <div class="bar-row">
                                <div class="bar-row-head">
                                    <span class="bar-row-label"><?= e($segment['label']) ?></span>
                                    <span class="bar-row-meta"><?= e(number_format($segment['count'])) ?></span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?= e((string) round(((int)$segment['count'] / max(1, $barMax)) * 100)) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No trend data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <?php if (empty($columns)): ?>
                    <th>No data</th>
                <?php else: ?>
                    <?php foreach ($columns as $column): ?>
                        <th><?= e($column['label']) ?></th>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= max(1, count($columns)) ?>" style="color:#64748b;padding:12px;">No rows to display</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <?php $value = $row['column_data'][$column['key']] ?? ''; ?>
                            <td><?= e(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div><?= e(config('app.name')) ?> report export</div>
        <div>Structured Excel to report workflow</div>
    </div>
</div>

<?php if ($autoPrint): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
<?php endif; ?>
</body>
</html>