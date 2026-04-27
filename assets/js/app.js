(function () {
    'use strict';

    const uploadForm = document.getElementById('uploadForm');
    const dropzone = document.querySelector('[data-dropzone]');
    const triggerFileButton = document.querySelector('[data-trigger-file]');
    const fileInput = document.getElementById('excelFile');
    const fileLabel = document.querySelector('[data-file-label]');
    const uploadButton = document.querySelector('[data-upload-button]');
    const uploadButtonLabel = document.querySelector('.upload-button-label');
    const uploadButtonLoading = document.querySelector('.upload-button-loading');
    const statusBox = document.getElementById('uploadStatus');
    const reportMount = document.getElementById('reportMount');

    const charts = {
        sheet: null,
        column: null,
    };

    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
        Chart.defaults.color = '#64748b';
        Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.14)';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getSelectedFile() {
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            return null;
        }

        return fileInput.files[0];
    }

    function updateFileLabel(file) {
        if (fileLabel) {
            fileLabel.textContent = file ? file.name : 'No file selected';
        }
    }

    function setDropzoneState(active) {
        if (!dropzone) {
            return;
        }

        dropzone.classList.toggle('dragover', active);
    }

    function setUploading(isUploading) {
        if (uploadButton) {
            uploadButton.disabled = isUploading;
        }

        if (uploadButtonLabel) {
            uploadButtonLabel.classList.toggle('d-none', isUploading);
        }

        if (uploadButtonLoading) {
            uploadButtonLoading.classList.toggle('d-none', !isUploading);
        }
    }

    function renderStatus(kind, title, message, summary) {
        if (!statusBox) {
            return;
        }

        const summaryMarkup = summary
            ? '<div class="result-grid">' +
                '<div class="result-stat"><span class="result-value">' + escapeHtml(summary.row_count ?? 0) + '</span><span class="result-label">Rows imported</span></div>' +
                '<div class="result-stat"><span class="result-value">' + escapeHtml(summary.sheet_count ?? 0) + '</span><span class="result-label">Sheets parsed</span></div>' +
                '<div class="result-stat"><span class="result-value">' + escapeHtml(summary.column_count ?? 0) + '</span><span class="result-label">Columns mapped</span></div>' +
              '</div>'
            : '';

        statusBox.innerHTML =
            '<div class="status-note ' + escapeHtml(kind) + '">' +
                '<strong>' + escapeHtml(title) + '</strong>' +
                '<p>' + escapeHtml(message) + '</p>' +
            '</div>' +
            summaryMarkup;
    }

    function clearStatus() {
        if (statusBox) {
            statusBox.innerHTML = '';
        }
    }

    function destroyCharts() {
        if (charts.sheet) {
            charts.sheet.destroy();
            charts.sheet = null;
        }

        if (charts.column) {
            charts.column.destroy();
            charts.column = null;
        }
    }

    function initializeCharts(reportData) {
        if (typeof Chart === 'undefined') {
            return;
        }

        destroyCharts();

        const sheetCanvas = document.getElementById('sheetChart');
        const columnCanvas = document.getElementById('columnChart');
        const chartData = reportData && reportData.chart_data ? reportData.chart_data : (reportData || {});

        if (sheetCanvas) {
            charts.sheet = new Chart(sheetCanvas, {
                type: 'bar',
                data: {
                    labels: chartData.sheet_labels || [],
                    datasets: [{
                        label: 'Rows',
                        data: chartData.sheet_counts || [],
                        backgroundColor: 'rgba(37, 99, 235, 0.78)',
                        borderRadius: 10,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    aspectRatio: 2.25,
                    layout: {
                        padding: {
                            top: 4,
                            right: 4,
                            bottom: 0,
                            left: 0,
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            titleColor: '#ffffff',
                            bodyColor: '#e2e8f0',
                            borderColor: 'rgba(148, 163, 184, 0.2)',
                            borderWidth: 1,
                            displayColors: false,
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b' },
                            border: { color: 'rgba(148, 163, 184, 0.16)' },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#64748b' },
                            grid: { color: 'rgba(148, 163, 184, 0.12)' },
                            border: { color: 'rgba(148, 163, 184, 0.16)' },
                        },
                    },
                },
            });
        }

        if (columnCanvas) {
            charts.column = new Chart(columnCanvas, {
                type: 'doughnut',
                data: {
                    labels: chartData.column_labels || [],
                    datasets: [{
                        data: chartData.column_counts || [],
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.82)',
                            'rgba(21, 128, 61, 0.82)',
                            'rgba(245, 158, 11, 0.82)',
                            'rgba(168, 85, 247, 0.82)',
                            'rgba(14, 165, 233, 0.82)',
                            'rgba(239, 68, 68, 0.82)',
                        ],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    aspectRatio: 1.65,
                    cutout: '68%',
                    layout: {
                        padding: 12,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 14,
                            },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            titleColor: '#ffffff',
                            bodyColor: '#e2e8f0',
                            borderColor: 'rgba(148, 163, 184, 0.2)',
                            borderWidth: 1,
                        },
                    },
                },
            });
        }
    }

    function showReport(reportHtml, reportData) {
        if (!reportMount) {
            return;
        }

        reportMount.innerHTML = reportHtml || '';
        reportMount.classList.toggle('d-none', !reportHtml);

        if (reportHtml) {
            initializeCharts(reportData || {});

            window.setTimeout(function () {
                reportMount.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 120);
        }
    }

    function bootstrapExistingReport() {
        if (!reportMount || reportMount.classList.contains('d-none')) {
            return;
        }

        const reportRoot = reportMount.querySelector('#generatedReport');

        if (!reportRoot) {
            return;
        }

        let reportData = null;

        try {
            reportData = JSON.parse(reportRoot.dataset.reportData || '{}');
        } catch (error) {
            reportData = null;
        }

        initializeCharts(reportData || {});

        window.setTimeout(function () {
            reportMount.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
    }

    function isXlsxFile(file) {
        return !!file && file.name.toLowerCase().endsWith('.xlsx');
    }

    function setSelectedFile(file) {
        if (!fileInput || !file) {
            return;
        }

        const fileList = new DataTransfer();
        fileList.items.add(file);
        fileInput.files = fileList.files;
        updateFileLabel(file);
    }

    function openFilePicker() {
        if (fileInput) {
            fileInput.click();
        }
    }

    async function handleSubmit(event) {
        event.preventDefault();

        const file = getSelectedFile();

        if (!file) {
            renderStatus('error', 'No file selected', 'Choose an .xlsx file first.');
            return;
        }

        if (!isXlsxFile(file)) {
            renderStatus('error', 'Invalid file type', 'Only .xlsx files are supported.');
            return;
        }

        setUploading(true);
        renderStatus('loading', 'Uploading file', 'Please wait while the workbook is imported.');

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

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || !payload.success) {
                throw new Error((payload && payload.message) ? payload.message : 'The upload failed.');
            }

            renderStatus('success', 'Import complete', payload.message || 'The file has been imported successfully.', payload.summary || null);
            showReport(payload.report_html || '', payload.report_data || null);

            if (fileInput) {
                fileInput.value = '';
            }

            updateFileLabel(null);
        } catch (error) {
            renderStatus('error', 'Upload failed', error.message || 'Unable to import the file.');
        } finally {
            setUploading(false);
        }
    }

    if (triggerFileButton) {
        triggerFileButton.addEventListener('click', function () {
            openFilePicker();
        });
    }

    if (dropzone) {
        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                setDropzoneState(true);
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                event.stopPropagation();
                setDropzoneState(false);
            });
        });

        dropzone.addEventListener('drop', function (event) {
            const files = event.dataTransfer && event.dataTransfer.files;

            if (!files || !files.length) {
                return;
            }

            const file = files[0];

            if (!isXlsxFile(file)) {
                renderStatus('error', 'Invalid file type', 'Only .xlsx files are supported.');
                return;
            }

            setSelectedFile(file);
            clearStatus();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = getSelectedFile();

            updateFileLabel(file);

            if (!file) {
                clearStatus();
                return;
            }

            if (!isXlsxFile(file)) {
                renderStatus('error', 'Invalid file type', 'Only .xlsx files are supported.');
                fileInput.value = '';
                updateFileLabel(null);
                return;
            }

            clearStatus();
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', handleSubmit);
    }

    updateFileLabel(null);
    bootstrapExistingReport();
})();