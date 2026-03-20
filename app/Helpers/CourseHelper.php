<?php

namespace App\Helpers;

use App\Core\Database;

class CourseHelper
{
    /**
     * Busca dados dinâmicos do curso (módulos, aulas, comunidades)
     * 
     * @param int $courseId ID do curso
     * @return array Array com totalModules, totalLessons e communities
     */
    public static function getCourseDetails(int $courseId): array
    {
        $result = [
            'totalModules' => 0,
            'totalLessons' => 0,
            'communities' => []
        ];
        
        if ($courseId <= 0) {
            return $result;
        }
        
        try {
            $pdo = Database::getConnection();
            
            // Buscar total de módulos
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM course_modules WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $modulesResult = $stmt->fetch(\PDO::FETCH_ASSOC);
            $result['totalModules'] = isset($modulesResult['total']) ? (int)$modulesResult['total'] : 0;
            
            // Buscar total de aulas
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM course_lessons WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $lessonsResult = $stmt->fetch(\PDO::FETCH_ASSOC);
            $result['totalLessons'] = isset($lessonsResult['total']) ? (int)$lessonsResult['total'] : 0;
            
            // Buscar comunidades
            $stmt = $pdo->prepare("SELECT c.name FROM course_allowed_communities cac 
                                   INNER JOIN communities c ON c.id = cac.community_id 
                                   WHERE cac.course_id = ? AND c.is_active = 1");
            $stmt->execute([$courseId]);
            $result['communities'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            
        } catch (\Exception $e) {
            // Retorna valores padrão em caso de erro
        }
        
        return $result;
    }
}
