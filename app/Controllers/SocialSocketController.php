<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class SocialSocketController extends Controller
{
    private function requireLogin(): array
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            exit;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            exit;
        }

        return $user;
    }

    public function token(): void
    {
        $user = $this->requireLogin();
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false]);
            return;
        }

        $secret = defined('\\SOCKET_IO_SECRET') ? (string)\SOCKET_IO_SECRET : '';
        if ($secret === '') {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Socket secret não configurado.']);
            return;
        }

        $exp = time() + 60 * 10; // 10 min
        $payload = $userId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, $secret);
        $token = base64_encode($payload . '|' . $sig);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'token' => $token,
            'user_id' => $userId,
            'expires_at' => $exp,
        ]);
    }
}
