<?php

namespace App\Controllers;

use App\Core\Database;
use PDO;

class ApiCoursesController
{
    public function enrolled(): void
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode([]);
            return;
        }
        
        $pdo = Database::getConnection();
        
        // Get courses the user is enrolled in
        $stmt = $pdo->prepare('
            SELECT 
                c.id,
                c.title
            FROM courses c
            INNER JOIN course_enrollments ce ON ce.course_id = c.id
            WHERE ce.user_id = :user_id
            AND c.is_active = 1
            ORDER BY c.title ASC
        ');
        $stmt->execute(['user_id' => $userId]);
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($courses);
    }

    public function lessons(): void
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode([]);
            return;
        }
        
        // Get course ID from route parameter
        $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($courseId <= 0) {
            echo json_encode([]);
            return;
        }
        
        $pdo = Database::getConnection();
        
        // Verify user has access to this course
        $accessStmt = $pdo->prepare('
            SELECT id FROM course_enrollments
            WHERE user_id = :user_id AND course_id = :course_id
            LIMIT 1
        ');
        $accessStmt->execute([
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        
        if (!$accessStmt->fetch()) {
            echo json_encode([]);
            return;
        }
        
        // Get lessons for this course
        $stmt = $pdo->prepare('
            SELECT 
                id,
                title,
                course_id
            FROM course_lessons
            WHERE course_id = :course_id
            AND is_published = 1
            ORDER BY order_index ASC, title ASC
        ');
        $stmt->execute(['course_id' => $courseId]);
        
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($lessons);
    }
}
