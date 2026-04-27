<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ExcelDataRepository;
use App\Repositories\ReportRepository;
use App\Repositories\UploadRepository;
use RuntimeException;

final class ReportService
{
    public function __construct(
        private readonly ReportRepository $reportRepository,
        private readonly UploadRepository $uploadRepository,
        private readonly ExcelDataRepository $excelDataRepository
    ) {
    }

    public function createFromUpload(int $uploadId, string $reportTitle): array
    {
        $upload = $this->uploadRepository->findById($uploadId);

        if (!$upload) {
            throw new RuntimeException('The selected upload could not be found.');
        }

        $rowCount = $this->excelDataRepository->countByUploadId($uploadId);

        if ($rowCount === 0) {
            throw new RuntimeException('There is no imported data available for this upload.');
        }

        $summary = $this->buildSummaryFromUpload($upload, $rowCount, $reportTitle);
        $reportId = $this->reportRepository->create($uploadId, $reportTitle, $summary);

        return $this->reportRepository->findById($reportId) ?? [];
    }

    public function getReportData(int $reportId, int $page = 1, int $perPage = 25): array
    {
        $report = $this->reportRepository->findById($reportId);

        if (!$report) {
            throw new RuntimeException('Report not found.');
        }

        $upload = $report['upload'];
        $summary = $report['summary'] ?? [];
        $pagination = $this->excelDataRepository->findByUploadId($upload['id'], $page, $perPage);
        $columns = $this->resolveColumns($summary, $pagination['data']);
        $allRows = $this->excelDataRepository->findAllByUploadId($upload['id']);
        $visualData = build_report_visual_data($upload, $summary, $allRows, $columns);

        return [
            'report' => $report,
            'upload' => $upload,
            'summary' => $visualData['summary'],
            'columns' => $columns,
            'rows' => $pagination['data'],
            'pagination' => $pagination,
            'chart_data' => $visualData['chart_data'],
            'insights' => $visualData['insights'],
            'metrics' => [
                'row_count' => (int) ($upload['row_count'] ?? 0),
                'column_count' => (int) ($upload['column_count'] ?? count($columns)),
                'sheet_count' => (int) ($upload['sheet_count'] ?? 0),
            ],
        ];
    }

    public function getPrintableReportData(int $reportId): array
    {
        $report = $this->reportRepository->findById($reportId);

        if (!$report) {
            throw new RuntimeException('Report not found.');
        }

        $upload = $report['upload'];
        $summary = $report['summary'] ?? [];
        $rows = $this->excelDataRepository->findAllByUploadId($upload['id']);
        $columns = $this->resolveColumns($summary, $rows);
        $visualData = build_report_visual_data($upload, $summary, $rows, $columns);

        return [
            'report' => $report,
            'upload' => $upload,
            'summary' => $visualData['summary'],
            'columns' => $columns,
            'rows' => $rows,
            'chart_data' => $visualData['chart_data'],
            'insights' => $visualData['insights'],
            'metrics' => [
                'row_count' => (int) ($upload['row_count'] ?? count($rows)),
                'column_count' => (int) ($upload['column_count'] ?? count($columns)),
                'sheet_count' => (int) ($upload['sheet_count'] ?? 0),
            ],
        ];
    }

    public function buildSummaryFromUpload(array $upload, int $rowCount, string $reportTitle): array
    {
        $uploadSummary = is_array($upload['summary'] ?? null) ? $upload['summary'] : [];
        $columnMap = $uploadSummary['column_map'] ?? [];

        return [
            'report_title' => $reportTitle,
            'upload_id' => (int) ($upload['id'] ?? 0),
            'source_filename' => $upload['original_filename'] ?? $upload['filename'] ?? '',
            'stored_filename' => $upload['filename'] ?? '',
            'upload_date' => $upload['upload_date'] ?? null,
            'row_count' => $rowCount,
            'column_count' => count($columnMap),
            'sheet_count' => (int) ($upload['sheet_count'] ?? 0),
            'headers' => array_values($columnMap),
            'column_map' => $columnMap,
        ];
    }

    private function resolveColumns(array $summary, array $rows): array
    {
        $columnMap = $summary['column_map'] ?? [];

        if (is_array($columnMap) && $columnMap !== []) {
            return array_map(
                static fn (string $label, string $key): array => [
                    'key' => $key,
                    'label' => $label,
                ],
                $columnMap,
                array_keys($columnMap)
            );
        }

        $firstRow = $rows[0]['column_data'] ?? [];

        if (!is_array($firstRow) || $firstRow === []) {
            return [];
        }

        $columns = [];

        foreach (array_keys($firstRow) as $key) {
            $columns[] = [
                'key' => (string) $key,
                'label' => ucfirst(str_replace('_', ' ', (string) $key)),
            ];
        }

        return $columns;
    }
}