<?php

$report = $report ?? [];
$upload = $upload ?? [];
$summary = $summary ?? [];
$columns = $columns ?? [];
$rows = $rows ?? [];
$metrics = $metrics ?? ['row_count' => 0, 'column_count' => 0, 'sheet_count' => 0];
$autoPrint = $autoPrint ?? true;
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
            <div class="subtext">Source file: <?= e($upload['original_filename'] ?? 'unknown file') ?><br>Generated on: <?= e(format_datetime($report['created_at'] ?? null)) ?></div>
        </div>
        <div class="subtext" style="text-align:right;">
            Report ID: <?= e((string) ($report['id'] ?? 0)) ?><br>
            Sheets: <?= e((string) ($metrics['sheet_count'] ?? 0)) ?><br>
            Rows: <?= e((string) ($metrics['row_count'] ?? 0)) ?>
        </div>
    </div>

    <div class="metrics">
        <div class="metric-card">
            <div class="metric-label">Rows</div>
            <div class="metric-value"><?= e(number_format((int) ($metrics['row_count'] ?? 0))) ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Columns</div>
            <div class="metric-value"><?= e(number_format((int) ($metrics['column_count'] ?? 0))) ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Sheets</div>
            <div class="metric-value"><?= e(number_format((int) ($metrics['sheet_count'] ?? 0))) ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Status</div>
            <div class="metric-value"><?= e(ucfirst((string) ($report['report_status'] ?? 'generated'))) ?></div>
        </div>
    </div>

    <?php if (!empty($summary['headers'])): ?>
        <div class="chips">
            <?php foreach ($summary['headers'] as $headerLabel): ?>
                <span class="chip"><?= e($headerLabel) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Source sheet</th>
                <th>Row #</th>
                <?php foreach ($columns as $column): ?>
                    <th><?= e($column['label']) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['sheet_name']) ?></td>
                    <td><?= e((string) $row['row_index']) ?></td>
                    <?php foreach ($columns as $column): ?>
                        <?php $value = $row['column_data'][$column['key']] ?? ''; ?>
                        <td><?= e(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
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