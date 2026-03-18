<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var string|null $error */

$courseTitle = trim((string)($course['title'] ?? ''));
$priceCents = isset($course['price_cents']) ? (int)$course['price_cents'] : 0;
$price = number_format(max($priceCents, 0) / 100, 2, ',', '.');
$companyName = isset($branding) && is_array($branding) ? trim((string)($branding['company_name'] ?? '')) : '';
?>

<div class="container" style="max-width: 900px;">
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

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div class="card" style="background: rgba(99, 102, 241, 0.05); border-color: var(--accent);">
            <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Segurança
            </div>
            <p style="font-size: 0.95rem; color: var(--text-secondary);">
                Use uma senha forte e não compartilhe suas credenciais
            </p>
        </div>
        
        <div class="card" style="background: rgba(99, 102, 241, 0.05); border-color: var(--accent);">
            <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Acesso Rápido
            </div>
            <p style="font-size: 0.95rem; color: var(--text-secondary);">
                <?php if ($priceCents > 0): ?>
                    Após o pagamento, acesso imediato ao conteúdo
                <?php else: ?>
                    Acesso imediato após criar sua conta
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="card">
        <form action="/curso-externo/checkout" method="post" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
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
                <input name="name" required class="form-input" placeholder="João Silva">
            </div>
            
            <div class="form-group">
                <label class="form-label">E-mail *</label>
                <input name="email" type="email" required class="form-input" placeholder="joao@email.com">
            </div>
            
            <div class="form-group">
                <label class="form-label">Senha *</label>
                <input name="password" type="password" minlength="8" required class="form-input" placeholder="Mínimo 8 caracteres">
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

            <div class="form-group">
                <label class="form-label">Data de Nascimento *</label>
                <input name="birthdate" type="date" required class="form-input">
            </div>
            
            <div class="form-group">
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

                <div style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
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

            <div style="grid-column: 1 / -1; display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn" style="flex: 1; font-size: 1.125rem; padding: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <?php if ($priceCents > 0): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Finalizar Compra - R$ <?= $price ?>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Criar Conta Gratuita
                    <?php endif; ?>
                </button>
                <a href="/curso-externo?token=<?= urlencode($token) ?>" class="btn-outline" style="padding: 1rem 2rem; display: inline-flex; align-items: center;">
                    Voltar
                </a>
            </div>
        </form>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Já tem uma conta? <a href="/curso-externo/login?token=<?= urlencode($token) ?>" style="color: var(--accent); font-weight: 600; text-decoration: none;">Fazer Login</a>
        </p>
    </div>
</div>

<style>
    .card:has(input[type="radio"]:checked) {
        border-color: var(--accent);
        background: rgba(99, 102, 241, 0.1);
    }
</style>
