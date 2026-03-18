<?php

namespace App\Core;

use App\Models\User;

class ExternalUserGuard
{
    public static function check(): void
    {
        if (empty($_SESSION['user_id'])) {
            return;
        }

        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            return;
        }

        if (!empty($user['is_external_course_user'])) {
            header('Location: /painel-externo');
            exit;
        }
    }
}
