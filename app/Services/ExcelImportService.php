<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\ExcelDataRepository;
use App\Repositories\UploadRepository;
use RuntimeException;
use Throwable;

final class ExcelImportService
{
    public function __construct(
        private readonly UploadRepository $uploadRepository,
        private readonly ExcelDataRepository $excelDataRepository
    ) {
    }

    public function import(int $uploadId, string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Uploaded Excel file could not be found.');
        }

        if (strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Only .xlsx files are supported.');
        }

        $readerFactoryClass = 'PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = call_user_func([$readerFactoryClass, 'createReaderForFile'], $filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $previewLimit = (int) config('app.preview_rows', 25);
        $pdo = Database::connection();

        $summary = [
            'sheet_count' => 0,
            'row_count' => 0,
            'column_count' => 0,
            'headers' => [],
            'column_map' => [],
            'sheets' => [],
            'intro_lines' => [],
        ];

        $columnMap = [];
        $columnTotals = [];
        $previewRows = [];
        $totalRows = 0;

        $pdo->beginTransaction();

        try {
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetSummary = $this->importWorksheet(
                    $uploadId,
                    $worksheet,
                    $columnMap,
                    $columnTotals,
                    $previewRows,
                    $previewLimit,
                    $totalRows
                );

                if ($sheetSummary['row_count'] > 0) {
                    $summary['sheet_count']++;
                }

                $summary['sheets'][] = $sheetSummary;

                if ($summary['intro_lines'] === [] && !empty($sheetSummary['intro_lines'])) {
                    $summary['intro_lines'] = $sheetSummary['intro_lines'];
                }
            }

            $summary['row_count'] = $totalRows;

            if ($summary['row_count'] === 0) {
                throw new RuntimeException('The workbook does not contain any importable rows.');
            }

            foreach ($columnMap as $key => $label) {
                if (!array_key_exists($key, $columnTotals)) {
                    $columnTotals[$key] = 0;
                }
            }

            $summary['column_map'] = $columnMap;
            $summary['headers'] = array_values($columnMap);
            $summary['column_count'] = count($columnMap);
            $summary['preview_rows'] = $previewRows;
            $summary['column_counts_map'] = $columnTotals;

            $this->uploadRepository->markProcessed(
                $uploadId,
                $summary,
                $summary['row_count'],
                $summary['column_count'],
                $summary['sheet_count']
            );

            $pdo->commit();

            return $summary;
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->uploadRepository->markFailed($uploadId, $throwable->getMessage());

            throw $throwable;
        }
    }

    private function importWorksheet(
        int $uploadId,
        $worksheet,
        array &$columnMap,
        array &$columnTotals,
        array &$previewRows,
        int $previewLimit,
        int &$globalRowCount
    ): array {
        $sheetName = $worksheet->getTitle();
        $headerRow = $this->detectHeaderRow($worksheet);
        $introLines = [];

        if ($headerRow === null) {
            return [
                'sheet_name' => $sheetName,
                'row_count' => 0,
                'headers' => [],
                'intro_lines' => $introLines,
            ];
        }

        if ($headerRow > 1) {
            for ($rowIndex = 1; $rowIndex < $headerRow; $rowIndex++) {
                $values = $this->extractRowValues($worksheet, $rowIndex);
                $parts = [];

                foreach ($values as $value) {
                    $text = trim((string) $value);

                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }

                if ($parts !== []) {
                    $introLines[] = implode(' ', $parts);
                }
            }
        }

        $rawHeaders = $this->extractRowValues($worksheet, $headerRow);
        $headers = $this->buildHeaderDefinitions($rawHeaders, $columnMap);
        $highestRow = $worksheet->getHighestDataRow();
        $batch = [];
        $sheetRowCount = 0;

        for ($rowIndex = $headerRow + 1; $rowIndex <= $highestRow; $rowIndex++) {
            $rowValues = $this->extractRowValues($worksheet, $rowIndex);

            if ($this->rowIsEmpty($rowValues)) {
                continue;
            }

            $rowData = [];

            foreach ($headers as $index => $header) {
                $normalizedValue = $this->normalizeCellValue($rowValues[$index] ?? null);
                $rowData[$header['key']] = $normalizedValue;

                if ($normalizedValue !== null && $normalizedValue !== '') {
                    $columnTotals[$header['key']] = ($columnTotals[$header['key']] ?? 0) + 1;
                }
            }

            $batch[] = [
                'sheet_name' => $sheetName,
                'row_index' => $rowIndex,
                'column_data' => $rowData,
            ];

            if (count($previewRows) < $previewLimit) {
                $previewRows[] = [
                    'sheet_name' => $sheetName,
                    'row_index' => $rowIndex,
                    'column_data' => $rowData,
                ];
            }

            $sheetRowCount++;
            $globalRowCount++;

            if (count($batch) >= 250) {
                $this->excelDataRepository->bulkInsert($uploadId, $batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->excelDataRepository->bulkInsert($uploadId, $batch);
        }

        return [
            'sheet_name' => $sheetName,
            'row_count' => $sheetRowCount,
            'headers' => array_values(array_map(static fn (array $header): string => $header['label'], $headers)),
            'intro_lines' => $introLines,
        ];
    }

    private function detectHeaderRow($worksheet): ?int
    {
        $highestRow = min((int) $worksheet->getHighestDataRow(), 10);
        $bestRow = null;
        $bestScore = 0;

        for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
            $values = $this->extractRowValues($worksheet, $rowIndex);
            $score = 0;

            foreach ($values as $value) {
                if (trim((string) $value) !== '') {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $rowIndex;
            }
        }

        return $bestScore > 0 ? $bestRow : null;
    }

    private function extractRowValues($worksheet, int $rowIndex): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();

        return $worksheet->rangeToArray(
            'A' . $rowIndex . ':' . $highestColumn . $rowIndex,
            null,
            true,
            true,
            false
        )[0] ?? [];
    }

    private function buildHeaderDefinitions(array $rawHeaders, array &$columnMap): array
    {
        $headers = [];
        $usedKeys = [];

        foreach ($rawHeaders as $index => $rawHeader) {
            $label = trim((string) $rawHeader);

            if ($label === '') {
                $label = 'Column ' . ($index + 1);
            }

            $baseKey = normalize_header_key($label, $index);
            $key = $baseKey;
            $counter = 2;

            while (isset($usedKeys[$key])) {
                $key = $baseKey . '_' . $counter;
                $counter++;
            }

            $usedKeys[$key] = true;

            if (!array_key_exists($key, $columnMap)) {
                $columnMap[$key] = $label;
            }

            $headers[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        if ($headers === []) {
            $headers[] = [
                'key' => 'column_1',
                'label' => 'Column 1',
            ];
            $columnMap['column_1'] = 'Column 1';
        }

        return $headers;
    }

    private function rowIsEmpty(array $rowValues): bool
    {
        foreach ($rowValues as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeCellValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }
}