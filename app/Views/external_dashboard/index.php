<div class="header">
    <h1>Bem-vindo, <?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Acesse seus cursos e comunidades</p>
</div>

<?php
$primaryColor = !empty($branding['primary_color']) ? $branding['primary_color'] : '#e53935';
?>
<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
    <div class="stat-card" style="background: transparent; border: 2px solid <?= $primaryColor ?>; padding: 20px; border-radius: 12px; color: var(--text-primary);">
        <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">Cursos Matriculados</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px; color: <?= $primaryColor ?>;"><?= $enrolledCoursesCount ?? 0 ?></div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            <?= ($enrolledCoursesCount ?? 0) === 1 ? 'curso ativo' : 'cursos ativos' ?>
        </div>
    </div>

    <div class="stat-card" style="background: transparent; border: 2px solid <?= $primaryColor ?>; padding: 20px; border-radius: 12px; color: var(--text-primary);">
        <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">Progresso Médio</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px; color: <?= $primaryColor ?>;"><?= $averageProgress ?? 0 ?>%</div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            <?php if (($averageProgress ?? 0) >= 75): ?>
                Excelente! Continue assim 🎉
            <?php elseif (($averageProgress ?? 0) >= 50): ?>
                Bom progresso! 👍
            <?php elseif (($averageProgress ?? 0) > 0): ?>
                Continue estudando! 📚
            <?php else: ?>
                Comece seus estudos! 🚀
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card" style="background: transparent; border: 2px solid <?= $primaryColor ?>; padding: 20px; border-radius: 12px; color: var(--text-primary);">
        <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">Comunidades</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px; color: <?= $primaryColor ?>;"><?= $communitiesCount ?? 0 ?></div>
        <div style="font-size: 12px; color: var(--text-secondary);">
            <?= ($communitiesCount ?? 0) === 1 ? 'comunidade disponível' : 'comunidades disponíveis' ?>
        </div>
    </div>
</div>

<!-- Navigation Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
    <a href="/painel-externo/cursos" class="card nav-card" style="cursor: pointer; transition: transform 0.2s, border-color 0.2s; background: transparent; border: 2px solid <?= $primaryColor ?>; text-decoration: none;">
        <div style="font-size: 48px; margin-bottom: 12px;">📚</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">Cursos Disponíveis</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Veja todos os cursos que você pode acessar</p>
    </a>

    <a href="/painel-externo/meus-cursos" class="card nav-card" style="cursor: pointer; transition: transform 0.2s, border-color 0.2s; background: transparent; border: 2px solid <?= $primaryColor ?>; text-decoration: none;">
        <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">Meus Cursos</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Continue seus estudos</p>
    </a>

    <a href="/painel-externo/comunidade" class="card nav-card" style="cursor: pointer; transition: transform 0.2s, border-color 0.2s; background: transparent; border: 2px solid <?= $primaryColor ?>; text-decoration: none;">
        <div style="font-size: 48px; margin-bottom: 12px;">👥</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px; color: var(--text-primary);">Comunidade</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Participe das discussões</p>
    </a>
</div>

<?php
// Buscar cursos matriculados do usuário com detalhes
$userId = $_SESSION['user_id'] ?? 0;
if ($userId > 0) {
    $pdo = \App\Core\Database::getConnection();
    
    // Buscar cursos matriculados
    $enrolledCoursesQuery = "
        SELECT c.id, c.title, c.description, c.short_description, c.image_path
        FROM course_enrollments ce
        INNER JOIN courses c ON c.id = ce.course_id
        WHERE ce.user_id = ? AND c.is_active = 1
        ORDER BY ce.created_at DESC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($enrolledCoursesQuery);
    $stmt->execute([$userId]);
    $enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($enrolledCourses)):
