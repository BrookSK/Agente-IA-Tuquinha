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
                avatar_path = :avatar_path,
                cover_path = :cover_path,
                language = :language,
                profile_category = :profile_category,
                profile_privacy = :profile_privacy,
                visibility_scope = :visibility_scope,
                relationship_status = :relationship_status,
                birthday = :birthday,
                age = :age,
                children = :children,
                ethnicity = :ethnicity,
                mood = :mood,
                sexual_orientation = :sexual_orientation,
                style = :style,
                smokes = :smokes,
                drinks = :drinks,
                pets = :pets,
                hometown = :hometown,
                location = :location,
                sports = :sports,
                passions = :passions,
                activities = :activities,
                instagram = :instagram,
                facebook = :facebook,
                youtube = :youtube,
                updated_at = NOW()
                WHERE user_id = :user_id');
        } else {
            $stmt = $pdo->prepare('INSERT INTO user_social_profiles
                (user_id, about_me, interests, favorite_music, favorite_movies, favorite_books, website,
                 avatar_path, cover_path, language, profile_category, profile_privacy, visibility_scope, relationship_status,
                 birthday, age, children, ethnicity, mood, sexual_orientation, style, smokes, drinks, pets,
                 hometown, location, sports, passions, activities, instagram, facebook, youtube,
                 visits_count, last_visit_at)
                VALUES (:user_id, :about_me, :interests, :favorite_music, :favorite_movies, :favorite_books, :website,
                 :avatar_path, :cover_path, :language, :profile_category, :profile_privacy, :visibility_scope, :relationship_status,
                 :birthday, :age, :children, :ethnicity, :mood, :sexual_orientation, :style, :smokes, :drinks, :pets,
                 :hometown, :location, :sports, :passions, :activities, :instagram, :facebook, :youtube,
                 0, NULL)');
        }

        $stmt->execute([
            'user_id' => $userId,
            'about_me' => $data['about_me'] ?? null,
            'interests' => $data['interests'] ?? null,
            'favorite_music' => $data['favorite_music'] ?? null,
            'favorite_movies' => $data['favorite_movies'] ?? null,
            'favorite_books' => $data['favorite_books'] ?? null,
            'website' => $data['website'] ?? null,
            'avatar_path' => $data['avatar_path'] ?? null,
            'cover_path' => $data['cover_path'] ?? null,
            'language' => $data['language'] ?? null,
            'profile_category' => $data['profile_category'] ?? null,
            'profile_privacy' => $data['profile_privacy'] ?? null,
            'visibility_scope' => $data['visibility_scope'] ?? null,
            'relationship_status' => $data['relationship_status'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'age' => $data['age'] ?? null,
            'children' => $data['children'] ?? null,
            'ethnicity' => $data['ethnicity'] ?? null,
            'mood' => $data['mood'] ?? null,
            'sexual_orientation' => $data['sexual_orientation'] ?? null,
            'style' => $data['style'] ?? null,
            'smokes' => $data['smokes'] ?? null,
            'drinks' => $data['drinks'] ?? null,
            'pets' => $data['pets'] ?? null,
            'hometown' => $data['hometown'] ?? null,
            'location' => $data['location'] ?? null,
            'sports' => $data['sports'] ?? null,
            'passions' => $data['passions'] ?? null,
            'activities' => $data['activities'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'youtube' => $data['youtube'] ?? null,
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
