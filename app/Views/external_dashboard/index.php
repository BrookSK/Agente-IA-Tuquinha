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
</style>
