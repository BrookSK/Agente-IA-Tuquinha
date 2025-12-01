<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Conversation
{
    public int $id;
    public string $session_id;
    public ?int $user_id = null;
    public ?string $title = null;

    public static function findOrCreateBySession(string $sessionId): self
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
            $conv->title = $row['title'] ?? null;
            return $conv;
        }

        $stmt = $pdo->prepare('INSERT INTO conversations (session_id) VALUES (:session_id)');
        $stmt->execute(['session_id' => $sessionId]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->user_id = null;
        return $conv;
    }

    public static function createForUser(int $userId, string $sessionId): self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO conversations (session_id, user_id) VALUES (:session_id, :user_id)');
        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
        ]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->user_id = $userId;
        $conv->title = null;
        return $conv;
    }

    public static function createForSession(string $sessionId): self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO conversations (session_id) VALUES (:session_id)');
        $stmt->execute(['session_id' => $sessionId]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
        $conv->title = null;
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
            'SELECT c.* FROM conversations c
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
            'SELECT c.* FROM conversations c
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

    public static function searchBySession(string $sessionId, string $term): array
    {
        $pdo = Database::getConnection();
        if ($term === '') {
            return self::allBySession($sessionId);
        }

        $like = '%' . $term . '%';
        $stmt = $pdo->prepare(
            'SELECT c.* FROM conversations c
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
            'SELECT c.* FROM conversations c
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
}
