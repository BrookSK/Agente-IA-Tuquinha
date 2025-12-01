<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findAdminByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND is_admin = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createUser(string $name, string $email, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (:name, :email, :password_hash, 0)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateName(int $id, string $name): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id LIMIT 1');
        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id LIMIT 1');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $id,
        ]);
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function search(string $term): array
    {
        $pdo = Database::getConnection();
        $like = '%' . $term . '%';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE name LIKE :q OR email LIKE :q ORDER BY created_at DESC');
        $stmt->execute(['q' => $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function countAdmins(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }
}
