<?php

declare(strict_types=1);

final class BadgeService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listAll(): array
    {
        // Elenca tutti i badge disponibili
        $stmt = $this->pdo->query('SELECT * FROM badges ');
        return $stmt->fetchAll();
    }

    // Restituisce la lista dei badge ottenuti da un utente specifico
    public function getUserBadges(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.* FROM badges b INNER JOIN user_badges ub ON ub.badge_id = b.id WHERE ub.user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // Recupera i dettagli di un singolo badge tramite il suo codice
    public function get(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM badges WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $badge = $stmt->fetch();
        return $badge ?: null;
    }

    // Assegna un badge a un utente se non lo possiede già
    public function awardBadge(int $userId, string $badgeCode): bool
    {
        // Check if badge exists
        $stmt = $this->pdo->prepare('SELECT id FROM badges WHERE code = :code');
        $stmt->execute(['code' => $badgeCode]);
        $badgeId = $stmt->fetchColumn();

        if (!$badgeId) {
            return false;
        }

        // Check if already awarded
        $check = $this->pdo->prepare('SELECT 1 FROM user_badges WHERE user_id = :u AND badge_id = :b');
        $check->execute(['u' => $userId, 'b' => $badgeId]);
        if ($check->fetchColumn()) {
            return false;
        }

        // Award
        $insert = $this->pdo->prepare('INSERT INTO user_badges (user_id, badge_id) VALUES (:u, :b)');
        $insert->execute(['u' => $userId, 'b' => $badgeId]);
        return true;
    }
}
