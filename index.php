<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Database;
use App\Repositories\ExcelDataRepository;
use App\Repositories\ReportRepository;
use App\Repositories\UploadRepository;
use App\Services\ExcelImportService;
use App\Services\PdfService;
use App\Services\ReportService;
use RuntimeException;
use Throwable;

function render_error_page(string $title, string $message, int $status = 500): never
{
    http_response_code($status);
    $safeTitle = e($title);
    $safeMessage = e($message);
    $appName = e((string) config('app.name'));

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle} | {$appName}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #f5f7fb, #eef4ff);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        .card {
            max-width: 760px;
            width: 100%;
            padding: 32px;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.1);
            border: 1px solid #e4eaf3;
        }
        .eyebrow {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(47, 111, 237, 0.1);
            color: #2f6fed;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 16px 0 12px;
            font-size: 34px;
            line-height: 1.06;
        }
        p {
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }
        a {
            display: inline-flex;
            align-items: center;
            margin-top: 20px;
            color: #2f6fed;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="eyebrow">{$appName}</div>
    <h1>{$safeTitle}</h1>
    <p>{$safeMessage}</p>
    <a href="index.php?page=dashboard">Back to dashboard</a>
</div>
</body>
</html>
HTML;

    exit;
}

function is_ajax_request(): bool
{
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function ensure_directory(string $directory): void
{
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

function normalize_report_title(string $filename): string
{
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $baseName = str_replace(['-', '_'], ' ', $baseName);
    $baseName = preg_replace('/\s+/', ' ', $baseName) ?? $baseName;

    return trim(ucwords(trim($baseName))) ?: 'Generated Report';
}

function build_upload_path(string $originalFilename): array
{
    $safeBaseName = slugify(pathinfo($originalFilename, PATHINFO_FILENAME));
    $dateSegment = date('Y/m');
    $uniqueSuffix = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $storedFilename = $safeBaseName . '-' . $uniqueSuffix . '.xlsx';
    $relativeDirectory = 'storage/uploads/' . $dateSegment;
    $relativePath = $relativeDirectory . '/' . $storedFilename;
    $absoluteDirectory = base_path($relativeDirectory);

    ensure_directory($absoluteDirectory);

    return [
        'stored_filename' => $storedFilename,
        'relative_path' => $relativePath,
        'absolute_path' => base_path($relativePath),
    ];
}

function build_topbar_actions(string $page): string
{
    return match ($page) {
        'dashboard' => implode(' ', [
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=history')) . '"><i class="bi bi-clock-history me-1"></i> History</a>',
            '<a class="btn btn-primary rounded-pill" href="' . e(asset_url('index.php?page=upload')) . '"><i class="bi bi-cloud-arrow-up me-1"></i> Upload Excel</a>',
        ]),
        'upload' => implode(' ', [
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=dashboard')) . '"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>',
            '<a class="btn btn-primary rounded-pill" href="' . e(asset_url('index.php?page=reports')) . '"><i class="bi bi-file-earmark-text me-1"></i> Reports</a>',
        ]),
        'reports' => implode(' ', [
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=history')) . '"><i class="bi bi-clock-history me-1"></i> History</a>',
            '<a class="btn btn-primary rounded-pill" href="' . e(asset_url('index.php?page=upload')) . '"><i class="bi bi-cloud-arrow-up me-1"></i> Upload Excel</a>',
        ]),
        'history' => implode(' ', [
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=reports')) . '"><i class="bi bi-file-earmark-text me-1"></i> Reports</a>',
            '<a class="btn btn-primary rounded-pill" href="' . e(asset_url('index.php?page=upload')) . '"><i class="bi bi-cloud-arrow-up me-1"></i> Upload Excel</a>',
        ]),
        'view-report' => implode(' ', [
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=history')) . '"><i class="bi bi-arrow-left me-1"></i> History</a>',
            '<a class="btn btn-outline-secondary rounded-pill" href="' . e(asset_url('index.php?page=print-report&id=' . (int) ($_GET['id'] ?? 0))) . '" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>',
            '<a class="btn btn-primary rounded-pill" href="' . e(asset_url('index.php?action=download-pdf&id=' . (int) ($_GET['id'] ?? 0))) . '"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>',
        ]),
        default => '',
    };
}

try {
    $pdo = Database::connection();
    $uploadRepository = new UploadRepository($pdo);
    $excelDataRepository = new ExcelDataRepository($pdo);
    $reportRepository = new ReportRepository($pdo);
    $importService = new ExcelImportService($uploadRepository, $excelDataRepository);
    $reportService = new ReportService($reportRepository, $uploadRepository, $excelDataRepository);
    $pdfService = new PdfService();
} catch (Throwable $throwable) {
    render_error_page('Database unavailable', $throwable->getMessage(), 500);
}

$action = strtolower((string) request_input('action', ''));
$page = strtolower((string) request_input('page', 'dashboard'));

if ($action === 'upload' && request_method() === 'POST') {
    try {
        if (!isset($_FILES['excel_file'])) {
            throw new RuntimeException('Please choose an Excel file to import.');
        }

        $file = $_FILES['excel_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        $originalFilename = (string) ($file['name'] ?? 'workbook.xlsx');
        $extension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));

        if ($extension !== 'xlsx') {
            throw new RuntimeException('Only .xlsx files are supported.');
        }

        $maxBytes = (int) config('app.upload_max_mb', 20) * 1024 * 1024;

        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('The file exceeds the maximum allowed size of ' . config('app.upload_max_mb', 20) . ' MB.');
        }

        $uploadPath = build_upload_path($originalFilename);

        if (!move_uploaded_file((string) $file['tmp_name'], $uploadPath['absolute_path'])) {
            throw new RuntimeException('The uploaded file could not be saved.');
        }

        $uploadId = $uploadRepository->create([
            'filename' => $uploadPath['stored_filename'],
            'original_filename' => $originalFilename,
            'file_path' => $uploadPath['relative_path'],
            'file_size' => (int) ($file['size'] ?? 0),
            'mime_type' => (string) ($file['type'] ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'status' => 'uploaded',
        ]);

        $summary = $importService->import($uploadId, $uploadPath['absolute_path']);
        $previewRows = $summary['preview_rows'] ?? [];
        $columns = [];

        foreach (($summary['column_map'] ?? []) as $key => $label) {
            $columns[] = [
                'key' => (string) $key,
                'label' => (string) $label,
            ];
        }

        json_response([
            'success' => true,
            'message' => 'Workbook imported successfully.',
            'upload_id' => $uploadId,
            'suggested_title' => normalize_report_title($originalFilename),
            'summary' => [
                'sheet_count' => (int) ($summary['sheet_count'] ?? 0),
                'row_count' => (int) ($summary['row_count'] ?? 0),
                'column_count' => (int) ($summary['column_count'] ?? 0),
                'sheet_name' => $previewRows[0]['sheet_name'] ?? 'Workbook',
                'preview_count' => count($previewRows),
            ],
            'preview' => [
                'columns' => $columns,
                'rows' => $previewRows,
                'summary' => [
                    'sheet_count' => (int) ($summary['sheet_count'] ?? 0),
                    'row_count' => (int) ($summary['row_count'] ?? 0),
                    'column_count' => (int) ($summary['column_count'] ?? 0),
                    'sheet_name' => $previewRows[0]['sheet_name'] ?? 'Workbook',
                    'preview_count' => count($previewRows),
                ],
            ],
        ]);
    } catch (Throwable $throwable) {
        json_response([
            'success' => false,
            'message' => $throwable->getMessage(),
        ], 422);
    }
}

if ($action === 'generate-report' && request_method() === 'POST') {
    try {
        $uploadId = (int) request_input('upload_id', 0);
        $reportTitle = trim((string) request_input('report_title', ''));

        if ($uploadId <= 0) {
            throw new RuntimeException('Select a processed upload before generating a report.');
        }

        if ($reportTitle === '') {
            throw new RuntimeException('Please provide a report title.');
        }

        $report = $reportService->createFromUpload($uploadId, $reportTitle);

        if ($report === []) {
            throw new RuntimeException('The report could not be generated.');
        }

        $reportId = (int) $report['id'];
        $reportUrl = asset_url('index.php?page=view-report&id=' . $reportId);

        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'message' => 'Report generated successfully.',
                'report_id' => $reportId,
                'report_url' => $reportUrl,
            ]);
        }

        flash('success', 'Report generated successfully.');
        redirect_to($reportUrl);
    } catch (Throwable $throwable) {
        if (is_ajax_request()) {
            json_response([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        flash('error', $throwable->getMessage());
        redirect_to(asset_url('index.php?page=reports'));
    }
}

if ($action === 'delete-report' && request_method() === 'POST') {
    try {
        $reportId = (int) request_input('report_id', 0);

        if ($reportId <= 0) {
            throw new RuntimeException('A report identifier is required.');
        }

        $deleted = $reportRepository->delete($reportId);

        if (!$deleted) {
            throw new RuntimeException('The report could not be deleted.');
        }

        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'message' => 'Report deleted successfully.',
            ]);
        }

        flash('success', 'Report deleted successfully.');
        redirect_to(asset_url('index.php?page=history'));
    } catch (Throwable $throwable) {
        if (is_ajax_request()) {
            json_response([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        flash('error', $throwable->getMessage());
        redirect_to(asset_url('index.php?page=history'));
    }
}

if ($action === 'download-pdf') {
    $reportId = (int) request_input('id', 0);

    if ($reportId <= 0) {
        render_error_page('Report not found', 'A valid report identifier is required.', 404);
    }

    try {
        $reportData = $reportService->getPrintableReportData($reportId);
        $pdfService->downloadReport($reportData);
    } catch (Throwable $throwable) {
        render_error_page('Unable to export PDF', $throwable->getMessage(), 422);
    }
}

if ($page === 'print-report') {
    $reportId = (int) request_input('id', 0);

    if ($reportId <= 0) {
        render_error_page('Report not found', 'A valid report identifier is required.', 404);
    }

    try {
        $reportData = $reportService->getPrintableReportData($reportId);
        echo view('reports/print', array_merge($reportData, ['autoPrint' => true]), false);
        exit;
    } catch (Throwable $throwable) {
        render_error_page('Unable to open print view', $throwable->getMessage(), 422);
    }
}

$currentPage = 'dashboard';
$pageTitle = config('app.name');
$pageSubtitle = 'Upload Excel workbooks, map columns dynamically, and generate printable reports.';
$topbarActions = build_topbar_actions('dashboard');
$contentView = 'dashboard';
$contentData = [];

try {
    switch ($page) {
        case 'upload':
            $currentPage = 'upload';
            $pageTitle = 'Upload Excel';
            $pageSubtitle = 'Import a .xlsx workbook, preview the parsed rows, and create a report from the same dataset.';
            $topbarActions = build_topbar_actions('upload');
            $contentView = 'upload';
            $contentData = [];
            break;

        case 'reports':
            $currentPage = 'reports';
            $pageTitle = 'Reports';
            $pageSubtitle = 'Generate reports from previously imported workbooks and review recent report snapshots.';
            $topbarActions = build_topbar_actions('reports');
            $contentView = 'reports/index';
            $contentData = [
                'processedUploads' => $uploadRepository->allProcessed(20),
                'reports' => $reportRepository->recent(8),
            ];
            break;

        case 'history':
            $currentPage = 'history';
            $pageTitle = 'History';
            $pageSubtitle = 'Browse every generated report with view, PDF export, and delete actions.';
            $topbarActions = build_topbar_actions('history');
            $pageNumber = max(1, (int) request_input('p', 1));
            $perPage = (int) config('app.page_size', 25);
            $history = $reportRepository->paginate($pageNumber, $perPage);
            $contentView = 'history';
            $contentData = [
                'reports' => $history['data'],
                'pagination' => $history,
            ];
            break;

        case 'view-report':
            $reportId = (int) request_input('id', 0);

            if ($reportId <= 0) {
                render_error_page('Report not found', 'A valid report identifier is required.', 404);
            }

            $currentPage = 'reports';
            $pageTitle = 'Report Viewer';
            $pageSubtitle = 'Preview the structured report, print it, or export it as a PDF document.';
            $topbarActions = build_topbar_actions('view-report');
            $pageNumber = max(1, (int) request_input('p', 1));
            $perPage = (int) config('app.page_size', 25);
            $contentView = 'reports/view';
            $contentData = $reportService->getReportData($reportId, $pageNumber, $perPage);
            $contentData['pageTitle'] = $pageTitle;
            $contentData['pageSubtitle'] = $pageSubtitle;
            break;

        case 'dashboard':
        default:
            $currentPage = 'dashboard';
            $pageTitle = 'Dashboard';
            $pageSubtitle = 'Track workbook imports, generated reports, and the latest processed files at a glance.';
            $topbarActions = build_topbar_actions('dashboard');
            $contentView = 'dashboard';
            $contentData = [
                'stats' => [
                    'uploads' => $uploadRepository->count(),
                    'reports' => $reportRepository->count(),
                    'rows' => $excelDataRepository->countAll(),
                    'latest_upload' => ($uploadRepository->recent(1)[0]['original_filename'] ?? 'No uploads yet'),
                ],
                'recentUploads' => $uploadRepository->recent(5),
                'recentReports' => $reportRepository->recent(5),
            ];
            break;
    }

    echo view($contentView, array_merge($contentData, [
        'currentPage' => $currentPage,
        'pageTitle' => $pageTitle,
        'pageSubtitle' => $pageSubtitle,
        'topbarActions' => $topbarActions,
    ]));
} catch (Throwable $throwable) {
    render_error_page('Unable to render page', $throwable->getMessage(), 500);
}