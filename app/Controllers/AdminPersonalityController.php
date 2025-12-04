<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;

class AdminPersonalityController extends Controller
{
    private function ensureAdmin(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();
        $personalities = Personality::all();

        $this->view('admin/personalidades/index', [
            'pageTitle' => 'Personalidades do Tuquinha',
            'personalities' => $personalities,
        ]);
    }

    public function form(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $persona = null;
        if ($id > 0) {
            $persona = Personality::findById($id);
        }

        $this->view('admin/personalidades/form', [
            'pageTitle' => $persona ? 'Editar personalidade' : 'Nova personalidade',
            'persona' => $persona,
        ]);
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $prompt = trim($_POST['prompt'] ?? '');
        $imagePath = trim($_POST['image_path'] ?? '');
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;
        $active = !empty($_POST['active']) ? 1 : 0;

        $target = '/admin/personalidades/novo';
        if ($id > 0) {
            $target = '/admin/personalidades/editar?id=' . $id;
        }

        if ($name === '' || $area === '' || $slug === '' || $prompt === '') {
            // volta para o formulário com erro simples via sessão
            $_SESSION['admin_persona_error'] = 'Preencha nome, área, slug e prompt.';
            header('Location: ' . $target);
            exit;
        }

        // Upload opcional de nova imagem da personalidade
        if (!empty($_FILES['image_file']) && is_array($_FILES['image_file'])) {
            $uploadError = (int)($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $_SESSION['admin_persona_error'] = 'Erro ao enviar a imagem da personalidade.';
                    header('Location: ' . $target);
                    exit;
                }

                $tmp = $_FILES['image_file']['tmp_name'] ?? '';
                $originalName = (string)($_FILES['image_file']['name'] ?? 'persona-image');
                $type = (string)($_FILES['image_file']['type'] ?? '');
                $size = (int)($_FILES['image_file']['size'] ?? 0);

                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($size <= 0 || $size > $maxSize) {
                    $_SESSION['admin_persona_error'] = 'A imagem da personalidade deve ter até 2 MB.';
                    header('Location: ' . $target);
                    exit;
                }

                if (!str_starts_with($type, 'image/')) {
                    $_SESSION['admin_persona_error'] = 'Envie apenas arquivos de imagem (como JPG ou PNG) para a personalidade.';
                    header('Location: ' . $target);
                    exit;
                }

                $publicDir = __DIR__ . '/../../public/uploads/personalities';
                if (!is_dir($publicDir)) {
                    @mkdir($publicDir, 0775, true);
                }

                $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'png';
                }

                $fileName = uniqid('persona_', true) . '.' . $ext;
                $targetPath = $publicDir . '/' . $fileName;

                if (!@move_uploaded_file($tmp, $targetPath)) {
                    $_SESSION['admin_persona_error'] = 'Não foi possível salvar a imagem enviada. Tente novamente.';
                    header('Location: ' . $target);
                    exit;
                }

                // Caminho web para uso nos cards de personalidade
                $imagePath = '/public/uploads/personalities/' . $fileName;
            }
        }

        if ($isDefault) {
            $pdo = \App\Core\Database::getConnection();
            $pdo->exec('UPDATE personalities SET is_default = 0');
        }

        $data = [
            'name' => $name,
            'area' => $area,
            'slug' => $slug,
            'prompt' => $prompt,
            'image_path' => $imagePath !== '' ? $imagePath : null,
            'is_default' => $isDefault,
            'active' => $active,
        ];

        if ($id > 0) {
            Personality::update($id, $data);
        } else {
            Personality::create($data);
        }

        header('Location: /admin/personalidades');
        exit;
    }

    public function toggleActive(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $value = isset($_GET['v']) ? (int)$_GET['v'] : 0;
        if ($id > 0) {
            $persona = Personality::findById($id);
            if ($persona) {
                Personality::update($id, [
                    'name' => $persona['name'],
                    'area' => $persona['area'],
                    'slug' => $persona['slug'],
                    'prompt' => $persona['prompt'],
                    'image_path' => $persona['image_path'],
                    'is_default' => $persona['is_default'],
                    'active' => $value === 1 ? 1 : 0,
                ]);
            }
        }
        header('Location: /admin/personalidades');
        exit;
    }

    public function setDefault(): void
    {
        $this->ensureAdmin();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            // zera padrão atual
            $pdo = \App\Core\Database::getConnection();
            $pdo->exec('UPDATE personalities SET is_default = 0');

            $persona = Personality::findById($id);
            if ($persona) {
                Personality::update($id, [
                    'name' => $persona['name'],
                    'area' => $persona['area'],
                    'slug' => $persona['slug'],
                    'prompt' => $persona['prompt'],
                    'image_path' => $persona['image_path'],
                    'is_default' => 1,
                    'active' => $persona['active'],
                ]);
            }
        }
        header('Location: /admin/personalidades');
        exit;
    }
}
