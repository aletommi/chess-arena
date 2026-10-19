<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/lib/UserRepository.php';

require_once __DIR__ . '/lib/BadgeService.php';

require_login();

$repo = new UserRepository(Database::connection());

$badgeService = new BadgeService(Database::connection());

$user = current_user();
$current = $repo->findById((int) $user['id']);
$badges = $badgeService->getUserBadges((int) $user['id']);

require_once __DIR__ . '/includes/header.php';
?>
<section class="container profile-page">
    <div class="card profile-card">
        <h1>Il tuo profilo</h1>
        <div class="profile-header">
            <?php if (!empty($current['avatar_path'])): ?>
                <img class="profile-avatar" src="<?= e($current['avatar_path']) ?>" alt="Avatar utente">
            <?php else: ?>
                <img class="profile-avatar" src="<?= e(base_url('assets/img/avatar-placeholder.svg')) ?>"
                    alt="Avatar placeholder">
            <?php endif; ?>
            <div class="profile-details">
                <p><strong>Username:</strong> <?= e($current['username'] ?? '-') ?></p>
                <p><strong>Email:</strong> <?= e($current['email']) ?></p>
                <p><strong>Score:</strong> <?= e((string) $current['score']) ?></p>
                <p><strong>Newsletter:</strong> <?= $current['newsletter_opt_in'] ? 'Iscritto' : 'Non iscritto' ?></p>
            </div>
            <a class="button primary" href="<?= e(base_url('profile_edit.php')) ?>">Modifica profilo</a>

        </div>
    </div>

    <div class="card badges-card">
        <h2>Badge</h2>
        <?php if (!$badges): ?>
            <p>Nessun badge ancora. Vinci partite per sbloccarli.</p>
        <?php else: ?>
            <div class="badges">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge">
                        <?php if (!empty($badge['icon'])): ?>
                            <img src="<?= e(base_url($badge['icon'])) ?>" alt="Badge <?= e($badge['name']) ?>">
                        <?php endif; ?>
                        <div>
                            <strong><?= e($badge['name']) ?></strong>
                            <div><?= e($badge['description']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>