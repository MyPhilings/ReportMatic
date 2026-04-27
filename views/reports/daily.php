<?php

$report = $report ?? [];
$data = is_array($reportData ?? null) ? $reportData : [];
$upload = $upload ?? [];
$autoPrint = $autoPrint ?? false;

$company = e(config('app.name'));
$title = e($report['report_title'] ?? ($data['title'] ?? 'Daily Report'));
$employee = e($data['employee_name'] ?? $report['author'] ?? '');
$department = e($data['department'] ?? '');
$date = e(format_datetime($report['created_at'] ?? ($data['date'] ?? null)));

$financial = is_array($data['financial'] ?? null) ? $data['financial'] : [];
$products = is_array($data['products'] ?? null) ? $data['products'] : [];
$revenue = is_array($data['revenue'] ?? null) ? $data['revenue'] : [];
$sales = is_array($data['sales'] ?? null) ? $data['sales'] : [];
$accounts = is_array($data['accounts_receivable'] ?? null) ? $data['accounts_receivable'] : [];
$expenses = is_array($data['expenses'] ?? null) ? $data['expenses'] : [];
$inventory = is_array($data['inventory'] ?? null) ? $data['inventory'] : [];
$highlights = is_array($data['highlights'] ?? null) ? $data['highlights'] : [];

function money($v) { return $v === '' || $v === null ? '-' : ('$' . number_format((float)$v, 0)); }

// Build a small pie chart from revenue (fallback to products by qty)
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

$piePalette = ['#2563eb', '#0f766e', '#7c3aed', '#f59e0b', '#14b8a6', '#64748b'];

$pieSource = [];
if ($revenue !== []) {
    foreach ($revenue as $r) {
        $amt = (float) ($r['amount'] ?? ($r[1] ?? 0));
        if ($amt <= 0) {
            continue;
        }
        $pieSource[] = [
            'label' => (string) ($r['label'] ?? ($r[0] ?? '')),
            'amount' => $amt,
        ];
    }
} elseif ($products !== []) {
    foreach ($products as $p) {
        $qty = (int) ($p['qty'] ?? ($p[1] ?? 0));
        if ($qty <= 0) {
            continue;
        }
        $pieSource[] = [
            'label' => (string) ($p['name'] ?? ($p[0] ?? '')),
            'amount' => $qty,
        ];
    }
}

$pieSlicesRevenue = [];
$totalPie = array_sum(array_map(static fn($s) => (float) ($s['amount'] ?? 0), $pieSource));
$runningAngle = -90.0;
foreach ($pieSource as $index => $segment) {
    $amt = (float) ($segment['amount'] ?? 0);
    if ($totalPie <= 0 || $amt <= 0) {
        continue;
    }
    $share = $amt / $totalPie;
    $endAngle = $runningAngle + (360.0 * $share);

    $pieSlicesRevenue[] = [
        'label' => (string) ($segment['label'] ?? ''),
        'amount' => $amt,
        'share' => round($share * 100, 1),
        'color' => $piePalette[$index % count($piePalette)],
        'path' => $buildPiePath(60.0, 60.0, 48.0, $runningAngle, $endAngle),
    ];

    $runningAngle = $endAngle;
}

