<?php
/** @var array $course */
/** @var string $redirectUrl */
/** @var string $billingType */
/** @var float $amountReais */

$title = trim((string)($course['title'] ?? ''));
?>
<div style="max-width:640px; margin:0 auto; padding:16px 8px; text-align:center;">
    <h1 style="font-size:22px; margin-bottom:10px; font-weight:650;">Abrimos seu pagamento em outra aba</h1>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        Geramos um pagamento de <strong><?= number_format($amountReais, 2, ',', '.') ?> reais</strong>
        para o curso <strong><?= htmlspecialchars($title) ?></strong>
        via <?= $billingType === 'PIX' ? 'PIX' : ($billingType === 'BOLETO' ? 'boleto bancário' : 'cartão de crédito') ?>.
    </p>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:12px;">
        A janela do Tuquinha continuará aberta aqui enquanto você conclui o pagamento na outra aba.
        Assim que o pagamento for confirmado pelo banco, seu acesso ao curso será liberado automaticamente.
    </p>
    <p style="color:#b0b0b0; font-size:13px; margin-bottom:16px;">
        Se o link não abrir automaticamente, você pode clicar no botão abaixo:
    </p>
    <a href="<?= htmlspecialchars($redirectUrl) ?>" target="_blank" rel="noopener noreferrer" style="
        display:inline-flex; align-items:center; justify-content:center;
        padding:8px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60);
        color:#050509; font-size:13px; font-weight:600; text-decoration:none; margin-bottom:10px;">
        Abrir pagamento
    </a>
    <div style="margin-top:8px;">
        <a href="<?= \App\Controllers\CourseController::buildCourseUrl($course) ?>" style="font-size:13px; color:#b0b0b0; text-decoration:none; margin-right:10px;">Voltar para o curso</a>
        <a href="/conta" style="font-size:13px; color:#b0b0b0; text-decoration:none;">Ir para minha conta</a>
    </div>
</div>
<script>
    (function() {
        var url = <?= json_encode($redirectUrl) ?>;
        if (url) {
            window.open(url, '_blank');
        }
    })();
</script>
