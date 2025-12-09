<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class UserSocialProfile
{
    public static function findByUserId(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_social_profiles WHERE user_id = :uid LIMIT 1');
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsertForUser(int $userId, array $data): void
    {
        if ($userId <= 0) {
            return;
        }

        $existing = self::findByUserId($userId);
        $pdo = Database::getConnection();

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE user_social_profiles SET
                about_me = :about_me,
                interests = :interests,
                favorite_music = :favorite_music,
                favorite_movies = :favorite_movies,
                favorite_books = :favorite_books,
                website = :website,
                updated_at = NOW()
                WHERE user_id = :user_id');
        } else {
            $stmt = $pdo->prepare('INSERT INTO user_social_profiles
                (user_id, about_me, interests, favorite_music, favorite_movies, favorite_books, website, visits_count, last_visit_at)
                VALUES (:user_id, :about_me, :interests, :favorite_music, :favorite_movies, :favorite_books, :website, 0, NULL)');
        }

        $stmt->execute([
            'user_id' => $userId,
            'about_me' => $data['about_me'] ?? null,
            'interests' => $data['interests'] ?? null,
            'favorite_music' => $data['favorite_music'] ?? null,
            'favorite_movies' => $data['favorite_movies'] ?? null,
            'favorite_books' => $data['favorite_books'] ?? null,
            'website' => $data['website'] ?? null,
        ]);
    }

    public static function incrementVisit(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE user_social_profiles
            SET visits_count = visits_count + 1, last_visit_at = NOW()
            WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
    }
}
