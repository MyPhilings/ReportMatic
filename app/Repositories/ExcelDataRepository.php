<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ExcelDataRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function bulkInsert(int $uploadId, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $placeholders = [];
        $parameters = [];

        foreach ($rows as $row) {
            $placeholders[] = '(?, ?, ?, ?, NOW())';
            $parameters[] = $uploadId;
            $parameters[] = (string) ($row['sheet_name'] ?? 'Sheet1');
            $parameters[] = (int) ($row['row_index'] ?? 0);
            $parameters[] = json_encode($row['column_data'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO excel_data (upload_id, sheet_name, row_index, column_data, created_at) VALUES ' . implode(', ', $placeholders)
        );

        $statement->execute($parameters);
    }

    public function countByUploadId(int $uploadId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM excel_data WHERE upload_id = :upload_id');
        $statement->execute(['upload_id' => $uploadId]);

        return (int) $statement->fetchColumn();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM excel_data')->fetchColumn();
    }

    public function findByUploadId(int $uploadId, int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $count = $this->countByUploadId($uploadId);
        $statement = $this->pdo->prepare(
            'SELECT * FROM excel_data WHERE upload_id = :upload_id ORDER BY id ASC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':upload_id', $uploadId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'data' => array_map([$this, 'hydrateRow'], $statement->fetchAll()),
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($count / $perPage)),
        ];
    }

    public function findAllByUploadId(int $uploadId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM excel_data WHERE upload_id = :upload_id ORDER BY id ASC');
        $statement->execute(['upload_id' => $uploadId]);

        return array_map([$this, 'hydrateRow'], $statement->fetchAll());
    }

    public function firstRowByUploadId(int $uploadId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM excel_data WHERE upload_id = :upload_id ORDER BY id ASC LIMIT 1');
        $statement->execute(['upload_id' => $uploadId]);
        $row = $statement->fetch();

        return $row ? $this->hydrateRow($row) : null;
    }

    public function deleteByUploadId(int $uploadId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM excel_data WHERE upload_id = :upload_id');

        return $statement->execute(['upload_id' => $uploadId]);
    }

    private function hydrateRow(array $row): array
    {
        $row['column_data'] = safe_json_decode($row['column_data'] ?? null, []);

        return $row;
    }
}