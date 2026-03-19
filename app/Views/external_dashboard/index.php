<div class="header">
    <h1>Bem-vindo, <?= htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Acesse seus cursos e comunidades</p>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Cursos Matriculados</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px;"><?= $enrolledCoursesCount ?? 0 ?></div>
        <div style="font-size: 12px; opacity: 0.8;">
            <?= ($enrolledCoursesCount ?? 0) === 1 ? 'curso ativo' : 'cursos ativos' ?>
        </div>
    </div>

    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 12px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Progresso Médio</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px;"><?= $averageProgress ?? 0 ?>%</div>
        <div style="font-size: 12px; opacity: 0.8;">
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

    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 12px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Comunidades</div>
        <div style="font-size: 36px; font-weight: 700; margin-bottom: 4px;"><?= $communitiesCount ?? 0 ?></div>
        <div style="font-size: 12px; opacity: 0.8;">
            <?= ($communitiesCount ?? 0) === 1 ? 'comunidade disponível' : 'comunidades disponíveis' ?>
        </div>
    </div>
</div>

<!-- Navigation Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
    <a href="/painel-externo/cursos" class="card" style="cursor: pointer; transition: transform 0.2s;">
        <div style="font-size: 48px; margin-bottom: 12px;">📚</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Cursos Disponíveis</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Veja todos os cursos que você pode acessar</p>
    </a>

    <a href="/painel-externo/meus-cursos" class="card" style="cursor: pointer; transition: transform 0.2s;">
        <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Meus Cursos</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Continue seus estudos</p>
    </a>

    <a href="/painel-externo/comunidade" class="card" style="cursor: pointer; transition: transform 0.2s;">
        <div style="font-size: 48px; margin-bottom: 12px;">👥</div>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Comunidade</h3>
        <p style="font-size: 13px; color: var(--text-secondary);">Participe das discussões</p>
    </a>
</div>

<style>
.card:hover {
    transform: translateY(-4px);
}

.stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
}
</style>
