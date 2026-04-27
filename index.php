<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Database;
use App\Repositories\ExcelDataRepository;
use App\Repositories\UploadRepository;
use App\Services\ExcelImportService;
use App\Services\PdfService;

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
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f7fb;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }
        .panel {
            max-width: 640px;
            width: 100%;
            padding: 28px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid #dbe3ef;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        }
        .label {
            display: inline-flex;
            margin-bottom: 14px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.1;
        }
        p {
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="panel">
        <div class="label">{$appName}</div>
        <h1>{$safeTitle}</h1>
        <p>{$safeMessage}</p>
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

function build_import_report_data(UploadRepository $uploadRepository, ExcelDataRepository $excelDataRepository, int $uploadId): array
{
    $upload = $uploadRepository->findById($uploadId);

    if (!$upload) {
        throw new RuntimeException('The imported file could not be found.');
    }

    $rows = $excelDataRepository->findAllByUploadId($uploadId);
    $summary = is_array($upload['summary'] ?? null) ? $upload['summary'] : [];
    $sheets = is_array($summary['sheets'] ?? null) ? $summary['sheets'] : [];
    $columnMap = is_array($summary['column_map'] ?? null) ? $summary['column_map'] : [];

    if ($columnMap === [] && !empty($rows[0]['column_data']) && is_array($rows[0]['column_data'])) {
        foreach (array_keys($rows[0]['column_data']) as $key) {
            $columnMap[(string) $key] = ucfirst(str_replace('_', ' ', (string) $key));
        }
    }

    if ($sheets === []) {
        $sheetGroups = [];

        foreach ($rows as $row) {
            $sheetName = (string) ($row['sheet_name'] ?? 'Sheet 1');
            $sheetGroups[$sheetName] = ($sheetGroups[$sheetName] ?? 0) + 1;
        }

        foreach ($sheetGroups as $sheetName => $rowCount) {
            $sheets[] = [
                'sheet_name' => $sheetName,
                'row_count' => $rowCount,
                'headers' => array_values($columnMap),
            ];
        }
    }

    $sheetLabels = [];
    $sheetCounts = [];

    foreach ($sheets as $sheet) {
        $sheetLabels[] = (string) ($sheet['sheet_name'] ?? 'Sheet');
        $sheetCounts[] = (int) ($sheet['row_count'] ?? 0);
    }

    $columnLabels = array_values($columnMap);
    $columnCounts = [];

    foreach (array_keys($columnMap) as $columnKey) {
        $count = 0;

        foreach ($rows as $row) {
            $value = $row['column_data'][$columnKey] ?? null;

            if ($value !== null && $value !== '') {
                $count++;
            }
        }

        $columnCounts[] = $count;
    }

    $sheetCount = max(1, count($sheetLabels));
    $rowCount = count($rows);
    $topSheetIndex = $sheetCounts === [] ? 0 : array_search(max($sheetCounts), $sheetCounts, true);
    $topSheetName = $sheetLabels[$topSheetIndex] ?? 'Sheet';
    $averageRows = round($rowCount / $sheetCount, 1);
    $filledCells = 0;
    $totalCells = max(1, $rowCount * max(1, count($columnLabels)));

    foreach ($columnCounts as $count) {
        $filledCells += $count;
    }

    return [
        'upload' => $upload,
        'rows' => $rows,
        'columns' => array_map(
            static fn (string $label, string $key): array => ['key' => $key, 'label' => $label],
            array_values($columnMap),
            array_keys($columnMap)
        ),
        'summary' => [
            'row_count' => $rowCount,
            'sheet_count' => count($sheetLabels),
            'column_count' => count($columnLabels),
            'file_name' => $upload['original_filename'] ?? $upload['filename'] ?? 'Imported file',
            'upload_date' => $upload['upload_date'] ?? null,
        ],
        'chart_data' => [
            'sheet_labels' => $sheetLabels,
            'sheet_counts' => $sheetCounts,
            'column_labels' => $columnLabels,
            'column_counts' => $columnCounts,
        ],
        'insights' => [
            [
                'title' => 'Largest sheet',
                'value' => $topSheetName,
                'note' => 'Largest sheet by row volume',
            ],
            [
                'title' => 'Average rows per sheet',
                'value' => (string) $averageRows,
                'note' => 'Across all parsed sheets',
            ],
            [
                'title' => 'Cell completeness',
                'value' => round(($filledCells / $totalCells) * 100) . '%',
                'note' => 'Filled cells across mapped columns',
            ],
        ],
    ];
}

function build_export_report_payload(array $reportData, int $uploadId, bool $autoPrint): array
{
    return [
        'reportData' => $reportData,
        'report' => [
            'id' => $uploadId,
            'report_title' => $reportData['summary']['file_name'] . ' Insights',
            'report_status' => 'generated',
            'created_at' => $reportData['summary']['upload_date'],
        ],
        'upload' => $reportData['upload'],
        'summary' => $reportData['summary'],
        'columns' => $reportData['columns'],
        'rows' => $reportData['rows'],
        'metrics' => [
            'row_count' => $reportData['summary']['row_count'],
            'column_count' => $reportData['summary']['column_count'],
            'sheet_count' => $reportData['summary']['sheet_count'],
        ],
        'autoPrint' => $autoPrint,
    ];
}

try {
    $pdo = Database::connection();
    $uploadRepository = new UploadRepository($pdo);
    $excelDataRepository = new ExcelDataRepository($pdo);
    $importService = new ExcelImportService($uploadRepository, $excelDataRepository);
    $pdfService = new PdfService();
} catch (Throwable $throwable) {
    render_error_page('Database unavailable', $throwable->getMessage(), 500);
}

$requestedUploadId = (int) request_input('upload_id', 0);

if ($requestedUploadId > 0 && request_method() === 'GET' && ($action = strtolower((string) request_input('action', ''))) === 'print-report') {
    try {
        $reportData = build_import_report_data($uploadRepository, $excelDataRepository, $requestedUploadId);
        $exportData = build_export_report_payload($reportData, $requestedUploadId, true);

        echo view('reports/print', $exportData, false);
        exit;
    } catch (Throwable $throwable) {
        render_error_page('Unable to open print view', $throwable->getMessage(), 422);
    }
}

if ($requestedUploadId > 0 && request_method() === 'GET' && (strtolower((string) request_input('action', ''))) === 'download-pdf') {
    try {
        $reportData = build_import_report_data($uploadRepository, $excelDataRepository, $requestedUploadId);
        $exportData = build_export_report_payload($reportData, $requestedUploadId, false);

        $pdfService->downloadReport($exportData);
    } catch (Throwable $throwable) {
        render_error_page('Unable to export PDF', $throwable->getMessage(), 422);
    }
}

if (request_method() === 'POST') {
    $uploadPath = null;

    try {
        if (!isset($_FILES['excel_file'])) {
            throw new RuntimeException('Choose an .xlsx file first.');
        }

        $file = $_FILES['excel_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        $originalFilename = (string) ($file['name'] ?? 'workbook.xlsx');

        if (strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION)) !== 'xlsx') {
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
        $message = sprintf('Imported %d rows from %s.', (int) ($summary['row_count'] ?? 0), $originalFilename);
        $reportData = build_import_report_data($uploadRepository, $excelDataRepository, $uploadId);
        $reportHtml = view('partials/report-section', ['reportData' => $reportData], false);

        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'message' => $message,
                'file_name' => $originalFilename,
                'summary' => [
                    'row_count' => (int) ($summary['row_count'] ?? 0),
                    'sheet_count' => (int) ($summary['sheet_count'] ?? 0),
                    'column_count' => (int) ($summary['column_count'] ?? 0),
                ],
                'upload_id' => $uploadId,
                'report_html' => $reportHtml,
                'report_data' => $reportData,
            ]);
        }

        flash('success', $message);
        redirect_to(asset_url('index.php?upload_id=' . $uploadId));
    } catch (Throwable $throwable) {
        if (is_array($uploadPath) && isset($uploadPath['absolute_path']) && is_file($uploadPath['absolute_path'])) {
            @unlink($uploadPath['absolute_path']);
        }

        if (is_ajax_request()) {
            json_response([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        flash('error', $throwable->getMessage());
        redirect_to(asset_url('index.php'));
    }
}

$initialReportHtml = '';
$initialReportData = null;

if ($requestedUploadId > 0 && request_method() === 'GET') {
    try {
        $initialReportData = build_import_report_data($uploadRepository, $excelDataRepository, $requestedUploadId);
        $initialReportHtml = view('partials/report-section', ['reportData' => $initialReportData], false);
    } catch (Throwable) {
        $initialReportHtml = '';
        $initialReportData = null;
    }
}

echo view('upload', [
    'pageTitle' => config('app.name'),
    'pageSubtitle' => 'Click or drag an .xlsx file to import it.',
    'currentPage' => 'upload',
    'uploadMaxMb' => (int) config('app.upload_max_mb', 20),
    'reportHtml' => $initialReportHtml,
    'reportData' => $initialReportData,
]);