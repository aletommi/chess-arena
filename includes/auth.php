<?php

declare(strict_types=1);

// Recupera l'utente correntemente loggato dalla sessione, se esiste e attivo
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['active'] !== 1) {
        return null;
    }

    return $user;
}

// Reindirizza alla pagina di login se l'utente non è autenticato
function require_login(): void
{
    if (!current_user()) {
        redirect(base_url('login.php'));
    }
}

// Reindirizza alla home se l'utente non ha ruolo admin
function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        redirect(base_url('index.php'));
    }
}

// Imposta l'ID utente in sessione per effettuare il login
function login_user(int $userId): void
{
    $_SESSION['user_id'] = $userId;
}

// Distrugge la sessione e disconnette l'utente
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
