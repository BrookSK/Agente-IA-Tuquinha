<?php

use App\Core\Database;

class UserNotification
{
    /**
     * Cria uma nova notificação para um usuário
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
        $pdo = Database::getConnection();
        
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
    
    /**
     * Cria notificação de curtida em post
     */
    public static function createLikeNotification(
        int $postAuthorId,
        int $likerUserId,
        string $postType,
        int $postId,
        string $link
    ): int {
        $pdo = Database::getConnection();
        
        // Busca nome de quem curtiu
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $likerUserId]);
        $liker = $stmt->fetch(PDO::FETCH_ASSOC);
        $likerName = $liker['preferred_name'] ?? $liker['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $postAuthorId,
            'type' => 'like',
            'related_type' => $postType,
            'related_id' => $postId,
            'actor_user_id' => $likerUserId,
            'title' => 'Curtida no seu post',
            'message' => "{$likerName} curtiu seu post",
            'link' => $link,
        ]);
    }
    
    /**
     * Cria notificação de pedido de amizade
     */
    public static function createFriendRequestNotification(
        int $recipientUserId,
        int $requesterUserId,
        string $link
    ): int {
        $pdo = Database::getConnection();
        
        // Busca nome de quem enviou o pedido
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $requesterUserId]);
        $requester = $stmt->fetch(PDO::FETCH_ASSOC);
        $requesterName = $requester['preferred_name'] ?? $requester['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $recipientUserId,
            'type' => 'friend_request',
            'related_type' => 'user',
            'related_id' => $requesterUserId,
            'actor_user_id' => $requesterUserId,
            'title' => 'Novo pedido de amizade',
            'message' => "{$requesterName} enviou um pedido de amizade para você",
            'link' => $link,
        ]);
    }
    
    /**
     * Cria notificação de pedido de amizade aceito
     */
    public static function createFriendAcceptedNotification(
        int $requesterUserId,
        int $accepterUserId,
        string $link
    ): int {
        $pdo = Database::getConnection();
        
        // Busca nome de quem aceitou o pedido
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $accepterUserId]);
        $accepter = $stmt->fetch(PDO::FETCH_ASSOC);
        $accepterName = $accepter['preferred_name'] ?? $accepter['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $requesterUserId,
            'type' => 'friend_accepted',
            'related_type' => 'user',
            'related_id' => $accepterUserId,
            'actor_user_id' => $accepterUserId,
            'title' => 'Pedido de amizade aceito',
            'message' => "{$accepterName} aceitou seu pedido de amizade",
            'link' => $link,
        ]);
    }
    
    /**
     * Cria notificação de nova mensagem
     */
    public static function createMessageNotification(
        int $recipientUserId,
        int $senderUserId,
        int $conversationId,
        string $link
    ): int {
        $pdo = Database::getConnection();
        
        // Busca nome de quem enviou a mensagem
        $stmt = $pdo->prepare("SELECT preferred_name, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $senderUserId]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);
        $senderName = $sender['preferred_name'] ?? $sender['name'] ?? 'Alguém';
        
        return self::create([
            'user_id' => $recipientUserId,
            'type' => 'message',
            'related_type' => 'conversation',
            'related_id' => $conversationId,
            'actor_user_id' => $senderUserId,
            'title' => 'Nova mensagem',
            'message' => "{$senderName} enviou uma mensagem para você",
            'link' => $link,
        ]);
    }
}
