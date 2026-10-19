<?php

declare(strict_types=1);

// Legge una variabile d'ambiente, con un valore di default per lo sviluppo locale
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Database Credentials
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'SAW'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Application Paths
define('BASE_PATH', env('BASE_PATH', ''));

// Email Configuration (PHPMailer)
define('SMTP_HOST', env('SMTP_HOST', 'sandbox.smtp.mailtrap.io'));
define('SMTP_USER', env('SMTP_USER'));
define('SMTP_PASS', env('SMTP_PASS'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'newsletter@gamesaw.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'GameSaw Newsletter'));
