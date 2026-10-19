<?php

declare(strict_types=1);

final class GameService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Registra una nuova partita completata nel database
    public function record(int $userId, string $result, string $moves, int $scoreDelta): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO games (user_id, result, moves, score_delta) VALUES (:user_id, :result, :moves, :score_delta)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'result' => $result,
            'moves' => $moves,
            'score_delta' => $scoreDelta,
        ]);
    }

    // Calcola le statistiche di vittoria/pareggio/sconfitta per un utente
    public function getUserStats(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT result, COUNT(*) AS c
             FROM games
             WHERE user_id = :user_id
             GROUP BY result'
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $stats = [
            'win' => 0,
            'loss' => 0,
            'draw' => 0,
            'total' => 0,
        ];
        foreach ($rows as $row) {
            $result = (string) ($row['result'] ?? '');
            $count = (int) ($row['c'] ?? 0);
            if (isset($stats[$result])) {
                $stats[$result] = $count;
                $stats['total'] += $count;
            }
        }
        return $stats;
    }
}
