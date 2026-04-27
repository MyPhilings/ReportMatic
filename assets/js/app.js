(function () {
    'use strict';

    const config = window.ReportMatix || {};
    const appAlerts = document.getElementById('appAlerts');

    const uploadForm = document.getElementById('uploadForm');
    const generateReportForms = [
        document.getElementById('generateReportForm'),
        document.getElementById('reportBuilderForm'),
    ].filter(Boolean);

    const deleteButtons = Array.from(document.querySelectorAll('.js-delete-report'));
    const dropzone = document.querySelector('[data-dropzone]');
    const triggerFileButton = document.querySelector('[data-trigger-file]');
    const fileInput = document.getElementById('excelFile');
    const fileChip = document.querySelector('[data-file-chip]');
    const fileNameLabel = document.querySelector('[data-file-name]');
    const uploadSubmit = document.querySelector('[data-upload-submit]');
    const generateReportCard = document.getElementById('generateReportCard');
    const reportUploadId = document.getElementById('reportUploadId');
    const reportTitleInput = document.getElementById('reportTitle');
    const previewTableHead = document.getElementById('previewTableHead');
    const previewTableBody = document.getElementById('previewTableBody');
    const previewSheetLabel = document.getElementById('previewSheetLabel');
    const previewRowLabel = document.getElementById('previewRowLabel');
    const summaryRows = document.getElementById('summaryRows');
    const summarySheets = document.getElementById('summarySheets');
    const summaryColumns = document.getElementById('summaryColumns');

    const state = {
        currentColumns: [],
        currentRows: [],
    };

    function sanitizeText(value) {
        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value);
        }

        return String(value);
    }

    function defaultReportTitle(fileName) {
        return sanitizeText(fileName)
            .replace(/\.[^.]+$/, '')
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim() || 'New Report';
    }

    function showAlert(type, message) {
        if (!appAlerts) {
            return;
        }

        const alertType = type || 'info';
        const box = document.createElement('div');
        box.className = 'alert alert-' + alertType + ' alert-soft border-0 rounded-4 shadow-sm mb-3';
        box.innerHTML = '<div class="d-flex align-items-start justify-content-between gap-3"><div>' + sanitizeText(message) + '</div><button type="button" class="btn-close ms-2" aria-label="Close"></button></div>';

        box.querySelector('.btn-close').addEventListener('click', function () {
            box.remove();
        });

        appAlerts.innerHTML = '';
        appAlerts.appendChild(box);

        window.setTimeout(function () {
            if (box.isConnected) {
                box.remove();
            }
        }, 5000);
    }

    function setButtonLoading(button, isLoading, labels) {
        if (!button) {
            return;
        }

        const normalLabel = button.querySelector(labels.normal);
        const loadingLabel = button.querySelector(labels.loading);

        if (normalLabel && loadingLabel) {
            normalLabel.classList.toggle('d-none', isLoading);
            loadingLabel.classList.toggle('d-none', !isLoading);
        }

        button.disabled = isLoading;
    }

    function setUploadBusy(isBusy) {
        setButtonLoading(uploadSubmit, isBusy, {
            normal: '.upload-submit-label',
            loading: '.upload-submit-spinner',
        });
    }

    function setReportBusy(button, isBusy) {
        setButtonLoading(button, isBusy, {
            normal: '.report-submit-label',
            loading: '.report-submit-spinner',
        });
    }

    function renderMetrics(summary) {
        if (summaryRows) {
            summaryRows.textContent = sanitizeText(summary.row_count || 0);
        }

        if (summarySheets) {
            summarySheets.textContent = sanitizeText(summary.sheet_count || 0);
        }

        if (summaryColumns) {
            summaryColumns.textContent = sanitizeText(summary.column_count || 0);
        }

        if (previewSheetLabel) {
            previewSheetLabel.textContent = summary.sheet_name || 'Workbook';
        }

        if (previewRowLabel) {
            previewRowLabel.textContent = (summary.preview_count || 0) + ' rows';
        }
    }

    function renderPreview(columns, rows, summary) {
        const safeColumns = Array.isArray(columns) ? columns : [];
        const safeRows = Array.isArray(rows) ? rows : [];

        state.currentColumns = columns || [];
        state.currentRows = rows || [];

        if (!previewTableHead || !previewTableBody) {
            return;
        }

        const headerCells = [
            '<th>Source sheet</th>',
            '<th>Row #</th>',
        ];

        safeColumns.forEach(function (column) {
            headerCells.push('<th>' + sanitizeText(column.label || column.key || 'Column') + '</th>');
        });

        previewTableHead.innerHTML = '<tr>' + headerCells.join('') + '</tr>';

        if (!safeRows.length) {
            previewTableBody.innerHTML = '<tr><td colspan="' + (safeColumns.length + 2) + '"><div class="empty-state text-center py-5"><div class="empty-icon mb-3"><i class="bi bi-inbox"></i></div><h6 class="mb-2">No rows found</h6><p class="text-secondary mb-0">The selected sheet does not have any visible data rows.</p></div></td></tr>';
            renderMetrics(summary || {});
            return;
        }

        const rowMarkup = safeRows.map(function (row) {
            const values = safeColumns.map(function (column) {
                const cellValue = row.column_data ? row.column_data[column.key] : '';
                const text = cellValue === null || cellValue === undefined ? '' : sanitizeText(cellValue);
                return '<td>' + text + '</td>';
            });

            return '<tr><td class="text-nowrap">' + sanitizeText(row.sheet_name || '') + '</td><td>' + sanitizeText(row.row_index || '') + '</td>' + values.join('') + '</tr>';
        }).join('');

        previewTableBody.innerHTML = rowMarkup;
        renderMetrics(summary || {});
    }

    function renderWorkbookPreviewFromSheetJs(file) {
        return file.arrayBuffer().then(function (buffer) {
            if (typeof XLSX === 'undefined') {
                throw new Error('Spreadsheet preview library is unavailable.');
            }

            const workbook = XLSX.read(buffer, { type: 'array', cellDates: true, blankrows: false });

            if (!workbook.SheetNames.length) {
                throw new Error('The workbook does not contain any sheets.');
            }

            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const grid = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '', raw: false, blankrows: false });

            if (!grid.length) {
                throw new Error('The first sheet is empty.');
            }

            const rawHeaders = grid[0].map(function (value, index) {
                const label = sanitizeText(value).trim() || 'Column ' + (index + 1);
                return {
                    key: 'column_' + (index + 1),
                    label: label,
                };
            });

            const rows = grid.slice(1, 26).filter(function (row) {
                return row.some(function (cell) {
                    return sanitizeText(cell).trim() !== '';
                });
            }).map(function (row, index) {
                const data = {};

                rawHeaders.forEach(function (header, headerIndex) {
                    data[header.key] = sanitizeText(row[headerIndex] ?? '').trim();
                });

                return {
                    sheet_name: firstSheetName,
                    row_index: index + 2,
                    column_data: data,
                };
            });

            renderPreview(rawHeaders, rows, {
                sheet_name: firstSheetName,
                row_count: Math.max(grid.length - 1, 0),
                sheet_count: workbook.SheetNames.length,
                column_count: rawHeaders.length,
                preview_count: rows.length,
            });
        });
    }

    function updateFileChip(file) {
        if (!fileChip || !fileNameLabel) {
            return;
        }

        fileNameLabel.textContent = file ? file.name : 'Ready to upload';
        fileChip.classList.toggle('d-none', !file);
    }

    function syncReportTitleFromFile(file) {
        if (reportTitleInput && file && !reportTitleInput.value.trim()) {
            reportTitleInput.value = defaultReportTitle(file.name);
        }
    }

    function getSelectedFile() {
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            return null;
        }

        return fileInput.files[0];
    }

    function resetPreviewState() {
        renderPreview([], [], {
            sheet_name: 'No file loaded',
            row_count: 0,
            sheet_count: 0,
            column_count: 0,
            preview_count: 0,
        });
    }

    async function handleUploadSubmit(event) {
        event.preventDefault();

        const file = getSelectedFile();

        if (!file) {
            showAlert('warning', 'Please select an .xlsx file before importing.');
            return;
        }

        const uploadButton = uploadSubmit;
        setUploadBusy(true);

        try {
            const formData = new FormData(uploadForm);
            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'The upload failed.');
            }

            const preview = payload.preview || {};
            renderPreview(preview.columns || [], preview.rows || [], preview.summary || {});

            if (reportUploadId) {
                reportUploadId.value = payload.upload_id || '';
            }

            if (generateReportCard) {
                generateReportCard.classList.remove('d-none');
                generateReportCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            if (reportTitleInput) {
                reportTitleInput.value = payload.suggested_title || defaultReportTitle(file.name);
            }

            showAlert('success', payload.message || 'Excel workbook imported successfully.');
        } catch (error) {
            showAlert('danger', error.message || 'The upload failed.');
        } finally {
            setUploadBusy(false);
        }
    }

    async function handleGenerateReportSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const submitButton = form.querySelector('button[type="submit"]');
        setReportBusy(submitButton, true);

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to generate the report.');
            }

            showAlert('success', payload.message || 'Report generated successfully.');

            if (payload.report_url) {
                window.location.href = payload.report_url;
            }
        } catch (error) {
            showAlert('danger', error.message || 'Unable to generate the report.');
        } finally {
            setReportBusy(submitButton, false);
        }
    }

    async function handleDeleteReport(event) {
        const button = event.currentTarget;
        const reportId = button.getAttribute('data-report-id');

        if (!reportId) {
            return;
        }

        const confirmed = window.confirm('Delete this report? This cannot be undone.');

        if (!confirmed) {
            return;
        }

        button.disabled = true;

        try {
            const formData = new FormData();
            formData.append('report_id', reportId);

            const response = await fetch((config.baseUrl || '') + '/index.php?action=delete-report', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to delete the report.');
            }

            showAlert('success', payload.message || 'Report deleted.');
            window.location.reload();
        } catch (error) {
            showAlert('danger', error.message || 'Unable to delete the report.');
            button.disabled = false;
        }
    }

    function attachDropzone() {
        if (!dropzone || !fileInput) {
            return;
        }

        const openFilePicker = function () {
            fileInput.click();
        };

        if (triggerFileButton) {
            triggerFileButton.addEventListener('click', openFilePicker);
        }

        dropzone.addEventListener('click', function (event) {
            if (event.target.closest('button')) {
                return;
            }

            openFilePicker();
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            const files = event.dataTransfer && event.dataTransfer.files;

            if (files && files.length) {
                const fileList = new DataTransfer();
                Array.from(files).forEach(function (file) {
                    fileList.items.add(file);
                });

                fileInput.files = fileList.files;

                const file = fileList.files[0];
                updateFileChip(file);
                syncReportTitleFromFile(file);

                if (file.name.toLowerCase().endsWith('.xlsx')) {
                    renderWorkbookPreviewFromSheetJs(file).catch(function (error) {
                        showAlert('danger', error.message || 'Could not preview the selected workbook.');
                    });
                } else {
                    showAlert('warning', 'Only .xlsx files are supported.');
                }
            }
        });
    }

    function attachFileInputPreview() {
        if (!fileInput) {
            return;
        }

        fileInput.addEventListener('change', function () {
            const file = getSelectedFile();

            updateFileChip(file);
            syncReportTitleFromFile(file);

            if (!file) {
                resetPreviewState();
                return;
            }

            if (!file.name.toLowerCase().endsWith('.xlsx')) {
                showAlert('warning', 'Only .xlsx files are supported.');
                fileInput.value = '';
                updateFileChip(null);
                resetPreviewState();
                return;
            }

            renderWorkbookPreviewFromSheetJs(file).catch(function (error) {
                showAlert('danger', error.message || 'Could not preview the selected workbook.');
            });
        });
    }

    function attachForms() {
        if (uploadForm) {
            uploadForm.addEventListener('submit', handleUploadSubmit);
        }

        generateReportForms.forEach(function (form) {
            form.addEventListener('submit', handleGenerateReportSubmit);
        });
    }

    function attachDeleteButtons() {
        deleteButtons.forEach(function (button) {
            button.addEventListener('click', handleDeleteReport);
        });
    }

    function bootstrapPreviewState() {
        if (!previewTableBody || !previewTableHead) {
            return;
        }

        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            resetPreviewState();
        }
    }

    attachDropzone();
    attachFileInputPreview();
    attachForms();
    attachDeleteButtons();
    bootstrapPreviewState();
})();