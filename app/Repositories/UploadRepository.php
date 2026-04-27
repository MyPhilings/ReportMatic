<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UploadRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO uploads (
                filename,
                original_filename,
                file_path,
                file_size,
                mime_type,
                sheet_count,
                row_count,
                column_count,
                status,
                summary_json,
                error_message,
                upload_date
            ) VALUES (
                :filename,
                :original_filename,
                :file_path,
                :file_size,
                :mime_type,
                :sheet_count,
                :row_count,
                :column_count,
                :status,
                :summary_json,
                :error_message,
                NOW()
            )'
        );

        $statement->execute([
            'filename' => $data['filename'] ?? '',
            'original_filename' => $data['original_filename'] ?? '',
            'file_path' => $data['file_path'] ?? '',
            'file_size' => (int) ($data['file_size'] ?? 0),
            'mime_type' => $data['mime_type'] ?? 'application/octet-stream',
            'sheet_count' => (int) ($data['sheet_count'] ?? 0),
            'row_count' => (int) ($data['row_count'] ?? 0),
            'column_count' => (int) ($data['column_count'] ?? 0),
            'status' => $data['status'] ?? 'uploaded',
            'summary_json' => isset($data['summary_json']) ? json_encode($data['summary_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'error_message' => $data['error_message'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $uploadId, array $data): bool
    {
        $allowedFields = [
            'filename',
            'original_filename',
            'file_path',
            'file_size',
            'mime_type',
            'sheet_count',
            'row_count',
            'column_count',
            'status',
            'summary_json',
            'error_message',
        ];

        $assignments = [];
        $parameters = ['id' => $uploadId];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $assignments[] = $field . ' = :' . $field;
            $parameters[$field] = $field === 'summary_json' && is_array($data[$field])
                ? json_encode($data[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $data[$field];
        }

        if ($assignments === []) {
            return true;
        }

        $statement = $this->pdo->prepare('UPDATE uploads SET ' . implode(', ', $assignments) . ' WHERE id = :id');

        return $statement->execute($parameters);
    }

    public function markProcessed(int $uploadId, array $summary, int $rowCount, int $columnCount, int $sheetCount): bool
    {
        return $this->update($uploadId, [
            'status' => 'processed',
            'row_count' => $rowCount,
            'column_count' => $columnCount,
            'sheet_count' => $sheetCount,
            'summary_json' => $summary,
            'error_message' => null,
        ]);
    }

    public function markFailed(int $uploadId, string $message): bool
    {
        return $this->update($uploadId, [
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }

    public function findById(int $uploadId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM uploads WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $uploadId]);
        $upload = $statement->fetch();

        if (!$upload) {
            return null;
        }

        $upload['summary'] = safe_json_decode($upload['summary_json'] ?? null, []);
        $upload['summary_json'] = $upload['summary'];

        return $upload;
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM uploads')->fetchColumn();
    }

    public function recent(int $limit = 5): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM uploads ORDER BY upload_date DESC, id DESC LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();

        return array_map([$this, 'hydrateUpload'], $rows);
    }

    public function paginate(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $count = $this->count();
        $statement = $this->pdo->prepare('SELECT * FROM uploads ORDER BY upload_date DESC, id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'data' => array_map([$this, 'hydrateUpload'], $statement->fetchAll()),
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($count / $perPage)),
        ];
    }

    public function allProcessed(int $limit = 50): array
    {
        $statement = $this->pdo->prepare("SELECT * FROM uploads WHERE status = 'processed' ORDER BY upload_date DESC, id DESC LIMIT :limit");
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map([$this, 'hydrateUpload'], $statement->fetchAll());
    }

    public function delete(int $uploadId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM uploads WHERE id = :id');

        return $statement->execute(['id' => $uploadId]);
    }

    private function hydrateUpload(array $upload): array
    {
        $upload['summary'] = safe_json_decode($upload['summary_json'] ?? null, []);
        $upload['summary_json'] = $upload['summary'];

        return $upload;
    }
}