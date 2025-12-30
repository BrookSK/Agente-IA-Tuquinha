<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Attachment
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        $hasOpenAiFileId = array_key_exists('openai_file_id', $data);

        if ($hasOpenAiFileId) {
            $stmt = $pdo->prepare('INSERT INTO attachments (
                conversation_id, message_id, type, path, original_name, mime_type, size, openai_file_id
            ) VALUES (
                :conversation_id, :message_id, :type, :path, :original_name, :mime_type, :size, :openai_file_id
            )');
        } else {
            $stmt = $pdo->prepare('INSERT INTO attachments (
                conversation_id, message_id, type, path, original_name, mime_type, size
            ) VALUES (
                :conversation_id, :message_id, :type, :path, :original_name, :mime_type, :size
            )');
        }

        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    public static function allByConversation(int $conversationId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM attachments WHERE conversation_id = :cid ORDER BY id ASC');
        $stmt->execute(['cid' => $conversationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateOpenAIFileId(int $attachmentId, string $openaiFileId): void
    {
        if ($attachmentId <= 0 || trim($openaiFileId) === '') {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE attachments SET openai_file_id = :fid WHERE id = :id');
        $stmt->execute([
            'fid' => $openaiFileId,
            'id' => $attachmentId,
        ]);
    }

    public static function search(?string $type, ?string $beforeDate, int $limit, int $offset): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM attachments WHERE 1=1';
        $params = [];
        if ($type !== null && $type !== '') {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        if ($beforeDate !== null && $beforeDate !== '') {
            $sql .= ' AND created_at < :before';
            $params['before'] = $beforeDate . ' 00:00:00';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countAll(?string $type, ?string $beforeDate): int
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT COUNT(*) AS c FROM attachments WHERE 1=1';
        $params = [];
        if ($type !== null && $type !== '') {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        if ($beforeDate !== null && $beforeDate !== '') {
            $sql .= ' AND created_at < :before';
            $params['before'] = $beforeDate . ' 00:00:00';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function deleteByIds(array $ids): void
    {
        $filtered = array_values(array_filter(array_map('intval', $ids), static function ($v) {
            return $v > 0;
        }));
        if (!$filtered) {
            return;
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($filtered), '?'));
        $stmt = $pdo->prepare('DELETE FROM attachments WHERE id IN (' . $placeholders . ')');
        $stmt->execute($filtered);
    }
}
