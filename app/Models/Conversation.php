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
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE session_id = :session_id ORDER BY created_at DESC');
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
