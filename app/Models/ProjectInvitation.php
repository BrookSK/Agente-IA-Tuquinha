<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class ProjectInvitation
{
    public static function create(int $projectId, int $inviterUserId, string $invitedEmail, ?string $invitedName, string $role, string $token): int
    {
        $role = in_array($role, ['read', 'write', 'admin'], true) ? $role : 'read';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO project_invitations
            (project_id, inviter_user_id, invited_email, invited_name, role, token)
            VALUES (:project_id, :inviter_user_id, :invited_email, :invited_name, :role, :token)');
        $stmt->execute([
            'project_id' => $projectId,
            'inviter_user_id' => $inviterUserId,
            'invited_email' => $invitedEmail,
            'invited_name' => $invitedName,
            'role' => $role,
            'token' => $token,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_invitations WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function hasValidInviteForEmail(int $projectId, string $email): bool
    {
        $email = trim($email);
        if ($projectId <= 0 || $email === '') {
            return false;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM project_invitations
            WHERE project_id = :pid AND invited_email = :email AND status = "pending"
            LIMIT 1');
        $stmt->execute([
            'pid' => $projectId,
            'email' => $email,
        ]);
        return (bool)$stmt->fetchColumn();
    }

    public static function allPendingForProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM project_invitations WHERE project_id = :pid AND status = "pending" ORDER BY created_at DESC');
        $stmt->execute(['pid' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function cancelById(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_invitations SET status = "cancelled" WHERE id = :id AND status = "pending"');
        $stmt->execute(['id' => $id]);
    }

    public static function markAccepted(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE project_invitations
            SET status = "accepted", accepted_at = NOW()
            WHERE id = :id AND status = "pending"');
        $stmt->execute(['id' => $id]);
    }
}
