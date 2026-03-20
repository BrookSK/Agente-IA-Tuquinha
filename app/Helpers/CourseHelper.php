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
            $db = Database::getInstance();
            
            // Buscar total de módulos
            $modulesResult = $db->query(
                "SELECT COUNT(*) as total FROM course_modules WHERE course_id = ?",
                [$courseId]
            );
            $result['totalModules'] = isset($modulesResult[0]['total']) ? (int)$modulesResult[0]['total'] : 0;
            
            // Buscar total de aulas
            $lessonsResult = $db->query(
                "SELECT COUNT(*) as total FROM course_lessons WHERE course_id = ?",
                [$courseId]
            );
            $result['totalLessons'] = isset($lessonsResult[0]['total']) ? (int)$lessonsResult[0]['total'] : 0;
            
            // Buscar comunidades
            $result['communities'] = $db->query(
                "SELECT c.name FROM course_allowed_communities cac 
                 INNER JOIN communities c ON c.id = cac.community_id 
                 WHERE cac.course_id = ? AND c.is_active = 1",
                [$courseId]
            );
            
        } catch (\Exception $e) {
            // Retorna valores padrão em caso de erro
        }
        
        return $result;
    }
}
