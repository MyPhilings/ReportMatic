<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ReportRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function create(int $uploadId, string $title, array $summary = [], string $status = 'generated'): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO reports (upload_id, report_title, report_status, summary_json, created_at) VALUES (:upload_id, :report_title, :report_status, :summary_json, NOW())'
        );

        $statement->execute([
            'upload_id' => $uploadId,
            'report_title' => $title,
            'report_status' => $status,
            'summary_json' => $summary === [] ? null : json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $reportId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, u.filename, u.original_filename, u.file_path, u.file_size, u.mime_type, u.sheet_count, u.row_count, u.column_count, u.status AS upload_status, u.summary_json AS upload_summary_json, u.upload_date
             FROM reports r
             INNER JOIN uploads u ON u.id = r.upload_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $reportId]);
        $report = $statement->fetch();

        if (!$report) {
            return null;
        }

        $report['summary'] = safe_json_decode($report['summary_json'] ?? null, []);
        $report['summary_json'] = $report['summary'];
        $report['upload'] = [
            'id' => (int) $report['upload_id'],
            'filename' => $report['filename'],
            'original_filename' => $report['original_filename'],
            'file_path' => $report['file_path'],
            'file_size' => (int) $report['file_size'],
            'mime_type' => $report['mime_type'],
            'sheet_count' => (int) $report['sheet_count'],
            'row_count' => (int) $report['row_count'],
            'column_count' => (int) $report['column_count'],
            'status' => $report['upload_status'],
            'summary' => safe_json_decode($report['upload_summary_json'] ?? null, []),
            'upload_date' => $report['upload_date'],
        ];

        return $report;
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
    }

    public function recent(int $limit = 10): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, u.filename, u.original_filename, u.upload_date
             FROM reports r
             INNER JOIN uploads u ON u.id = r.upload_id
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([$this, 'hydrateReport'], $statement->fetchAll());
    }

    public function paginate(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $count = $this->count();
        $statement = $this->pdo->prepare(
            'SELECT r.*, u.filename, u.original_filename, u.upload_date
             FROM reports r
             INNER JOIN uploads u ON u.id = r.upload_id
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'data' => array_map([$this, 'hydrateReport'], $statement->fetchAll()),
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($count / $perPage)),
        ];
    }

    public function findByUploadId(int $uploadId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, u.filename, u.original_filename, u.upload_date
             FROM reports r
             INNER JOIN uploads u ON u.id = r.upload_id
             WHERE r.upload_id = :upload_id
             ORDER BY r.created_at DESC, r.id DESC'
        );
        $statement->execute(['upload_id' => $uploadId]);

        return array_map([$this, 'hydrateReport'], $statement->fetchAll());
    }

    public function delete(int $reportId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM reports WHERE id = :id');

        return $statement->execute(['id' => $reportId]);
    }

    private function hydrateReport(array $report): array
    {
        $report['summary'] = safe_json_decode($report['summary_json'] ?? null, []);
        $report['summary_json'] = $report['summary'];

        return $report;
    }
}