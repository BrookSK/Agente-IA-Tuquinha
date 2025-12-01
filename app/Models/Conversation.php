<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Conversation
{
    public int $id;
    public string $session_id;
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
            $conv->title = $row['title'] ?? null;
            return $conv;
        }

        $stmt = $pdo->prepare('INSERT INTO conversations (session_id) VALUES (:session_id)');
        $stmt->execute(['session_id' => $sessionId]);

        $conv = new self();
        $conv->id = (int)$pdo->lastInsertId();
        $conv->session_id = $sessionId;
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
}
