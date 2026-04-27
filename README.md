  # ReportMatix

  Modern Excel-to-report workflow for internal teams.

  ## Features

  - Upload and validate `.xlsx` workbooks
  - Parse Excel data into MySQL
  - Preview imported rows in a dashboard UI
  - Generate structured reports from any processed upload
  - View reports in the browser or print-optimized mode
  - Export reports to PDF
  - Review report history and delete outdated entries

  ## Setup

  1. Install PHP dependencies:

    ```bash
    composer install
    ```

  2. Copy `.env.example` to `.env` and update the MySQL credentials.

  3. Create the database tables:

    ```sql
    -- run database/schema.sql in MySQL
    ```

  4. Ensure the `storage/uploads` and `storage/reports` directories are writable.

  5. Open the app from XAMPP or your Apache virtual host and start with:

    ```text
    index.php?page=dashboard
    ```

  ## Folder Layout

  - `app/` - core runtime, repositories, and services
  - `config/` - application and database configuration
  - `views/` - dashboard, upload, and report templates
  - `assets/` - CSS and JavaScript
  - `database/schema.sql` - MySQL schema
  - `storage/` - uploaded files and generated artifacts

  ## Notes

  - The app uses PhpSpreadsheet for Excel parsing and Dompdf for PDF export.
  - Report print and PDF output share the same report layout.
  - Large files are imported in batches to keep database writes efficient.
