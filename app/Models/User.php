<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findAdminByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND is_admin = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByMentionName(string $mention): ?array
    {
        $mention = trim($mention);
        if ($mention === '') {
            return null;
        }

        // Permite usar @nome, @nome.sobrenome, @nome_sobrenome, etc. convertendo para espaços
        $normalized = preg_replace('/[._-]+/u', ' ', $mention);
        $normalized = trim((string)$normalized);
        if ($normalized === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users
            WHERE preferred_name = :name OR name = :name
            ORDER BY created_at ASC
            LIMIT 1');
        $stmt->execute(['name' => $normalized]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createUser(string $name, string $email, string $passwordHash): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, is_admin) VALUES (:name, :email, :password_hash, 0)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateName(int $id, string $name): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id LIMIT 1');
        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);
    }

    public static function updateProfile(int $id, string $name, ?string $preferredName, ?string $globalMemory, ?string $globalInstructions, ?int $defaultPersonaId = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET name = :name, preferred_name = :preferred_name, global_memory = :global_memory, global_instructions = :global_instructions, default_persona_id = :default_persona_id WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'preferred_name' => $preferredName !== '' ? $preferredName : null,
            'global_memory' => $globalMemory !== '' ? $globalMemory : null,
            'global_instructions' => $globalInstructions !== '' ? $globalInstructions : null,
            'default_persona_id' => $defaultPersonaId && $defaultPersonaId > 0 ? $defaultPersonaId : null,
        ]);
    }

    public static function updateBillingData(
        int $id,
        ?string $cpf,
        ?string $birthdate,
        ?string $phone,
        ?string $postalCode,
        ?string $address,
        ?string $addressNumber,
        ?string $complement,
        ?string $province,
        ?string $city,
        ?string $state
    ): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET
            billing_cpf = :cpf,
            billing_birthdate = :birthdate,
            billing_phone = :phone,
            billing_postal_code = :postal_code,
            billing_address = :address,
            billing_address_number = :address_number,
            billing_complement = :complement,
            billing_province = :province,
            billing_city = :city,
            billing_state = :state
            WHERE id = :id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'cpf' => $cpf !== '' ? $cpf : null,
            'birthdate' => $birthdate !== '' ? $birthdate : null,
            'phone' => $phone !== '' ? $phone : null,
            'postal_code' => $postalCode !== '' ? $postalCode : null,
            'address' => $address !== '' ? $address : null,
            'address_number' => $addressNumber !== '' ? $addressNumber : null,
            'complement' => $complement !== '' ? $complement : null,
            'province' => $province !== '' ? $province : null,
            'city' => $city !== '' ? $city : null,
            'state' => $state !== '' ? strtoupper($state) : null,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id LIMIT 1');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $id,
        ]);
    }

    public static function setEmailVerifiedAt(int $id, string $dateTime): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET email_verified_at = :dt WHERE id = :id LIMIT 1');
        $stmt->execute([
            'dt' => $dateTime,
            'id' => $id,
        ]);
    }

    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function search(string $term): array
    {
        $pdo = Database::getConnection();
        $like = '%' . $term . '%';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE name LIKE :q OR email LIKE :q ORDER BY created_at DESC');
        $stmt->execute(['q' => $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function countAdmins(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function setActive(int $id, bool $active): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET is_active = :a WHERE id = :id LIMIT 1');
        $stmt->execute([
            'a' => $active ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function setAdmin(int $id, bool $isAdmin): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET is_admin = :adm WHERE id = :id LIMIT 1');
        $stmt->execute([
            'adm' => $isAdmin ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function resetTokenBalanceForPlan(int $id, int $monthlyLimit): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET token_balance = :balance, last_token_reset_at = NOW() WHERE id = :id LIMIT 1');
        $stmt->execute([
            'balance' => $monthlyLimit,
            'id' => $id,
        ]);
    }

    public static function getTokenBalance(int $id): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT token_balance FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['token_balance'] ?? 0);
    }

    public static function debitTokens(int $id, int $amount, string $reason, array $meta = []): void
    {
        if ($amount <= 0) {
            return;
        }

        $pdo = Database::getConnection();

        // Atualiza saldo e total consumido (garantindo que não fique negativo)
        $stmt = $pdo->prepare('UPDATE users SET
            token_balance = GREATEST(token_balance - :amount, 0),
            token_spent_total = token_spent_total + :amount
            WHERE id = :id LIMIT 1');
        $stmt->execute([
            'amount' => $amount,
            'id' => $id,
        ]);

        // Log de transação (débito)
        TokenTransaction::create([
            'user_id' => $id,
            'amount' => -$amount,
            'reason' => $reason,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }

    public static function creditTokens(int $id, int $amount, string $reason, array $meta = []): void
    {
        if ($amount <= 0) {
            return;
        }

        $pdo = Database::getConnection();

        // Atualiza saldo (crédito)
        $stmt = $pdo->prepare('UPDATE users SET
            token_balance = token_balance + :amount
            WHERE id = :id LIMIT 1');
        $stmt->execute([
            'amount' => $amount,
            'id' => $id,
        ]);

        // Log de transação (crédito)
        TokenTransaction::create([
            'user_id' => $id,
            'amount' => $amount,
            'reason' => $reason,
            'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }

    public static function getOrCreateReferralCode(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT referral_code FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing = trim((string)($row['referral_code'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        // Gera um código curto e tenta garantir unicidade
        for ($i = 0; $i < 5; $i++) {
            $candidate = substr(bin2hex(random_bytes(6)), 0, 10);

            $check = $pdo->prepare('SELECT id FROM users WHERE referral_code = :code LIMIT 1');
            $check->execute(['code' => $candidate]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                $update = $pdo->prepare('UPDATE users SET referral_code = :code WHERE id = :id LIMIT 1');
                $update->execute([
                    'code' => $candidate,
                    'id' => $id,
                ]);
                return $candidate;
            }
        }

        // Fallback simples baseado no ID, em caso de colisões improváveis
        $fallback = 'U' . $id;
        $update = $pdo->prepare('UPDATE users SET referral_code = :code WHERE id = :id LIMIT 1');
        $update->execute([
            'code' => $fallback,
            'id' => $id,
        ]);

        return $fallback;
    }

    public static function findByReferralCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE referral_code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
