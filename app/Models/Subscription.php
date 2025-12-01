<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Subscription
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO subscriptions (
            plan_id, customer_name, customer_email, customer_cpf, customer_phone,
            customer_postal_code, customer_address, customer_address_number,
            customer_complement, customer_province, customer_city, customer_state,
            asaas_customer_id, asaas_subscription_id, status, started_at
        ) VALUES (
            :plan_id, :customer_name, :customer_email, :customer_cpf, :customer_phone,
            :customer_postal_code, :customer_address, :customer_address_number,
            :customer_complement, :customer_province, :customer_city, :customer_state,
            :asaas_customer_id, :asaas_subscription_id, :status, :started_at
        )');

        $stmt->execute($data);

        return (int)$pdo->lastInsertId();
    }
}
