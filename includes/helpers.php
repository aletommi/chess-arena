<?php

declare(strict_types=1);

// Esegue l'escape di una stringa per prevenire XSS
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Esegue un reindirizzamento HTTP a un percorso specificato
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// Restituisce true se la richiesta corrente è di tipo POST
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Restituisce l'URL base completo dell'applicazione concatenato al percorso
function base_url(string $path = ''): string
{
    $prefix = rtrim(BASE_PATH, '/');
    return $prefix . '/' . ltrim($path, '/');
}
