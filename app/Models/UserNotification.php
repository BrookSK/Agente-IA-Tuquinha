<?php

class UserNotification
{
    /**
     * Cria uma nova notificação para um usuário
     */
    public static function create(array $data): int
    {
        global $pdo;
        
        $sql = "INSERT INTO user_notifications (
            user_id, type, related_type, related_id, actor_user_id, 
            title, message, link, is_read, created_at
        ) VALUES (
            :user_id, :type, :related_type, :related_id, :actor_user_id,
            :title, :message, :link, 0, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':related_type' => $data['related_type'] ?? null,
            ':related_id' => $data['related_id'] ?? null,
            ':actor_user_id' => $data['actor_user_id'] ?? null,
            ':title' => $data['title'],
            ':message' => $data['message'] ?? null,
            ':link' => $data['link'] ?? null,
        ]);
        
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * Busca todas as notificações de um usuário
     */
    public static function findByUserId(int $userId, int $limit = 50): array
    {
        global $pdo;
        
        $sql = "SELECT n.*, 
                u.name as actor_name, 
                u.preferred_name as actor_preferred_name,
                up.avatar_path as actor_avatar
            FROM user_notifications n
            LEFT JOIN users u ON n.actor_user_id = u.id
            LEFT JOIN user_social_profiles up ON u.id = up.user_id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta notificações não lidas de um usuário
     */
    public static function countUnread(int $userId): int
    {
        global $pdo;
        
        $sql = "SELECT COUNT(*) FROM user_notifications 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Marca uma notificação como lida
     */
    public static function markAsRead(int $notificationId): bool
    {
        global $pdo;
        
        $sql = "UPDATE user_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $notificationId]);
    }
    
    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public static function markAllAsRead(int $userId): bool
    {
        global $pdo;
        
        $sql = "UPDATE user_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
    
    /**
     * Cria notificação de menção em comentário
     */
    public static function createMentionNotification(
        int $mentionedUserId,
        int $actorUserId,
        string $commentType,
        int $commentId,
        string $link
    ): int {
        global $pdo;
        
        // Busca nome do ator
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $actorUserId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        $actorName = $actor['preferred_name'] ?? $actor['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $mentionedUserId,
            'type' => 'mention',
            'related_type' => $commentType,
            'related_id' => $commentId,
            'actor_user_id' => $actorUserId,
            'title' => 'Você foi mencionado',
            'message' => "{$actorName} mencionou você em um comentário",
            'link' => $link,
        ]);
    }
    
    /**
     * Cria notificação de resposta a comentário
     */
    public static function createReplyNotification(
        int $originalAuthorId,
        int $replierUserId,
        string $commentType,
        int $replyId,
        string $link
    ): int {
        global $pdo;
        
        // Busca nome do respondente
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $replierUserId]);
        $replier = $stmt->fetch(PDO::FETCH_ASSOC);
        $replierName = $replier['preferred_name'] ?? $replier['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $originalAuthorId,
            'type' => 'reply',
            'related_type' => $commentType,
            'related_id' => $replyId,
            'actor_user_id' => $replierUserId,
            'title' => 'Nova resposta ao seu comentário',
            'message' => "{$replierName} respondeu ao seu comentário",
            'link' => $link,
        ]);
    }
}
