<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Personality
{
    private static function tableExists(string $table): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1");
            $stmt->execute(['t' => $table]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (bool)$row;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hasAnyUsableForUsers(): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT 1 FROM personalities WHERE active = 1 AND coming_soon = 0 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (bool)$row;
    }

    public static function allVisibleForUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE active = 1 ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allVisibleForUsersByPlan(int $planId): array
    {
        $pdo = Database::getConnection();

        // Fallback: se a migration ainda não rodou, mantém comportamento atual.
        if (!self::tableExists('personality_plans')) {
            return self::allVisibleForUsers();
        }

        // Se nenhum vínculo estiver configurado ainda, mantém comportamento atual.
        $hasAny = false;
        try {
            $stmtAny = $pdo->query('SELECT 1 FROM personality_plans LIMIT 1');
            $hasAny = (bool)$stmtAny->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAny = false;
        }
        if (!$hasAny) {
            return self::allVisibleForUsers();
        }

        $stmt = $pdo->prepare('SELECT p.*
            FROM personalities p
            INNER JOIN personality_plans pp ON pp.personality_id = p.id
            WHERE p.active = 1 AND pp.plan_id = :pid
            ORDER BY p.is_default DESC, p.name ASC');
        $stmt->execute(['pid' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getPersonalityIdsForPlan(int $planId): array
    {
        if (!self::tableExists('personality_plans')) {
            return [];
        }

        $pdo = Database::getConnection();

        // Se não existir nenhum vínculo ainda, mantém comportamento legado (sem restrição)
        $hasAny = false;
        try {
            $stmtAny = $pdo->query('SELECT 1 FROM personality_plans LIMIT 1');
            $hasAny = (bool)$stmtAny->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasAny = false;
        }
        if (!$hasAny) {
            return [];
        }

        $stmt = $pdo->prepare('SELECT personality_id FROM personality_plans WHERE plan_id = :pid');
        $stmt->execute(['pid' => $planId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = (int)($r['personality_id'] ?? 0);
        }
        $out = array_values(array_filter($out));
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    public static function setPersonalityIdsForPlan(int $planId, array $personalityIds): void
    {
        if (!self::tableExists('personality_plans')) {
            return;
        }

        $pdo = Database::getConnection();

        $normalized = [];
        foreach ($personalityIds as $pidRaw) {
            $pid = (int)$pidRaw;
            if ($pid <= 0) {
                continue;
            }
            $normalized[$pid] = true;
        }
        $personalityIds = array_keys($normalized);

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM personality_plans WHERE plan_id = :pid');
            $del->execute(['pid' => $planId]);

            if ($personalityIds) {
                $ins = $pdo->prepare('INSERT INTO personality_plans (personality_id, plan_id) VALUES (:personality_id, :plan_id)');
                foreach ($personalityIds as $personalityId) {
                    $ins->execute([
                        'personality_id' => (int)$personalityId,
                        'plan_id' => (int)$planId,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getPlanIds(int $personalityId): array
    {
        if (!self::tableExists('personality_plans')) {
            return [];
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT plan_id FROM personality_plans WHERE personality_id = :id');
        $stmt->execute(['id' => $personalityId]);
        $out = [];
        foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
            $out[] = (int)($r['plan_id'] ?? 0);
        }
        return array_values(array_filter($out));
    }

    public static function setPlanIds(int $personalityId, array $planIds): void
    {
        if (!self::tableExists('personality_plans')) {
            return;
        }

        $pdo = Database::getConnection();

        $normalized = [];
        foreach ($planIds as $pidRaw) {
            $pid = (int)$pidRaw;
            if ($pid <= 0) {
                continue;
            }
            $normalized[$pid] = true;
        }
        $planIds = array_keys($normalized);

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM personality_plans WHERE personality_id = :id');
            $del->execute(['id' => $personalityId]);

            if ($planIds) {
                $ins = $pdo->prepare('INSERT INTO personality_plans (personality_id, plan_id) VALUES (:pid, :plan)');
                foreach ($planIds as $planId) {
                    $ins->execute([
                        'pid' => $personalityId,
                        'plan' => (int)$planId,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function countForPlan(int $planId, ?int $excludePersonalityId = null): int
    {
        if (!self::tableExists('personality_plans')) {
            return 0;
        }

        $pdo = Database::getConnection();
        if ($excludePersonalityId !== null && $excludePersonalityId > 0) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM personality_plans WHERE plan_id = :pid AND personality_id <> :eid');
            $stmt->execute(['pid' => $planId, 'eid' => $excludePersonalityId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM personality_plans WHERE plan_id = :pid');
            $stmt->execute(['pid' => $planId]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE active = 1 ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities ORDER BY is_default DESC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM personalities WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findDefault(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM personalities WHERE is_default = 1 AND active = 1 ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO personalities (name, area, slug, prompt, image_path, is_default, active, coming_soon) VALUES (:name, :area, :slug, :prompt, :image_path, :is_default, :active, :coming_soon)');
        $stmt->execute([
            'name' => $data['name'],
            'area' => $data['area'],
            'slug' => $data['slug'],
            'prompt' => $data['prompt'],
            'image_path' => $data['image_path'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'coming_soon' => !empty($data['coming_soon']) ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE personalities SET name = :name, area = :area, slug = :slug, prompt = :prompt, image_path = :image_path, is_default = :is_default, active = :active, coming_soon = :coming_soon WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'area' => $data['area'],
            'slug' => $data['slug'],
            'prompt' => $data['prompt'],
            'image_path' => $data['image_path'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'coming_soon' => !empty($data['coming_soon']) ? 1 : 0,
        ]);
    }

    public static function deactivate(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE personalities SET active = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
