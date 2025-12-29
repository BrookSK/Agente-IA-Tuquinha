<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Conversation
{
    public int $id;
    public string $session_id;
    public ?int $user_id = null;
    public ?int $persona_id = null;
    public ?string $title = null;
    public ?int $project_id = null;

    public static function findOrCreateBySession(string $sessionId, ?int $personaId = null, ?int $projectId = null): self
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE session_id = :session_id LIMIT 1');
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $conv = new self();
            $conv->id = (int)$row['id'];
            $conv->session_id = $row['session_id'];
            $conv->user_id = isset($row['user_id']) ? (int)$row['user_id'] : null;
            $conv->persona_id = isset($row['persona_id']) ? (int)$row['persona_id'] : null;
            $conv->title = $row['title'] ?? null;
            $conv->project_id = isset($row['project_id']) ? (int)$row['project_id'] : null;
            return $conv;
        }

        $stmt = $pdo->prepare('INSERT INTO conversations (session_id, persona_id, project_id) VALUES (:session_id, :persona_id, :project_id)');
        $stmt->execute([
            'session_id' => $sessionId,
            'persona_id' => $personaId,
            'project_id' => $projectId !== null && $projectId > 0 ? $projectId : null,
        ]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->user_id = null;
        $conv->persona_id = $personaId;
        $conv->project_id = $projectId !== null && $projectId > 0 ? $projectId : null;
        return $conv;
    }

    public static function createForUser(int $userId, string $sessionId, ?int $personaId = null, ?int $projectId = null): self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO conversations (session_id, user_id, persona_id, project_id) VALUES (:session_id, :user_id, :persona_id, :project_id)');
        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'persona_id' => $personaId,
            'project_id' => $projectId !== null && $projectId > 0 ? $projectId : null,
        ]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->user_id = $userId;
        $conv->persona_id = $personaId;
        $conv->title = null;
        $conv->project_id = $projectId !== null && $projectId > 0 ? $projectId : null;
        return $conv;
    }

    public static function createForSession(string $sessionId, ?int $personaId = null, ?int $projectId = null): self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO conversations (session_id, persona_id, project_id) VALUES (:session_id, :persona_id, :project_id)');
        $stmt->execute([
            'session_id' => $sessionId,
            'persona_id' => $personaId,
            'project_id' => $projectId !== null && $projectId > 0 ? $projectId : null,
        ]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->persona_id = $personaId;
        $conv->title = null;
        $conv->project_id = $projectId !== null && $projectId > 0 ? $projectId : null;
        return $conv;
    }

    public static function updateTitle(int $id, string $title): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE conversations SET title = :title WHERE id = :id LIMIT 1');
        $stmt->execute([
            'title' => $title,
            'id' => $id,
        ]);
    }

    public static function allBySession(string $sessionId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT c.*, p.name AS persona_name FROM conversations c
             LEFT JOIN personalities p ON p.id = c.persona_id
             WHERE c.session_id = :session_id
               AND EXISTS (
                   SELECT 1 FROM messages m
                   WHERE m.conversation_id = c.id
               )
             ORDER BY c.created_at DESC'
        );
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT c.*, p.name AS persona_name FROM conversations c
             LEFT JOIN personalities p ON p.id = c.persona_id
             WHERE c.user_id = :user_id
               AND EXISTS (
                   SELECT 1 FROM messages m
                   WHERE m.conversation_id = c.id
               )
             ORDER BY c.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allByProjectForUser(int $projectId, int $userId): array
    {
        if ($projectId <= 0 || $userId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT c.*, MAX(m.created_at) AS last_message_at, p.name AS persona_name
             FROM conversations c
             INNER JOIN messages m ON m.conversation_id = c.id
             LEFT JOIN personalities p ON p.id = c.persona_id
             WHERE c.user_id = :user_id
               AND c.project_id = :project_id
             GROUP BY c.id
             ORDER BY last_message_at DESC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'project_id' => $projectId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchBySession(string $sessionId, string $term): array
    {
        $pdo = Database::getConnection();
        if ($term === '') {
            return self::allBySession($sessionId);
        }

        $like = '%' . $term . '%';
        $stmt = $pdo->prepare(
            'SELECT c.*, p.name AS persona_name FROM conversations c
             LEFT JOIN personalities p ON p.id = c.persona_id
             WHERE c.session_id = :session_id
               AND c.title IS NOT NULL
               AND c.title <> ""
               AND c.title LIKE :term
               AND EXISTS (
                   SELECT 1 FROM messages m
                   WHERE m.conversation_id = c.id
               )
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'term' => $like,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchByUser(int $userId, string $term): array
    {
        $pdo = Database::getConnection();
        if ($term === '') {
            return self::allByUser($userId);
        }

        $like = '%' . $term . '%';
        $stmt = $pdo->prepare(
            'SELECT c.*, p.name AS persona_name FROM conversations c
             LEFT JOIN personalities p ON p.id = c.persona_id
             WHERE c.user_id = :user_id
               AND c.title IS NOT NULL
               AND c.title <> ""
               AND c.title LIKE :term
               AND EXISTS (
                   SELECT 1 FROM messages m
                   WHERE m.conversation_id = c.id
               )
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'term' => $like,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByIdAndSession(int $id, string $sessionId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = :id AND session_id = :session_id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'session_id' => $sessionId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updatePersona(int $id, ?int $personaId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE conversations SET persona_id = :persona_id WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'persona_id' => $personaId !== null && $personaId > 0 ? $personaId : null,
        ]);
    }

    public static function findByIdForUser(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensureUniqueTitle(string $sessionId, string $baseTitle): string
    {
        $pdo = Database::getConnection();

        $title = trim($baseTitle);
        if ($title === '') {
            $title = 'Chat com o Tuquinha';
        }

        // Se não existir ainda, usa direto
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM conversations WHERE session_id = :session_id AND title = :title');
        $stmt->execute([
            'session_id' => $sessionId,
            'title' => $title,
        ]);
        $count = (int)$stmt->fetchColumn();
        if ($count === 0) {
            return $title;
        }

        // Caso já exista, tenta com sufixos (2), (3), ...
        $suffix = 2;
        while (true) {
            $candidate = $title . ' (' . $suffix . ')';
            $stmt->execute([
                'session_id' => $sessionId,
                'title' => $candidate,
            ]);
            $exists = (int)$stmt->fetchColumn();
            if ($exists === 0) {
                return $candidate;
            }
            $suffix++;
            if ($suffix > 50) {
                // evita loop infinito em cenário extremo
                return $title . ' (' . uniqid() . ')';
            }
        }
    }

    public static function deleteByIdForUser(int $conversationId, int $userId): bool
    {
        if ($conversationId <= 0 || $userId <= 0) {
            return false;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // ownership check
            $stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = :id AND user_id = :user_id LIMIT 1');
            $stmt->execute([
                'id' => $conversationId,
                'user_id' => $userId,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare('DELETE FROM conversation_settings WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM attachments WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM messages WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM conversations WHERE id = :cid AND user_id = :user_id LIMIT 1');
            $stmt->execute([
                'cid' => $conversationId,
                'user_id' => $userId,
            ]);

            $pdo->commit();
            return ((int)$stmt->rowCount()) > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public static function deleteByIdForSession(int $conversationId, string $sessionId): bool
    {
        $sessionId = trim($sessionId);
        if ($conversationId <= 0 || $sessionId === '') {
            return false;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // ownership check
            $stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = :id AND session_id = :session_id LIMIT 1');
            $stmt->execute([
                'id' => $conversationId,
                'session_id' => $sessionId,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare('DELETE FROM conversation_settings WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM attachments WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM messages WHERE conversation_id = :cid');
            $stmt->execute(['cid' => $conversationId]);

            $stmt = $pdo->prepare('DELETE FROM conversations WHERE id = :cid AND session_id = :session_id LIMIT 1');
            $stmt->execute([
                'cid' => $conversationId,
                'session_id' => $sessionId,
            ]);

            $pdo->commit();
            return ((int)$stmt->rowCount()) > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}
