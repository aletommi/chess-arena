<?php
$user = current_user();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameSAW Chess</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/styles.css')) ?>">
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?= e(base_url('index.php')) ?>">GameSAW</a>
            <nav class="main-nav">
                <a href="<?= e(base_url('index.php')) ?>">Home</a>
                <a href="<?= e(base_url('leaderboard.php')) ?>">Classifica</a>
                <?php if ($user): ?>
                    <a href="<?= e(base_url('game.php')) ?>">Gioca</a>
                    <a href="<?= e(base_url('profile.php')) ?>">Profilo</a>
                    <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                        <a href="<?= e(base_url('admin.php')) ?>">Admin</a>
                        <a href="<?= e(base_url('newsletter.php')) ?>">Newsletter</a>
                    <?php endif; ?>
                    <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
                <?php else: ?>
                    <a href="<?= e(base_url('login.php')) ?>">Login</a>
                    <a class="cta" href="<?= e(base_url('register.php')) ?>">Registrati</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="site-content">
        <main class="page">