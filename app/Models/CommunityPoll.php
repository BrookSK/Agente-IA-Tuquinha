<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class CommunityPoll
{
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO community_polls
            (community_id, user_id, question, option1, option2, option3, option4, option5, allow_multiple)
            VALUES (:community_id, :user_id, :question, :option1, :option2, :option3, :option4, :option5, :allow_multiple)');
        $stmt->execute([
            'community_id' => (int)($data['community_id'] ?? 0),
            'user_id' => (int)($data['user_id'] ?? 0),
            'question' => (string)($data['question'] ?? ''),
            'option1' => (string)($data['option1'] ?? ''),
            'option2' => (string)($data['option2'] ?? ''),
            'option3' => $data['option3'] ?? null,
            'option4' => $data['option4'] ?? null,
            'option5' => $data['option5'] ?? null,
            'allow_multiple' => !empty($data['allow_multiple']) ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_polls WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allWithStatsForCommunity(int $communityId, int $userId): array
    {
        if ($communityId <= 0) {
            return [];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM community_polls WHERE community_id = :cid ORDER BY created_at DESC');
        $stmt->execute(['cid' => $communityId]);
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$polls) {
            return [];
        }

        $byId = [];
        $ids = [];
        foreach ($polls as $p) {
            $id = (int)$p['id'];
            $byId[$id] = $p;
            $ids[] = $id;
        }

        if (!$ids) {
            return [];
        }

        // Monta placeholders nomeados para usar no IN
        $placeholders = [];
        $params = [];
        foreach ($ids as $idx => $id) {
            $key = ':id' . $idx;
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $inClause = implode(',', $placeholders);

        // Contagem de votos por opção
        $stmt = $pdo->prepare("SELECT poll_id, option_number, COUNT(*) AS c
            FROM community_poll_votes
            WHERE poll_id IN ($inClause)
            GROUP BY poll_id, option_number");
        $stmt->execute($params);
        $counts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pid = (int)$row['poll_id'];
            $opt = (int)$row['option_number'];
            $c = (int)$row['c'];
            if (!isset($counts[$pid])) {
                $counts[$pid] = [];
            }
            $counts[$pid][$opt] = $c;
        }

        // Voto do usuário em cada enquete
        $userVotes = [];
        $stmt = $pdo->prepare("SELECT poll_id, option_number FROM community_poll_votes
            WHERE poll_id IN ($inClause) AND user_id = :uid");
        $paramsWithUser = $params;
        $paramsWithUser['uid'] = $userId;
        $stmt->execute($paramsWithUser);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userVotes[(int)$row['poll_id']] = (int)$row['option_number'];
        }

        $result = [];
        foreach ($byId as $id => $p) {
            $opts = [];
            for ($i = 1; $i <= 5; $i++) {
                $label = trim((string)($p['option' . $i] ?? ''));
                if ($label === '') {
                    continue;
                }
                $opts[$i] = $label;
            }

            $pollCounts = $counts[$id] ?? [];
            $total = 0;
            foreach ($pollCounts as $c) {
                $total += (int)$c;
            }

            $votes = [];
            foreach ($opts as $num => $label) {
                $c = (int)($pollCounts[$num] ?? 0);
                $pct = $total > 0 ? round(($c / $total) * 100) : 0;
                $votes[$num] = [
                    'label' => $label,
                    'count' => $c,
                    'percentage' => $pct,
                ];
            }

            $result[] = [
                'poll' => $p,
                'options' => $opts,
                'votes' => $votes,
                'total_votes' => $total,
                'user_vote' => $userVotes[$id] ?? null,
            ];
        }

        return $result;
    }
}
