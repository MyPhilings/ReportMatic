<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class PdfService
{
    public function downloadReport(array $reportData): never
    {
        if ($reportData === []) {
            throw new RuntimeException('Unable to generate the PDF document.');
        }

        $viewName = 'reports/print';

        if (!empty($reportData['layout']) && $reportData['layout'] === 'daily') {
            $viewName = 'reports/daily';
        } elseif (!empty($reportData['report']['template']) && $reportData['report']['template'] === 'daily') {
            $viewName = 'reports/daily';
        }

        $html = view($viewName, array_merge($reportData, ['autoPrint' => false]), false);

        $optionsClass = 'Dompdf\\Options';
        $dompdfClass = 'Dompdf\\Dompdf';

        $options = new $optionsClass();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new $dompdfClass($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $reportTitle = $reportData['report']['report_title'] ?? 'Report';
        $filename = slugify((string) $reportTitle) . '.pdf';

        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}