?>
<!-- Meus Cursos Matriculados -->
<div style="margin-top: 32px;">
    <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">Meus Cursos</h2>
    
    <div style="display: grid; gap: 20px;">
        <?php foreach ($enrolledCourses as $course): 
            $courseId = (int)$course['id'];
            $courseTitle = htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8');
            $courseImage = !empty($course['image_path']) ? htmlspecialchars($course['image_path'], ENT_QUOTES, 'UTF-8') : '';
            $courseShortDesc = !empty($course['short_description']) ? htmlspecialchars($course['short_description'], ENT_QUOTES, 'UTF-8') : '';
            
            // Buscar módulos do curso
            $modulesQuery = "SELECT COUNT(*) as total FROM course_modules WHERE course_id = ?";
            $modulesResult = $db->query($modulesQuery, [$courseId]);
            $totalModules = $modulesResult[0]['total'] ?? 0;
            
            // Buscar aulas do curso
            $lessonsQuery = "SELECT COUNT(*) as total FROM course_lessons WHERE course_id = ?";
            $lessonsResult = $db->query($lessonsQuery, [$courseId]);
            $totalLessons = $lessonsResult[0]['total'] ?? 0;
            
            // Buscar comunidades do curso
            $communitiesQuery = "SELECT c.name FROM course_allowed_communities cac 
                                 INNER JOIN communities c ON c.id = cac.community_id 
                                 WHERE cac.course_id = ? AND c.is_active = 1";
            $courseCommunities = $db->query($communitiesQuery, [$courseId]);
            $totalCourseCommunities = count($courseCommunities);
            
            // Buscar progresso do usuário
            $progressQuery = "SELECT COUNT(DISTINCT clp.lesson_id) as completed_lessons 
                             FROM course_lesson_progress clp 
                             WHERE clp.course_id = ? AND clp.user_id = ?";
            $progressResult = $db->query($progressQuery, [$courseId, $userId]);
            $completedLessons = $progressResult[0]['completed_lessons'] ?? 0;
            $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        ?>
        
        <div class="card" style="background: transparent; border: 2px solid <?= $primaryColor ?>; padding: 20px;">
            <div style="display: flex; gap: 20px; align-items: flex-start;" class="course-card-content">
                <?php if ($courseImage): ?>
                    <div style="flex-shrink: 0; width: 140px; height: 100px; border-radius: 10px; overflow: hidden; background: rgba(0,0,0,0.3);" class="course-card-image">
                        <img src="<?= $courseImage ?>" alt="<?= $courseTitle ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                
                <div style="flex: 1; min-width: 0;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary);">
                        <?= $courseTitle ?>
                    </h3>
                    
                    <?php if ($courseShortDesc): ?>
                        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.5;">
                            <?= $courseShortDesc ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 12px;" class="course-stats">
                        <?php if ($totalModules > 0 || $totalLessons > 0): ?>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                <strong style="color: <?= $primaryColor ?>;"><?= $totalModules ?></strong> módulo<?= $totalModules != 1 ? 's' : '' ?> • 
                                <strong style="color: <?= $primaryColor ?>;"><?= $totalLessons ?></strong> aula<?= $totalLessons != 1 ? 's' : '' ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($totalCourseCommunities > 0): ?>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                <strong style="color: <?= $primaryColor ?>;"><?= $totalCourseCommunities ?></strong> comunidade<?= $totalCourseCommunities != 1 ? 's' : '' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Barra de Progresso -->
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-size: 12px; color: var(--text-secondary);">Progresso</span>
                            <span style="font-size: 12px; font-weight: 700; color: <?= $primaryColor ?>;"><?= $progressPercent ?>%</span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: <?= $primaryColor ?>; width: <?= $progressPercent ?>%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    
                    <a href="/painel-externo/curso/<?= $courseId ?>" class="btn" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; background: <?= $primaryColor ?>; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Continuar Estudando
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <?php endforeach; ?>
    </div>
    
    <?php if (count($enrolledCourses) >= 3): ?>
        <div style="text-align: center; margin-top: 16px;">
            <a href="/painel-externo/meus-cursos" style="color: <?= $primaryColor ?>; font-size: 14px; font-weight: 600; text-decoration: none;">
                Ver todos os cursos →
            </a>
        </div>
    <?php endif; ?>
</div>
<?php 
    endif;
}
?>

<style>
.nav-card:hover {
    transform: translateY(-4px);
}

.stat-card {
    transition: transform 0.2s, border-color 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: <?= $primaryColor ?> !important;
    box-shadow: 0 0 0 1px <?= $primaryColor ?>;
}

@media (max-width: 768px) {
    .course-card-content {
        flex-direction: column !important;
    }
    .course-card-image {
        width: 100% !important;
        height: 160px !important;
    }
    .course-stats {
        grid-template-columns: 1fr !important;
    }
}
</style>
