<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Attachment
{
    public static function create(array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO attachments (
            conversation_id, message_id, type, path, original_name, mime_type, size
        ) VALUES (
            :conversation_id, :message_id, :type, :path, :original_name, :mime_type, :size
        )');
        $stmt->execute($data);
    }
}
