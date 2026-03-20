<?php
/** @var array $course */
/** @var array|null $branding */
/** @var string $token */
/** @var int $purchaseId */
/** @var string $billingType */
/** @var float $amountReais */
/** @var string|null $paymentUrl */
?>

<?php
$isPartnerSite = !empty($isPartnerSite);
$slug = isset($slug) ? trim((string)$slug) : '';

$statusEndpoint = '/status-pagamento';
$dashboardHref = '/painel-externo/meus-cursos';
?>
<div style="max-width: 560px; margin: 0 auto; text-align: center;">
    <div style="margin-bottom: 20px;">
        <div style="width: 80px; height: 80px; margin: 0 auto 16px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; font-size: 40px;">
            ⏳
        </div>
        <h1 style="font-size: 24px; font-weight: 800; margin: 0 0 8px 0;">Aguardando Pagamento</h1>
        <p style="font-size: 14px; color: var(--text-secondary); margin: 0;">
            Estamos verificando o status do seu pagamento...
        </p>
    </div>

    <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 14px; padding: 20px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 13px; color: var(--text-secondary);">Valor:</span>
            <span style="font-size: 18px; font-weight: 700;">R$ <?= number_format($amountReais, 2, ',', '.') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="font-size: 13px; color: var(--text-secondary);">Forma de pagamento:</span>
            <span style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($billingType, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 13px; color: var(--text-secondary);">Status:</span>
            <span id="paymentStatus" style="font-size: 14px; font-weight: 600; color: #ffcc80;">Pendente</span>
        </div>
    </div>

    <?php if ($paymentUrl): ?>
    <div style="margin-bottom: 20px;">
        <a href="<?= htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn" style="display: inline-block; text-decoration: none;">
            Abrir página de pagamento
        </a>
        <p style="font-size: 12px; color: var(--text-secondary); margin-top: 10px;">
            Clique no botão acima para realizar o pagamento. Esta página será atualizada automaticamente quando o pagamento for confirmado.
        </p>
    </div>
    <?php endif; ?>

    <div id="loadingIndicator" style="margin: 20px 0;">
        <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--accent); border-radius: 50%; animation: spin 1s linear infinite;"></div>
    </div>

    <div id="successMessage" style="display: none; margin: 20px 0;">
        <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
        <p style="font-size: 16px; font-weight: 600; color: #6be28d; margin: 0;">Pagamento confirmado!</p>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 8px;">Redirecionando para seus cursos...</p>
    </div>

    <p style="font-size: 12px; color: #777; margin-top: 20px;">
        Caso o pagamento já tenha sido realizado e esta página não atualize, 
        <a href="<?= $dashboardHref ?>" style="color: var(--accent); text-decoration: underline;">clique aqui</a>.
    </p>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    var purchaseId = <?= (int)$purchaseId ?>;
    var checkInterval = null;
    var attempts = 0;
    var maxAttempts = 200;

    function checkPaymentStatus() {
        attempts++;
        
        if (attempts > maxAttempts) {
            clearInterval(checkInterval);
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('paymentStatus').textContent = 'Tempo esgotado';
            document.getElementById('paymentStatus').style.color = '#ff8a80';
            return;
        }

        fetch('<?= $statusEndpoint ?>?purchase_id=' + purchaseId)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.status === 'paid') {
                    clearInterval(checkInterval);
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('paymentStatus').textContent = 'Confirmado';
                    document.getElementById('paymentStatus').style.color = '#6be28d';
                    document.getElementById('successMessage').style.display = 'block';
                    
                    setTimeout(function() {
                        window.location.href = data.redirect || '/painel-externo/meus-cursos';
                    }, 2000);
                }
            })
            .catch(function(error) {
                console.error('Erro ao verificar status:', error);
            });
    }

    checkInterval = setInterval(checkPaymentStatus, 3000);
    checkPaymentStatus();
})();
</script>
