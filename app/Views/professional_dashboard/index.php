<?php
/** @var array $metrics */
?>
<div style="max-width: 1200px; margin: 0 auto;">
    <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;">Painel do Profissional</h1>
    <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 30px;">Gerencie seus cursos, alunos e vendas</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 20px;">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">Total de Alunos</div>
            <div style="font-size: 32px; font-weight: 800; color: var(--accent);">
                <?= number_format((int)($metrics['total_students'] ?? 0)) ?>
            </div>
        </div>

        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 20px;">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">Cursos Ativos</div>
            <div style="font-size: 32px; font-weight: 800; color: #6be28d;">
                <?= number_format((int)($metrics['active_courses'] ?? 0)) ?>
            </div>
        </div>

        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 20px;">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">Total de Vendas</div>
            <div style="font-size: 32px; font-weight: 800; color: #ffcc80;">
                <?= number_format((int)($metrics['total_sales'] ?? 0)) ?>
            </div>
        </div>

        <div style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 20px;">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 6px;">Receita Total</div>
            <div style="font-size: 32px; font-weight: 800; color: #6be28d;">
                R$ <?= number_format((int)($metrics['total_revenue_cents'] ?? 0) / 100, 2, ',', '.') ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <a href="/profissional/cursos" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;">
            <div style="font-size: 40px; margin-bottom: 12px;">📚</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Meus Cursos</h3>
            <p style="font-size: 13px; color: var(--text-secondary);">Gerencie seus cursos e conteúdos</p>
        </a>

        <a href="/profissional/alunos" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;">
            <div style="font-size: 40px; margin-bottom: 12px;">👨‍🎓</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Alunos</h3>
            <p style="font-size: 13px; color: var(--text-secondary);">Veja todos os seus alunos</p>
        </a>

        <a href="/profissional/vendas" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;">
            <div style="font-size: 40px; margin-bottom: 12px;">💰</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Vendas</h3>
            <p style="font-size: 13px; color: var(--text-secondary);">Acompanhe suas vendas</p>
        </a>

        <a href="/profissional/comunidades" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;">
            <div style="font-size: 40px; margin-bottom: 12px;">👥</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Comunidades</h3>
            <p style="font-size: 13px; color: var(--text-secondary);">Gerencie suas comunidades</p>
        </a>

        <a href="/profissional/configuracoes" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 14px; padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;">
            <div style="font-size: 40px; margin-bottom: 12px;">⚙️</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">Configurações</h3>
            <p style="font-size: 13px; color: var(--text-secondary);">Personalize seu branding</p>
        </a>
    </div>
</div>

<style>
a:hover {
    transform: translateY(-4px);
}
</style>
