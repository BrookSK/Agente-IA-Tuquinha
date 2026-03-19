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
        
        // Get courses the user has access to (via enrollment OR purchase)
        $stmt = $pdo->prepare('
            SELECT DISTINCT
                c.id,
                c.title
            FROM courses c
            WHERE c.is_active = 1
            AND (
                EXISTS (
                    SELECT 1 FROM course_enrollments ce 
                    WHERE ce.course_id = c.id AND ce.user_id = :user_id
                )
                OR EXISTS (
                    SELECT 1 FROM course_purchases cp 
                    WHERE cp.course_id = c.id AND cp.user_id = :user_id AND cp.status = "paid"
                )
            )
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
        
        // Verify user has access to this course (via enrollment OR purchase)
        $accessStmt = $pdo->prepare('
            SELECT 1 FROM courses c
            WHERE c.id = :course_id
            AND c.is_active = 1
            AND (
                EXISTS (
                    SELECT 1 FROM course_enrollments ce 
                    WHERE ce.course_id = c.id AND ce.user_id = :user_id
                )
                OR EXISTS (
                    SELECT 1 FROM course_purchases cp 
                    WHERE cp.course_id = c.id AND cp.user_id = :user_id AND cp.status = "paid"
                )
            )
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
            ORDER BY sort_order ASC, title ASC
        ');
        $stmt->execute(['course_id' => $courseId]);
        
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($lessons);
    }
}
