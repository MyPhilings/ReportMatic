<?php

$uploadMaxMb = (int) config('app.upload_max_mb', 20);
?>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <h5 class="section-title mb-2">Upload Excel workbook</h5>
                    <p class="text-secondary mb-0">Drop an .xlsx file to parse it, persist the rows, and build a structured preview.</p>
                </div>

                <form id="uploadForm" action="<?= e(asset_url('index.php?action=upload')) ?>" method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="upload-dropzone" data-dropzone>
                        <div class="upload-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="fw-semibold mb-1">Drag and drop your Excel file here</div>
                        <div class="text-secondary small mb-3">Only .xlsx files up to <?= e((string) $uploadMaxMb) ?> MB</div>
                        <input class="form-control form-control-lg visually-hidden" type="file" id="excelFile" name="excel_file" accept=".xlsx" required>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-trigger-file>
                                <i class="bi bi-folder2-open me-1"></i> Choose file
                            </button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4" data-upload-submit>
                                <span class="upload-submit-label"><i class="bi bi-cloud-arrow-up me-1"></i> Import data</span>
                                <span class="upload-submit-spinner d-none"><span class="spinner-border spinner-border-sm me-2"></span>Importing</span>
                            </button>
                        </div>
                    </div>

                    <div class="upload-file-chip mt-3 d-none" data-file-chip>
                        <i class="bi bi-file-earmark-excel me-2"></i>
                        <span data-file-name>Ready to upload</span>
                    </div>
                </form>

                <div class="upload-insight-card mt-4">
                    <div class="small text-uppercase fw-semibold text-secondary mb-2">Processing notes</div>
                    <ul class="mb-0 text-secondary small ps-3">
                        <li>Headers are detected dynamically even when columns vary.</li>
                        <li>Rows are stored in MySQL as JSON per record for flexible reporting.</li>
                        <li>Preview is generated immediately after import completes.</li>
                    </ul>
                </div>

                <div class="card border-0 shadow-sm mt-4 d-none" id="generateReportCard">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <h6 class="mb-1">Generate report</h6>
                            <p class="text-secondary small mb-0">Create a report snapshot from the uploaded workbook.</p>
                        </div>

                        <form id="generateReportForm" action="<?= e(asset_url('index.php?action=generate-report')) ?>" method="post">
                            <input type="hidden" name="upload_id" id="reportUploadId" value="">
                            <div class="mb-3">
                                <label class="form-label">Report title</label>
                                <input class="form-control" type="text" name="report_title" id="reportTitle" placeholder="e.g. Monthly Sales Summary" required>
                            </div>
                            <button type="submit" class="btn btn-dark rounded-pill px-4">
                                <span class="report-submit-label"><i class="bi bi-magic me-1"></i> Generate report</span>
                                <span class="report-submit-spinner d-none"><span class="spinner-border spinner-border-sm me-2"></span>Generating</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card section-card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                    <div>
                        <h5 class="section-title mb-1">Live preview</h5>
                        <p class="text-secondary mb-0">The first rows from the workbook will appear here after import.</p>
                    </div>
                    <div class="preview-meta d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill text-bg-light border" id="previewSheetLabel">No file loaded</span>
                        <span class="badge rounded-pill text-bg-light border" id="previewRowLabel">0 rows</span>
                    </div>
                </div>

                <div class="preview-summary-grid mb-4" id="uploadSummaryGrid">
                    <div class="summary-metric">
                        <div class="summary-value" id="summaryRows">0</div>
                        <div class="summary-label">Rows imported</div>
                    </div>
                    <div class="summary-metric">
                        <div class="summary-value" id="summarySheets">0</div>
                        <div class="summary-label">Sheets parsed</div>
                    </div>
                    <div class="summary-metric">
                        <div class="summary-value" id="summaryColumns">0</div>
                        <div class="summary-label">Columns mapped</div>
                    </div>
                </div>

                <div class="table-responsive preview-table-wrap">
                    <table class="table table-hover align-middle preview-table mb-0">
                        <thead>
                            <tr id="previewTableHead">
                                <th>Source sheet</th>
                                <th>Row #</th>
                                <th>Waiting for file</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state text-center py-5">
                                        <div class="empty-icon mb-3"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                                        <h6 class="mb-2">Preview will appear here</h6>
                                        <p class="text-secondary mb-0">Select an Excel file and import it to load the first rows into the dashboard.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>