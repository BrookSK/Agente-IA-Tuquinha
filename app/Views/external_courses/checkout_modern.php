<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */
/** @var array $prefilledData */

$courseTitle = trim((string)($course['title'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';

$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$formAction = '/';
$backHref = '/';
$loginHref = '/login';

if ($slug !== '') {
    $formAction = '/curso/' . urlencode($slug) . '/checkout';
    $backHref = '/curso/' . urlencode($slug);
    $loginHref = '/curso/' . urlencode($slug) . '/login';
}

// Dados pré-preenchidos do formulário de registro ou usuário logado
$prefilledData = isset($prefilledData) && is_array($prefilledData) ? $prefilledData : [];
$prefilledName = '';

// Se veio do formulário de registro (tem first_name e last_name)
if (!empty($prefilledData['first_name']) || !empty($prefilledData['last_name'])) {
    $prefilledName = trim((string)($prefilledData['first_name'] ?? '') . ' ' . (string)($prefilledData['last_name'] ?? ''));
} elseif (!empty($prefilledData['name'])) {
    // Se veio do usuário logado (tem name completo)
    $prefilledName = trim((string)$prefilledData['name']);
}

$prefilledEmail = trim((string)($prefilledData['email'] ?? ''));
$prefilledPassword = (string)($prefilledData['password'] ?? '');
?>

<div class="container" style="max-width: 900px; background: transparent !important;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 0.5rem;">
            <?php if ($priceCents > 0): ?>
                Finalize sua Compra
            <?php else: ?>
                Crie sua Conta Gratuita
            <?php endif; ?>
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.125rem;">
            <?php if ($priceCents > 0): ?>
                Você está adquirindo: <strong style="color: var(--text-primary);"><?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php else: ?>
                Comece sua jornada de aprendizado hoje mesmo
            <?php endif; ?>
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message" style="max-width: 600px; margin: 0 auto 2rem;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card course-summary-card" style="background: rgba(99, 102, 241, 0.05); border-color: var(--accent); margin-bottom: 2rem;">
        <div style="display: flex; gap: 1.5rem; align-items: flex-start;" class="course-summary-content">
            <?php 
            $courseImage = !empty($course['image_path']) ? trim((string)$course['image_path']) : '';
            $courseDescription = !empty($course['description']) ? trim((string)$course['description']) : '';
            $courseShortDescription = !empty($course['short_description']) ? trim((string)$course['short_description']) : '';
            $courseWorkload = !empty($course['certificate_workload_hours']) ? (int)$course['certificate_workload_hours'] : 0;
            $courseSyllabus = !empty($course['certificate_syllabus']) ? trim((string)$course['certificate_syllabus']) : '';
            
            // Usar dados dinâmicos passados pelo controller
            $totalModules = isset($courseDetails['totalModules']) ? (int)$courseDetails['totalModules'] : 0;
            $totalLessons = isset($courseDetails['totalLessons']) ? (int)$courseDetails['totalLessons'] : 0;
            $communities = isset($courseDetails['communities']) ? $courseDetails['communities'] : [];
            ?>
            
            <?php if ($courseImage): ?>
                <div style="flex-shrink: 0; width: 180px; height: 120px; border-radius: 12px; overflow: hidden; background: rgba(0,0,0,0.3);" class="course-image">
                    <img src="<?= htmlspecialchars($courseImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0;">
                        <?= htmlspecialchars($courseTitle, ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <?php if ($totalModules > 0 || $totalLessons > 0 || $courseWorkload > 0): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--accent); font-weight: 600; white-space: nowrap;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php if ($courseWorkload > 0): ?>
                                <?= $courseWorkload ?>h
                            <?php endif; ?>
                            <?php if (($totalModules > 0 || $totalLessons > 0) && $courseWorkload > 0): ?>
                                •
                            <?php endif; ?>
                            <?php if ($totalModules > 0 || $totalLessons > 0): ?>
                                <?= $totalModules ?> módulos • <?= $totalLessons ?> aulas
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($courseShortDescription): ?>
                    <div style="margin-bottom: 1.25rem;">
                        <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; font-weight: 500;">
                            <?= htmlspecialchars($courseShortDescription, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($courseDescription): ?>
                    <div style="margin-bottom: 1.25rem;">
                        <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); font-weight: 700; margin-bottom: 0.5rem;">
                            Descrição
                        </h4>
                        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($courseDescription, ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;" class="course-details-grid">
                    <div>
                        <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                            Conteúdo Programático
                        </h4>
                        <?php if ($courseSyllabus): ?>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.8;">
                                <?= nl2br(htmlspecialchars($courseSyllabus, ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                <?php if ($totalModules > 0 && $totalLessons > 0): ?>
                                    <?= $totalModules ?> módulo<?= $totalModules > 1 ? 's' : '' ?> com <?= $totalLessons ?> aula<?= $totalLessons > 1 ? 's' : '' ?>
                                <?php else: ?>
                                    Acesso completo a todos os módulos e aulas do curso
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Comunidades
                        </h4>
                        <?php if (!empty($communities)): ?>
                            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                                Acesso a <?= count($communities) ?> comunidade<?= count($communities) > 1 ? 's' : '' ?>:
                            </p>
                            <ul style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; padding-left: 1.25rem; line-height: 1.8;">
                                <?php foreach ($communities as $community): ?>
                                    <li><?= htmlspecialchars($community['name'], ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="font-size: 0.9rem; color: var(--text-secondary);">
                                Acesso às comunidades exclusivas do curso
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="<?= $formAction ?>" method="post" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;" class="checkout-form">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <div style="grid-column: 1 / -1;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Informações da Conta
                </h2>
            </div>

            <div class="form-group">
                <label class="form-label">Nome Completo *</label>
                <input name="name" required class="form-input" placeholder="João Silva" value="<?= htmlspecialchars($prefilledName, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">E-mail *</label>
                <input name="email" type="email" required class="form-input" placeholder="joao@email.com" value="<?= htmlspecialchars($prefilledEmail, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Senha *</label>
                <input name="password" type="password" minlength="8" required class="form-input" placeholder="Mínimo 8 caracteres" value="<?= htmlspecialchars($prefilledPassword, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-hint">Escolha uma senha forte com letras, números e símbolos</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">CPF *</label>
                <input name="cpf" required class="form-input" placeholder="000.000.000-00">
            </div>

            <div style="grid-column: 1 / -1; margin-top: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Dados Pessoais
                </h2>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Data de Nascimento *</label>
                <input name="birthdate" type="date" required class="form-input" style="max-width: 100%;">
            </div>
            
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Telefone</label>
                <input name="phone" class="form-input" placeholder="(00) 00000-0000">
            </div>

            <div style="grid-column: 1 / -1; margin-top: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    Endereço
                </h2>
            </div>

            <div class="form-group">
                <label class="form-label">CEP *</label>
                <input name="postal_code" required class="form-input" placeholder="00000-000">
            </div>
            
            <div class="form-group">
                <label class="form-label">Endereço *</label>
                <input name="address" required class="form-input" placeholder="Rua, Avenida, etc">
            </div>
            
            <div class="form-group">
                <label class="form-label">Número *</label>
                <input name="address_number" required class="form-input" placeholder="123">
            </div>
            
            <div class="form-group">
                <label class="form-label">Complemento</label>
                <input name="complement" class="form-input" placeholder="Apto, Bloco, etc">
            </div>
            
            <div class="form-group">
                <label class="form-label">Bairro *</label>
                <input name="province" required class="form-input" placeholder="Centro">
            </div>
            
            <div class="form-group">
                <label class="form-label">Cidade *</label>
                <input name="city" required class="form-input" placeholder="São Paulo">
            </div>
            
            <div class="form-group">
                <label class="form-label">Estado (UF) *</label>
                <input name="state" maxlength="2" required class="form-input" placeholder="SP" style="text-transform:uppercase;">
            </div>

            <?php if ($priceCents > 0): ?>
                <div style="grid-column: 1 / -1; margin-top: 1rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--accent); display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Forma de Pagamento
                    </h2>
                </div>

                <div class="billing-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px;">
                    <label class="card" style="cursor: pointer; padding: 1.25rem; transition: all 0.2s;">
                        <input type="radio" name="billing_type" value="PIX" checked style="display: none;">
                        <div style="text-align: center;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 0.5rem;">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                                <line x1="12" y1="18" x2="12.01" y2="18"></line>
                            </svg>
                            <div style="font-weight: 700; margin-bottom: 0.25rem;">PIX</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Aprovação rápida</div>
                        </div>
                    </label>
                    
                    <label class="card" style="cursor: pointer; padding: 1.25rem; transition: all 0.2s;">
                        <input type="radio" name="billing_type" value="BOLETO" style="display: none;">
                        <div style="text-align: center;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 0.5rem;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <div style="font-weight: 700; margin-bottom: 0.25rem;">Boleto</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Até 3 dias úteis</div>
                        </div>
                    </label>
                    
                    <label class="card" style="cursor: pointer; padding: 1.25rem; transition: all 0.2s;">
                        <input type="radio" name="billing_type" value="CREDIT_CARD" style="display: none;">
                        <div style="text-align: center;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 0.5rem;">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <div style="font-weight: 700; margin-bottom: 0.25rem;">Cartão</div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">Crédito</div>
                        </div>
                    </label>
                </div>
            <?php endif; ?>

            <div style="grid-column: 1 / -1; display: flex; flex-direction: column; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn" style="width: 100%; padding: 1rem 2rem; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                    <?php if ($priceCents > 0): ?>
                        Finalizar Compra
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Criar Conta Gratuita
                    <?php endif; ?>
                </button>
                <a href="<?= $backHref ?>" style="width: 100%; padding: 1rem 2rem; display: flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-secondary); background: transparent; border: none;">
                    Voltar
                </a>
            </div>
        </form>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Já tem uma conta? <a href="<?= $loginHref ?>" style="color: var(--accent); font-weight: 600; text-decoration: none;">Fazer Login</a>
        </p>
    </div>
</div>

<style>
    .card:has(input[type="radio"]:checked) {
        border-color: var(--accent);
        background: rgba(99, 102, 241, 0.1);
    }
    
    @media (max-width: 768px) {
        .course-summary-content { flex-direction: column !important; }
        .course-image { width: 100% !important; height: 200px !important; }
        .course-details-grid { grid-template-columns: 1fr !important; gap: 1rem !important; }
        .checkout-form { grid-template-columns: 1fr !important; gap: 1rem !important; }
        .billing-grid { grid-template-columns: 1fr !important; }
    }
    
    @media (max-width: 640px) {
        .container { padding: 0 0.75rem !important; }
        h1 { font-size: 1.5rem !important; line-height: 1.25; }
        h2 { font-size: 1.1rem !important; }
        .card { padding: 1rem !important; }
        .course-summary-card h3 { font-size: 1.25rem !important; }
        .course-image { height: 160px !important; }
        .checkout-form { gap: 0.875rem !important; }
    }
</style>
