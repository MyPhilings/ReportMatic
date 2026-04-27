<?php

$uploadMaxMb = (int) config('app.upload_max_mb', 20);
$reportHtml = $reportHtml ?? '';
?>
<section class="upload-page">
    <div class="upload-card">
        <div class="upload-copy">
            <div class="eyebrow"><?= e(config('app.name')) ?></div>
            <h1>Import Excel file</h1>
            <p>Click or drag an <strong>.xlsx</strong> file into the box below. The file will be parsed and stored in MySQL.</p>
        </div>

        <form id="uploadForm" class="upload-form" action="<?= e(asset_url('index.php')) ?>" method="post" enctype="multipart/form-data">
            <label class="dropzone" for="excelFile" data-dropzone>
                <span class="dropzone-icon">+</span>
                <span class="dropzone-title">Click or drag and drop</span>
                <span class="dropzone-hint">Only .xlsx files up to <?= e((string) $uploadMaxMb) ?> MB</span>
                <span class="dropzone-file" data-file-label>No file selected</span>
            </label>

            <input class="visually-hidden" type="file" id="excelFile" name="excel_file" accept=".xlsx" required>

            <div class="upload-actions">
                <button type="button" class="btn btn-light border" data-trigger-file>Choose file</button>
                <button type="submit" class="btn btn-primary" data-upload-button>
                    <span class="upload-button-label">Import file</span>
                    <span class="upload-button-loading d-none">Importing…</span>
                </button>
            </div>
        </form>

        <div id="uploadStatus" class="upload-status" aria-live="polite"></div>
    </div>
</section>

<section id="reportMount" class="report-mount <?= $reportHtml !== '' ? '' : 'd-none' ?>">
    <?= $reportHtml ?>
</section>