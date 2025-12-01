<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            echo 'View não encontrada';
            return;
        }

        include __DIR__ . '/../Views/layouts/main.php';
    }
}