if ($pieSlicesRevenue === []) {
    $pieSlicesRevenue = [];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <style>
        @page { margin: 16mm; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, Helvetica, sans-serif; color:#0f172a; background:#fff; }

        .banner { background:#e6efe8; padding:18px 20px; border-bottom:6px solid #6b8a78; }
        .banner-inner { max-width:1100px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; }
        .brand { font-weight:700; color:#274c3e; }
        .report-title { text-align:center; flex:1; font-weight:700; letter-spacing:.08em; color:#ffffff; background:#6b8a78; padding:8px 12px; border-radius:4px; font-size:18px; margin:0 24px; }

        .meta-row { max-width:1100px; margin:18px auto 10px; display:flex; gap:12px; align-items:flex-start; }
        .meta-left { flex:1; }
        .meta-right { min-width:260px; text-align:right; color:#475569; font-size:12px; }

        .exec { max-width:1100px; margin:8px auto 16px; padding:12px 14px; background:#f3f7f3; border-radius:6px; color:#425a4d; font-size:13px; }

        .grid-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px; max-width:1100px; margin: 12px auto; }
        .card { background:#fff; border:1px solid #e6efe8; padding:12px; border-radius:8px; }
        .card h3 { margin:0 0 8px; font-size:14px; color:#274c3e; }

        .summary-list { list-style:none; padding:0; margin:0; }
        .summary-list li { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #eef5ee; font-size:13px; }

        .chart-placeholder { height:120px; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:12px; border-radius:6px; background:linear-gradient(180deg,#f8faf8,#fff); }

        .tables { max-width:1100px; margin:12px auto; display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
        .small-table { border-collapse:collapse; width:100%; }
        .small-table th, .small-table td { padding:8px 10px; border-bottom:1px solid #eef5ee; font-size:12px; text-align:left; }
        .section-heading { font-size:12px; text-transform:uppercase; color:#274c3e; font-weight:700; margin-bottom:8px; }

        .footer { max-width:1100px; margin:18px auto 0; color:#64748b; font-size:11px; border-top:1px solid #eef5ee; padding-top:10px; display:flex; justify-content:space-between; }

        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body>
    <div class="banner">
        <div class="banner-inner">
            <div class="brand"><?= $company ?></div>
            <div class="report-title">DAILY REPORT</div>
            <div style="width:180px;text-align:right;color:#274c3e;font-size:13px;">Date: <?= $date ?></div>
        </div>
    </div>

    <div class="meta-row">
        <div class="meta-left">
            <strong>Employee Name:</strong> <?= $employee ?><br>
            <strong>Department:</strong> <?= $department ?>
        </div>
    </div>

    <?php if (!empty($data['executive_summary'] ?? null)): ?>
        <div class="exec"><?= e($data['executive_summary']) ?></div>
    <?php endif; ?>

    <div class="grid-3">
        <div class="card">
            <h3>Financial Summary</h3>
            <ul class="summary-list">
                <li><span>Revenue</span><span><?= money($financial['revenue'] ?? ($financial['total_revenue'] ?? '')) ?></span></li>
                <li><span>Expenses</span><span><?= money($financial['expenses'] ?? '') ?></span></li>
                <li><span>Net Income</span><span><?= money($financial['net_income'] ?? '') ?></span></li>
                <li><span>Accounts Receivable</span><span><?= money($financial['accounts_receivable'] ?? '') ?></span></li>
            </ul>
        </div>

        <div class="card">
            <h3>Product Sold</h3>
            <div class="chart-placeholder">
                <?php if ($products !== []): ?>
                    <div style="width:100%;">
                        <?php foreach ($products as $p): ?>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12px;">
                                <div style="width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['name'] ?? $p[0] ?? '-') ?></div>
                                <div style="flex:1;height:10px;background:#eef6f1;border-radius:8px;overflow:hidden;">
                                    <div style="width:<?= (int)(($p['qty'] ?? ($p[1] ?? 0)) / max(1, max(array_column($products, 'qty') ?: [1])) * 100) ?>%;height:100%;background:#6b8a78;border-radius:8px;"></div>
                                </div>
                                <div style="width:54px;text-align:right;"><?= e(number_format((int)($p['qty'] ?? ($p[1] ?? 0)))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div>No product data available</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>Revenue</h3>
            <div class="chart-placeholder">
                <?php if ($pieSlicesRevenue !== []): ?>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <svg viewBox="0 0 120 120" width="120" height="120" role="img" aria-label="Revenue distribution">
                            <?php foreach ($pieSlicesRevenue as $slice): ?>
                                <path d="<?= e($slice['path']) ?>" fill="<?= e($slice['color']) ?>"></path>
                            <?php endforeach; ?>
                        </svg>
                        <div style="flex:1;">
                            <?php foreach ($pieSlicesRevenue as $slice): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:13px;">
                                    <div style="display:flex;gap:8px;align-items:center;"><span style="width:12px;height:12px;background:<?= e($slice['color']) ?>;display:inline-block;border-radius:2px;"></span><span><?= e($slice['label']) ?></span></div>
                                    <div style="color:#475569"><?= money($slice['amount']) ?> <span style="color:#64748b;font-size:12px;margin-left:8px;">(<?= e((string)$slice['share']) ?>%)</span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($revenue !== []): ?>
                    <div style="width:100%;">
                        <?php $maxR = max(array_map(function($r){return (float)($r['amount'] ?? $r[1] ?? 0);}, $revenue) ?: [1]); ?>
                        <?php foreach ($revenue as $r): $amt = (float)($r['amount'] ?? $r[1] ?? 0); ?>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12px;">
                                <div style="width:110px;overflow:hidden;white-space:nowrap;"><?= e($r['label'] ?? $r[0] ?? '-') ?></div>
                                <div style="flex:1;height:10px;background:#f1f5f4;border-radius:8px;overflow:hidden;">
                                    <div style="width:<?= (int)(($amt / max(1, $maxR)) * 100) ?>%;height:100%;background:#2563eb;border-radius:8px;"></div>
                                </div>
                                <div style="width:80px;text-align:right;"><?= money($amt) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div>No revenue data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tables">
        <div class="card">
            <div class="section-heading">Financial Summary</div>
            <table class="small-table">
                <tbody>
                <tr><th>Item</th><th>Amount</th></tr>
                <?php foreach ($financial as $k => $v): if (in_array($k, ['revenue','expenses','net_income','accounts_receivable'])) continue; ?>
                    <tr><td><?= e(ucwords(str_replace('_',' ',$k))) ?></td><td><?= money($v) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-heading">Sales Performance</div>
            <table class="small-table">
                <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?= e($row['product'] ?? $row[0] ?? '-') ?></td>
                        <td><?= e((string)($row['units'] ?? $row[1] ?? '')) ?></td>
                        <td><?= money($row['revenue'] ?? $row[2] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-heading">Accounts Receivable</div>
            <table class="small-table">
                <thead><tr><th>Customer</th><th>Invoice</th><th>Amount</th><th>Due</th></tr></thead>
                <tbody>
                <?php foreach ($accounts as $row): ?>
                    <tr>
                        <td><?= e($row['customer'] ?? $row[0] ?? '-') ?></td>
                        <td><?= e($row['invoice'] ?? $row[1] ?? '') ?></td>
                        <td><?= money($row['amount'] ?? $row[2] ?? '') ?></td>
                        <td><?= e($row['due'] ?? $row[3] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-heading">Expense Breakdown</div>
            <table class="small-table">
                <thead><tr><th>Category</th><th>Amount</th></tr></thead>
                <tbody>
                <?php foreach ($expenses as $row): ?>
                    <tr>
                        <td><?= e($row['category'] ?? $row[0] ?? '-') ?></td>
                        <td><?= money($row['amount'] ?? $row[1] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-heading">Inventory Status</div>
            <table class="small-table">
                <thead><tr><th>Product</th><th>Quantity</th></tr></thead>
                <tbody>
                <?php foreach ($inventory as $row): ?>
                    <tr>
                        <td><?= e($row['product'] ?? $row[0] ?? '-') ?></td>
                        <td><?= e((string)($row['quantity'] ?? $row[1] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="section-heading">Other Highlights</div>
            <div style="font-size:13px;color:#475569;">
                <?php if ($highlights !== []): ?>
                    <ul style="margin:0;padding-left:18px;">
                        <?php foreach ($highlights as $h): ?><li><?= e((string)$h) ?></li><?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div>No highlights provided.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>For questions, contact <?= e($data['contact_name'] ?? $report['author'] ?? '-') ?></div>
        <div><?= $company ?> • Generated <?= $date ?></div>
    </div>

<?php if ($autoPrint): ?>
    <script>window.addEventListener('load', function(){ window.print(); });</script>
<?php endif; ?>
</body>
</html>
