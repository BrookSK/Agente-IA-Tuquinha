<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectFileVersion
{
    public static function latestForFile(int $projectFileId): ?array
    {
        if ($projectFileId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_file_versions WHERE project_file_id = :fid ORDER BY version DESC LIMIT 1');
        $stmt->execute(['fid' => $projectFileId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createNewVersion(int $projectFileId, string $storageUrl, ?int $sizeBytes, ?string $sha256, ?string $extractedText, ?int $createdByUserId): int
    {
        $pdo = Database::getConnection();

        $latest = self::latestForFile($projectFileId);
        $nextVersion = $latest ? ((int)$latest['version'] + 1) : 1;

        $stmt = $pdo->prepare('INSERT INTO project_file_versions (project_file_id, version, storage_url, size_bytes, sha256, extracted_text, created_by_user_id) VALUES (:project_file_id, :version, :storage_url, :size_bytes, :sha256, :extracted_text, :created_by_user_id)');
        $stmt->execute([
            'project_file_id' => $projectFileId,
            'version' => $nextVersion,
            'storage_url' => $storageUrl,
            'size_bytes' => $sizeBytes,
            'sha256' => $sha256,
            'extracted_text' => $extractedText,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function latestForFiles(array $projectFileIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $projectFileIds), static function ($v) {
            return $v > 0;
        }));
        if (!$ids) {
            return [];
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT v.*
                FROM project_file_versions v
                INNER JOIN (
                    SELECT project_file_id, MAX(version) AS max_version
                    FROM project_file_versions
                    WHERE project_file_id IN (' . $placeholders . ')
                    GROUP BY project_file_id
                ) x ON x.project_file_id = v.project_file_id AND x.max_version = v.version';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)($r['project_file_id'] ?? 0)] = $r;
        }
        return $map;
    }
}
