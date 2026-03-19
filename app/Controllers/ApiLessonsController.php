<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

class ApiLessonsController
{
    public function search(): void
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode([]);
            return;
        }
        
        // Get search query if provided
        $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        
        $pdo = Database::getConnection();
        
        // Only show lessons from courses the user is enrolled in
        if ($query !== '') {
            $stmt = $pdo->prepare('
                SELECT 
                    cl.id,
                    cl.title,
                    cl.course_id,
                    c.title as course_title
                FROM course_lessons cl
                INNER JOIN courses c ON cl.course_id = c.id
                INNER JOIN course_enrollments ce ON ce.course_id = c.id
                WHERE ce.user_id = :user_id
                AND cl.is_published = 1
                AND cl.title LIKE :query
                ORDER BY cl.title ASC
                LIMIT 20
            ');
            $stmt->execute([
                'user_id' => $userId,
                'query' => '%' . $query . '%'
            ]);
        } else {
            $stmt = $pdo->prepare('
                SELECT 
                    cl.id,
                    cl.title,
                    cl.course_id,
                    c.title as course_title
                FROM course_lessons cl
                INNER JOIN courses c ON cl.course_id = c.id
                INNER JOIN course_enrollments ce ON ce.course_id = c.id
                WHERE ce.user_id = :user_id
                AND cl.is_published = 1
                ORDER BY cl.title ASC
                LIMIT 50
            ');
            $stmt->execute(['user_id' => $userId]);
        }
        
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($lessons);
    }
}
