<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CommunityPost;
use App\Models\CommunityPostLike;
use App\Models\CommunityPostComment;
use App\Models\CommunityUserBlock;
use App\Models\CourseEnrollment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MailService;

class CommunityController extends Controller
{
    private function getCurrentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
            return null;
        }
        return $user;
    }

    private function resolvePlanForUser(?array $user): ?array
    {
        $plan = null;
        if ($user && !empty($user['email'])) {
            $sub = Subscription::findLastByEmail($user['email']);
            if ($sub && !empty($sub['plan_id'])) {
                $plan = Plan::findById((int)$sub['plan_id']);
            }
        }
        if (!$plan) {
            $plan = Plan::findBySessionSlug($_SESSION['plan_slug'] ?? null) ?: Plan::findBySlug('free');
        }
        return $plan;
    }

    private function ensureCommunityAccess(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $isAdmin = !empty($_SESSION['is_admin']);

        $plan = $this->resolvePlanForUser($user);

        $enrollments = CourseEnrollment::allByUser((int)$user['id']);
        $hasCourses = !empty($enrollments);

        // Admin pode acessar a comunidade sempre.
        // Para demais usuários, basta estar inscrito em pelo menos um curso.
        if (!$isAdmin && !$hasCourses) {
            $_SESSION['community_error'] = 'A comunidade é liberada apenas para alunos inscritos em pelo menos um curso.';
            header('Location: /cursos');
            exit;
        }

        $block = CommunityUserBlock::findActiveByUserId((int)$user['id']);
        if ($block) {
            $_SESSION['community_block_reason'] = (string)($block['reason'] ?? '');
        } else {
            unset($_SESSION['community_block_reason']);
        }

        return [$user, $plan, $block];
    }

    public function index(): void
    {
        [$user, $plan, $block] = $this->ensureCommunityAccess();

        $rawTag = isset($_GET['tag']) ? trim((string)$_GET['tag']) : '';
        $tag = '';
        if ($rawTag !== '') {
            $t = $rawTag[0] === '#' ? mb_substr($rawTag, 1, null, 'UTF-8') : $rawTag;
            $t = trim((string)$t);
            if ($t !== '') {
                $tag = $t;
            }
        }

        $posts = CommunityPost::latestWithAuthors(100);
        $postIds = array_map(static fn(array $p) => (int)$p['id'], $posts);

        // Coleta IDs de posts originais que foram republicados neste feed
        $repostSourceIds = [];
        foreach ($posts as $p) {
            if (!empty($p['repost_post_id'])) {
                $repostSourceIds[] = (int)$p['repost_post_id'];
            }
        }

        $originalPosts = [];
        if (!empty($repostSourceIds)) {
            $originalList = CommunityPost::findByIdsWithAuthors($repostSourceIds);
            foreach ($originalList as $op) {
                $originalPosts[(int)($op['id'] ?? 0)] = $op;
            }
        }

        $likesCount = CommunityPostLike::likesCountByPostIds($postIds);
        $commentsCount = CommunityPostComment::countsByPostIds($postIds);
        $likedByMe = CommunityPostLike::likedPostIdsByUser((int)$user['id'], $postIds);
        $comments = CommunityPostComment::allByPostIdsWithUser($postIds);

        $commentsByPost = [];
        foreach ($comments as $c) {
            $pid = (int)($c['post_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $commentsByPost[$pid][] = $c;
        }

        // Posts onde o usuário foi mencionado via @
        $mentionedPosts = [];
        $displayName = trim((string)($user['preferred_name'] ?? $user['name'] ?? ''));
        if ($displayName !== '') {
            $canonical = preg_replace('/\s+/u', ' ', $displayName);
            $canonicalLower = mb_strtolower((string)$canonical, 'UTF-8');
            if ($canonicalLower !== '') {
                foreach ($posts as $p) {
                    $body = (string)($p['body'] ?? '');
                    if ($body === '') {
                        continue;
                    }
                    if (!preg_match_all('/@([A-Za-z0-9_.\-]{3,50})/u', $body, $matches)) {
                        continue;
                    }
                    $tokens = $matches[1] ?? [];
                    $found = false;
                    foreach ($tokens as $token) {
                        $norm = preg_replace('/[._-]+/u', ' ', (string)$token);
                        $norm = trim((string)$norm);
                        if ($norm === '') {
                            continue;
                        }
                        if (mb_strtolower($norm, 'UTF-8') === $canonicalLower) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $mentionedPosts[] = $p;
                    }
                }
            }
        }

        // Posts filtrados por hashtag
        $tagPosts = [];
        if ($tag !== '') {
            $tagNeedle = '#' . mb_strtolower($tag, 'UTF-8');
            foreach ($posts as $p) {
                $body = (string)($p['body'] ?? '');
                if ($body === '') {
                    continue;
                }
                $bodyLower = mb_strtolower($body, 'UTF-8');
                if (strpos($bodyLower, $tagNeedle) !== false) {
                    $tagPosts[] = $p;
                }
            }
        }

        $success = $_SESSION['community_success'] ?? null;
        $error = $_SESSION['community_error'] ?? null;
        unset($_SESSION['community_success'], $_SESSION['community_error']);

        $this->view('community/index', [
            'pageTitle' => 'Comunidade do Tuquinha',
            'user' => $user,
            'plan' => $plan,
            'posts' => $posts,
            'likesCount' => $likesCount,
            'commentsCount' => $commentsCount,
            'likedByMe' => $likedByMe,
            'commentsByPost' => $commentsByPost,
            'originalPosts' => $originalPosts,
            'mentionedPosts' => $mentionedPosts,
            'tag' => $tag,
            'tagPosts' => $tagPosts,
            'block' => $block,
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function createPost(): void
    {
        [$user, $plan, $block] = $this->ensureCommunityAccess();

        if ($block) {
            $_SESSION['community_error'] = 'Você está bloqueado de postar na comunidade.';
            header('Location: /comunidade');
            exit;
        }

        $body = trim((string)($_POST['body'] ?? ''));

        if ($body === '') {
            $_SESSION['community_error'] = 'Escreva algo antes de postar.';
            header('Location: /comunidade');
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['community_error'] = 'O texto do post pode ter no máximo 4000 caracteres.';
            header('Location: /comunidade');
            exit;
        }

        $imagePath = null;
        $filePath = null;

        // Upload simples de imagem (opcional)
        if (!empty($_FILES['image']['name'] ?? '')) {
            $imagePath = $this->handleUpload($_FILES['image'], 'images');
        }

        // Upload simples de arquivo (opcional)
        if (!empty($_FILES['file']['name'] ?? '')) {
            $filePath = $this->handleUpload($_FILES['file'], 'files');
        }

        $postId = CommunityPost::create([
            'user_id' => (int)$user['id'],
            'body' => $body,
            'image_path' => $imagePath,
            'file_path' => $filePath,
        ]);

        $this->notifyMentions($user, $postId, $body);

        $_SESSION['community_success'] = 'Post criado com sucesso.';
        header('Location: /comunidade');
        exit;
    }

    public function like(): void
    {
        [$user, $plan, $block] = $this->ensureCommunityAccess();

        if ($block) {
            $_SESSION['community_error'] = 'Você está bloqueado de interagir na comunidade.';
            header('Location: /comunidade');
            exit;
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        if ($postId <= 0 || !CommunityPost::findById($postId)) {
            $_SESSION['community_error'] = 'Post não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        CommunityPostLike::toggle($postId, (int)$user['id']);
        header('Location: /comunidade');
        exit;
    }

    public function comment(): void
    {
        [$user, $plan, $block] = $this->ensureCommunityAccess();

        if ($block) {
            $_SESSION['community_error'] = 'Você está bloqueado de comentar na comunidade.';
            header('Location: /comunidade');
            exit;
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));
        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

        if ($postId <= 0 || !CommunityPost::findById($postId)) {
            $_SESSION['community_error'] = 'Post não encontrado para comentar.';
            header('Location: /comunidade');
            exit;
        }

        if ($body === '') {
            $_SESSION['community_error'] = 'Escreva um comentário antes de enviar.';
            header('Location: /comunidade');
            exit;
        }

        if (strlen($body) > 2000) {
            $_SESSION['community_error'] = 'O comentário pode ter no máximo 2000 caracteres.';
            header('Location: /comunidade');
            exit;
        }

        $parentCommentId = null;
        if ($parentId > 0) {
            // No momento, apenas validamos que existe; threading visual pode ser adicionado depois
            $all = CommunityPostComment::allByPostIdsWithUser([$postId]);
            foreach ($all as $c) {
                if ((int)$c['id'] === $parentId) {
                    $parentCommentId = $parentId;
                    break;
                }
            }
        }

        CommunityPostComment::create([
            'post_id' => $postId,
            'user_id' => (int)$user['id'],
            'parent_id' => $parentCommentId,
            'body' => $body,
        ]);

        $_SESSION['community_success'] = 'Comentário enviado.';
        header('Location: /comunidade');
        exit;
    }

    public function repost(): void
    {
        [$user, $plan, $block] = $this->ensureCommunityAccess();

        if ($block) {
            $_SESSION['community_error'] = 'Você está bloqueado de republicar posts na comunidade.';
            header('Location: /comunidade');
            exit;
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        $original = $postId > 0 ? CommunityPost::findById($postId) : null;
        if (!$original) {
            $_SESSION['community_error'] = 'Post original não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['community_error'] = 'O texto da republicação pode ter no máximo 4000 caracteres.';
            header('Location: /comunidade');
            exit;
        }

        CommunityPost::create([
            'user_id' => (int)$user['id'],
            'body' => $body,
            'image_path' => null,
            'file_path' => null,
            'repost_post_id' => $postId,
        ]);

        $_SESSION['community_success'] = 'Post republicado.';
        header('Location: /comunidade');
        exit;
    }

    public function editPost(): void
    {
        [$user] = $this->ensureCommunityAccess();
        $isAdmin = !empty($_SESSION['is_admin']);

        $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $body = trim((string)($_POST['body'] ?? ''));

        $post = $id > 0 ? CommunityPost::findById($id) : null;
        if (!$post) {
            $_SESSION['community_error'] = 'Post não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        if (!$isAdmin && (int)$post['user_id'] !== (int)$user['id']) {
            $_SESSION['community_error'] = 'Você não pode editar este post.';
            header('Location: /comunidade');
            exit;
        }

        if (strlen($body) > 4000) {
            $_SESSION['community_error'] = 'O texto do post pode ter no máximo 4000 caracteres.';
            header('Location: /comunidade');
            exit;
        }

        CommunityPost::updateBody($id, $body);
        $_SESSION['community_success'] = 'Post atualizado.';
        header('Location: /comunidade');
        exit;
    }

    public function deletePost(): void
    {
        [$user] = $this->ensureCommunityAccess();
        $isAdmin = !empty($_SESSION['is_admin']);

        $id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $post = $id > 0 ? CommunityPost::findById($id) : null;
        if (!$post) {
            $_SESSION['community_error'] = 'Post não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        if (!$isAdmin && (int)$post['user_id'] !== (int)$user['id']) {
            $_SESSION['community_error'] = 'Você não pode excluir este post.';
            header('Location: /comunidade');
            exit;
        }

        CommunityPost::softDelete($id);
        $_SESSION['community_success'] = 'Post removido.';
        header('Location: /comunidade');
        exit;
    }

    public function blockUser(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /login');
            exit;
        }

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($userId <= 0 || $reason === '') {
            $_SESSION['community_error'] = 'Informe um motivo para o bloqueio.';
            header('Location: /comunidade');
            exit;
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            $_SESSION['community_error'] = 'Usuário não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        CommunityUserBlock::create([
            'user_id' => $userId,
            'reason' => $reason,
            'blocked_by' => (int)$_SESSION['user_id'],
        ]);

        if (!empty($targetUser['email'])) {
            $safeName = htmlspecialchars($targetUser['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeReason = nl2br(htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $subject = 'Você foi bloqueado na comunidade do Tuquinha';
            $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Seu acesso para publicar, curtir e comentar na comunidade do Tuquinha foi bloqueado temporariamente.</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Motivo informado pela moderação:</p>
      <p style="font-size:13px; margin:0 0 10px 0; color:#ffb74d;">{$safeReason}</p>
      <p style="font-size:12px; color:#b0b0b0; margin:0;">Se você acredita que isso foi um engano, responda este e-mail explicando o contexto para que possamos revisar o caso.</p>
    </div>
  </div>
</body>
</html>
HTML;
            try {
                MailService::send($targetUser['email'], $targetUser['name'] ?? '', $subject, $body);
            } catch (\Throwable $e) {
            }
        }

        $_SESSION['community_success'] = 'Usuário bloqueado na comunidade.';
        header('Location: /comunidade');
        exit;
    }

    public function unblockUser(): void
    {
        if (empty($_SESSION['is_admin'])) {
            header('Location: /login');
            exit;
        }

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($userId <= 0) {
            $_SESSION['community_error'] = 'Usuário inválido para desbloquear.';
            header('Location: /comunidade');
            exit;
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            $_SESSION['community_error'] = 'Usuário não encontrado.';
            header('Location: /comunidade');
            exit;
        }

        CommunityUserBlock::unblock($userId, (int)$_SESSION['user_id']);

        if (!empty($targetUser['email'])) {
            $safeName = htmlspecialchars($targetUser['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $subject = 'Seu acesso à comunidade do Tuquinha foi restaurado';
            $body = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;">Seu acesso para participar da comunidade do Tuquinha foi restaurado. Bora seguir construindo um espaço seguro e útil para todo mundo.</p>
    </div>
  </div>
</body>
</html>
HTML;
            try {
                MailService::send($targetUser['email'], $targetUser['name'] ?? '', $subject, $body);
            } catch (\Throwable $e) {
            }
        }

        $_SESSION['community_success'] = 'Usuário desbloqueado na comunidade.';
        header('Location: /comunidade');
        exit;
    }

    private function notifyMentions(array $author, int $postId, string $body): void
    {
        $body = trim($body);
        if ($body === '') {
            return;
        }

        if (!preg_match_all('/@([A-Za-z0-9_.\-]{3,50})/u', $body, $matches)) {
            return;
        }

        $authorId = (int)($author['id'] ?? 0);
        $authorName = trim((string)($author['preferred_name'] ?? $author['name'] ?? ''));
        if ($authorName === '') {
            $authorName = 'alguém da comunidade';
        }

        $tokens = array_unique($matches[1] ?? []);
        if (empty($tokens)) {
            return;
        }

        $notified = [];
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . '://' . $host . '/comunidade#post-' . $postId;

        foreach ($tokens as $token) {
            $mentioned = User::findByMentionName((string)$token);
            if (!$mentioned) {
                continue;
            }
            $mentionedId = (int)($mentioned['id'] ?? 0);
            if ($mentionedId <= 0 || $mentionedId === $authorId) {
                continue;
            }
            if (!empty($notified[$mentionedId])) {
                continue;
            }

            $email = trim((string)($mentioned['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $safeName = htmlspecialchars($mentioned['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeAuthor = htmlspecialchars($authorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $snippet = mb_substr($body, 0, 300, 'UTF-8');
            if (mb_strlen($body, 'UTF-8') > 300) {
                $snippet .= '...';
            }
            $safeSnippet = nl2br(htmlspecialchars($snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

            $subject = 'Você foi mencionado na comunidade do Tuquinha';
            $html = <<<HTML
<html>
<body style="margin:0; padding:0; background:#050509; font-family:system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color:#f5f5f5;">
  <div style="width:100%; padding:24px 0;">
    <div style="max-width:520px; margin:0 auto; background:#111118; border-radius:16px; border:1px solid #272727; padding:18px 20px;">
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:radial-gradient(circle at 30% 20%, #fff 0, #ff8a65 25%, #e53935 65%, #050509 100%); text-align:center; font-weight:700; font-size:16px; color:#050509;">T</div>
        <div>
          <div style="font-weight:700; font-size:15px;">Agente IA - Tuquinha</div>
          <div style="font-size:11px; color:#b0b0b0;">Branding vivo na veia</div>
        </div>
      </div>

      <p style="font-size:14px; margin:0 0 10px 0;">Oi, {$safeName} 👋</p>
      <p style="font-size:14px; margin:0 0 10px 0;"><strong>{$safeAuthor}</strong> te mencionou em um post na Comunidade do Tuquinha.</p>
      <p style="font-size:13px; margin:0 0 10px 0; color:#b0b0b0;">Trecho do post:</p>
      <div style="font-size:13px; margin:0 0 12px 0; padding:8px 10px; border-radius:10px; border:1px solid #272727; background:#050509;">{$safeSnippet}</div>
      <p style="margin:0; font-size:13px;"><a href="{$link}" style="color:#ff6f60; text-decoration:none;">👉 Ver post completo na comunidade</a></p>
    </div>
  </div>
</body>
</html>
HTML;

            try {
                MailService::send($email, $mentioned['name'] ?? '', $subject, $html);
            } catch (\Throwable $e) {
            }

            $notified[$mentionedId] = true;
        }
    }

    private function handleUpload(array $file, string $type): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $name = (string)($file['name'] ?? '');
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($name === '' || $tmp === '') {
            return null;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $safeExt = preg_replace('/[^a-z0-9]+/', '', $ext);
        if ($safeExt === '') {
            $safeExt = 'bin';
        }

        $dir = __DIR__ . '/../../public/uploads/community/' . $type;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $basename = bin2hex(random_bytes(8));
        $target = $dir . '/' . $basename . '.' . $safeExt;
        if (!@move_uploaded_file($tmp, $target)) {
            return null;
        }

        $publicPath = '/public/uploads/community/' . $type . '/' . $basename . '.' . $safeExt;
        return $publicPath;
    }
}
