<?php

declare(strict_types=1);

// Genera o recupera il token CSRF dalla sessione
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_token'];
}

// Restituisce un campo input hidden HTML con il token CSRF
function csrf_input(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

// Verifica se il token CSRF inviato via POST corrisponde a quello in sessione
function csrf_verify(): bool
{
    if (!is_post()) {
        return true;
    }

    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
