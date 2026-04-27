# ReportMatic

Minimal Excel import page for internal use.

## What It Does

- Upload a single `.xlsx` file
- Drag and drop or click to choose a file
- Parse the workbook and store rows in MySQL

## Setup

1. Run `composer install`
2. Copy `.env.example` to `.env`
3. Import `database/schema.sql` into MySQL
4. Open `index.php` in your XAMPP/Apache setup

## Notes

- The page is intentionally simple and centered.
- Only `.xlsx` files are accepted.
- Imported rows are stored in the `uploads` and `excel_data` tables.