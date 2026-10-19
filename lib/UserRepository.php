<?php

declare(strict_types=1);

final class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Crea un nuovo utente con password crittografata
    public function create(string $email, string $username, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, username, password_hash) VALUES (:email, :username, :hash)'
        );
        $stmt->execute([
            'email' => $email,
            'username' => $username,
            'hash' => $hash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // Cerca un utente tramite indirizzo email
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Cerca un utente tramite nome utente
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Cerca un utente tramite il suo ID univoco
    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Aggiorna i dati del profilo utente (avatar, newsletter, ecc.)
    public function updateProfile(int $userId, array $data): void
    {
        $set = 'UPDATE users SET avatar_path = :avatar, newsletter_opt_in = :newsletter';
        $params = [
            'avatar' => $data['avatar_path'],
            'newsletter' => $data['newsletter_opt_in'],
            'user_id' => $userId,
        ];

        if (isset($data['username'])) {
            $set .= ', username = :username';
            $params['username'] = $data['username'];
        }

        $set .= ' WHERE id = :user_id';

        $stmt = $this->pdo->prepare($set);
        $stmt->execute($params);
    }

    // Aggiorna la password dell'utente hashando quella nuova
    public function updatePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $userId]);
    }

    // Aggiorna il punteggio ELO dell'utente incrementandolo del delta
    public function updateScore(int $userId, int $delta): int
    {
        $stmt = $this->pdo->prepare('UPDATE users SET score = score + :delta WHERE id = :id');
        $stmt->execute(['delta' => $delta, 'id' => $userId]);

        $stmt = $this->pdo->prepare('SELECT score FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    // Restituisce la lista dei migliori utenti per punteggio
    public function listTop(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, score FROM users WHERE active = 1 ORDER BY score DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Restituisce una porzione paginata della classifica utenti
    public function listLeaderboard(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username, u.score, u.avatar_path
             FROM users u
             WHERE u.active = 1
             ORDER BY u.score DESC, u.id ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Conta il numero totale di utenti attivi
    public function countActiveUsers(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users WHERE active = 1');
        return (int) $stmt->fetchColumn();
    }

    // Calcola il rango (posizione in classifica) di un utente specifico
    public function getRankForUser(int $userId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id, score FROM users WHERE id = :id AND active = 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 + COUNT(*)
             FROM users
             WHERE active = 1
               AND (score > :score OR (score = :score AND id < :id))'
        );
        $stmt->execute([
            'score' => (int) $row['score'],
            'id' => (int) $row['id'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    // Elenca tutti gli utenti (anche non attivi) per l'amministrazione
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, email, username, role, active, score FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    // Attiva o disattiva un account utente
    public function setActive(int $userId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $userId]);
    }

    // Modifica il ruolo di un utente (es. admin, user)
    public function setRole(int $userId, string $role): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $userId]);
    }

    // Restituisce la lista degli utenti iscritti alla newsletter
    public function listNewsletterSubscribers(): array
    {
        $stmt = $this->pdo->query('SELECT email, username FROM users WHERE active = 1 AND newsletter_opt_in = 1');
        return $stmt->fetchAll();
    }
}
