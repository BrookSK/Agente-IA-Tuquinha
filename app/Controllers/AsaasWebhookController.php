<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\TokenTransaction;
use App\Models\TokenTopup;
use App\Models\CoursePurchase;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\SubscriptionPayment;
use App\Services\MailService;

class AsaasWebhookController extends Controller
{
    public function handle(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        if (!is_array($data) || empty($data['event']) || empty($data['payment'])) {
            http_response_code(400);
            echo 'invalid payload';
            return;
        }

        $event = (string)$data['event'];
        $payment = (array)$data['payment'];

        // Considera eventos de confirmação de pagamento/renovação
        $isPaidEvent = in_array($event, [
            'PAYMENT_RECEIVED',
            'PAYMENT_CONFIRMED',
        ], true);

        if (!$isPaidEvent) {
            http_response_code(200);
            echo 'ignored';
            return;
        }

        // 1) Tratamento de recargas de tokens (pagamentos avulsos sem subscription)
        $externalRef = isset($payment['externalReference']) ? (string)$payment['externalReference'] : '';
        $asaasSubscriptionId = isset($payment['subscription']) ? (string)$payment['subscription'] : '';

        if ($externalRef !== '' && str_starts_with($externalRef, 'token_topup:')) {
            $parts = explode(':', $externalRef, 2);
            $topupId = isset($parts[1]) ? (int)$parts[1] : 0;

            if ($topupId > 0) {
                $topup = TokenTopup::findByAsaasPaymentId((string)($payment['id'] ?? ''));
                if (!$topup) {
                    // fallback: se ainda não tiver o payment_id salvo, tenta só pelo ID interno
                    $topup = ['id' => $topupId] + ['user_id' => null, 'tokens' => null];
                }

                if (!empty($topup['user_id']) && !empty($topup['tokens'])) {
                    $userId = (int)$topup['user_id'];
                    $tokens = (int)$topup['tokens'];

                    // Credita tokens extras no usuário
                    User::creditTokens($userId, $tokens, 'token_topup', [
                        'asaas_payment_id' => (string)($payment['id'] ?? ''),
                        'event' => $event,
                    ]);

                    // Marca a recarga como paga
                    TokenTopup::markPaid((int)$topup['id']);
                }
            }

            http_response_code(200);
            echo 'ok';
            return;
        }

        // 2) Tratamento de compras avulsas de cursos
        if ($externalRef !== '' && str_starts_with($externalRef, 'course_purchase:')) {
            $parts = explode(':', $externalRef, 2);
            $purchaseId = isset($parts[1]) ? (int)$parts[1] : 0;

            $purchase = null;
            if (!empty($payment['id'])) {
                $purchase = CoursePurchase::findByAsaasPaymentId((string)$payment['id']);
            }
            if (!$purchase && $purchaseId > 0) {
                $purchase = CoursePurchase::findById($purchaseId);
            }

            if ($purchase && !empty($purchase['user_id']) && !empty($purchase['course_id'])) {
                $userId = (int)$purchase['user_id'];
                $courseId = (int)$purchase['course_id'];

                $user = User::findById($userId);
                $course = Course::findById($courseId);

                if ($user && $course) {
                    // Garante inscrição no curso
                    CourseEnrollment::enroll($courseId, $userId);
                    CoursePurchase::markPaid((int)$purchase['id']);

                    // Tenta enviar e-mail de confirmação de inscrição
                    try {
                        if (!empty($user['email'])) {
                            $subject = 'Inscrição confirmada no curso: ' . (string)($course['title'] ?? 'Curso do Tuquinha');

                            $coursePath = '/cursos/ver';
                            if (!empty($course['slug'])) {
                                $coursePath .= '?slug=' . urlencode((string)$course['slug']);
                            } else {
                                $coursePath .= '?id=' . (int)($course['id'] ?? 0);
                            }

                            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            $courseUrl = $scheme . $host . $coursePath;

                            $safeName = htmlspecialchars($user['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeCourseTitle = htmlspecialchars($course['title'] ?? 'Curso do Tuquinha', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $safeCourseUrl = htmlspecialchars($courseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $logoUrl = $scheme . $host . '/public/favicon.png';
                            $safeLogoUrl = htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                            $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; border-radius:50%; overflow:hidden; background:#050509; box-shadow:0 0 18px rgba(229,57,53,0.8);"><img src="{$safeLogoUrl}" alt="Tuquinha" style="width:100%; height:100%; display:block; object-fit:cover;"></div>
        <div>
          <div style="font-weight:700; font-size:15px;">Resenha 2.0</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Seu pagamento foi confirmado e sua inscrição no curso <strong>{$safeCourseTitle}</strong> está liberada.</p>

      <div style="text-align:center; margin:14px 0 8px 0;">
        <a href="{$safeCourseUrl}" style="display:inline-block; padding:9px 18px; border-radius:999px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:600; font-size:13px; text-decoration:none;">Acessar curso</a>
      </div>

      <p style="font-size:12px; color:#777; margin:8px 0 0 0;">Se o botão não funcionar, copie e cole este link no navegador:<br>
        <a href="{$safeCourseUrl}" style="color:#ff6f60; text-decoration:none;">{$safeCourseUrl}</a>
      </p>
    </div>
  </div>
</body>
</html>
HTML;

                            MailService::send($user['email'], $user['name'] ?? '', $subject, $body);
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }

            http_response_code(200);
            echo 'ok';
            return;
        }

        // 3) Fluxo padrão de assinatura (subscription)
        if ($asaasSubscriptionId === '') {
            http_response_code(200);
            echo 'ignored';
            return;
        }
        $subscription = Subscription::findByAsaasId($asaasSubscriptionId);
        if (!$subscription) {
            http_response_code(200);
            echo 'subscription not found';
            return;
        }

        $plan = Plan::findById((int)$subscription['plan_id']);
        if (!$plan) {
            http_response_code(200);
            echo 'plan not found';
            return;
        }

        // Registra este pagamento da assinatura para relatórios financeiros
        // (necessário para filtrar receita por mês/ano/semestre)
        try {
            $value = 0;
            if (isset($payment['value'])) {
                $value = (float)$payment['value'];
            }
            $paidAt = null;
            if (!empty($payment['paymentDate'])) {
                $paidAt = (string)$payment['paymentDate'];
            } elseif (!empty($payment['confirmedDate'])) {
                $paidAt = (string)$payment['confirmedDate'];
            } elseif (!empty($payment['dateCreated'])) {
                $paidAt = (string)$payment['dateCreated'];
            }

            if ($paidAt) {
                // Asaas normalmente manda ISO; strtotime resolve
                $paidAt = date('Y-m-d H:i:s', strtotime($paidAt));
            } else {
                $paidAt = date('Y-m-d H:i:s');
            }

            SubscriptionPayment::upsertPaid([
                'subscription_id' => (int)$subscription['id'],
                'plan_id' => (int)$plan['id'],
                'amount_cents' => (int)round($value * 100),
                'asaas_payment_id' => (string)($payment['id'] ?? ''),
                'billing_type' => (string)($payment['billingType'] ?? ''),
                'paid_at' => $paidAt,
            ]);
        } catch (\Throwable $e) {
        }

        // Garante que esta assinatura fique como ativa e cancela outras ativas do mesmo e-mail
        Subscription::updateStatusAndCanceledAt((int)$subscription['id'], 'active', null);
        if (!empty($subscription['customer_email'])) {
            Subscription::cancelOtherActivesForEmail((string)$subscription['customer_email'], (int)$subscription['id']);
        }

        $monthlyLimit = isset($plan['monthly_token_limit']) ? (int)$plan['monthly_token_limit'] : 0;
        if ($monthlyLimit < 0) {
            $monthlyLimit = 0;
        }

        // Localiza o usuário pelo e-mail da assinatura
        $user = User::findByEmail($subscription['customer_email']);
        if (!$user) {
            http_response_code(200);
            echo 'user not found';
            return;
        }

        $userId = (int)$user['id'];

        // Reseta saldo de tokens para o limite do plano
        User::resetTokenBalanceForPlan($userId, $monthlyLimit);

        if ($monthlyLimit > 0) {
            TokenTransaction::create([
                'user_id' => $userId,
                'amount' => $monthlyLimit,
                'reason' => 'plan_monthly_reset',
                'meta' => json_encode([
                    'plan_id' => (int)$subscription['plan_id'],
                    'asaas_subscription_id' => $asaasSubscriptionId,
                    'event' => $event,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        http_response_code(200);
        echo 'ok';
    }
}
