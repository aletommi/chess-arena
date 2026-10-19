<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/lib/UserRepository.php';
require_once __DIR__ . '/lib/GameService.php';

$repo = new UserRepository(Database::connection());
$gameService = new GameService(Database::connection());

$user = current_user();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$total = $repo->countActiveUsers();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$leaders = $repo->listLeaderboard($perPage, $offset);

$myRank = null;
$myStats = null;
$myScore = null;
if ($user) {
    $myRank = $repo->getRankForUser((int) $user['id']);
    $myStats = $gameService->getUserStats((int) $user['id']);
    $myScore = (int) ($user['score'] ?? 0);
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <h1>Classifica</h1>

    <?php if ($user && $myRank !== null && $myStats !== null): ?>
        <div class="card leaderboard-summary">
            <h2>Il tuo posto</h2>
            <div class="leaderboard-summary-grid">
                <div class="leaderboard-summary-item">
                    <div class="label">Posizione</div>
                    <div class="value">#<?= e((string) $myRank) ?></div>
                </div>
                <div class="leaderboard-summary-item">
                    <div class="label">Punti</div>
                    <div class="value"><?= e((string) $myScore) ?></div>
                </div>
                <div class="leaderboard-summary-item">
                    <div class="label">Vinte</div>
                    <div class="value"><?= e((string) $myStats['win']) ?></div>
                </div>
                <div class="leaderboard-summary-item">
                    <div class="label">Perse</div>
                    <div class="value"><?= e((string) $myStats['loss']) ?></div>
                </div>
                <div class="leaderboard-summary-item">
                    <div class="label">Patte</div>
                    <div class="value"><?= e((string) $myStats['draw']) ?></div>
                </div>
            </div>
        </div>
    <?php elseif (!$user): ?>
        <div class="card" style="margin-bottom: 1rem;">
            Accedi per vedere la tua posizione, punti e statistiche.
        </div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Giocatore</th>
                <th>Punteggio</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$leaders): ?>
                <tr>
                    <td colspan="3">Nessun punteggio disponibile.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($leaders as $index => $leader): ?>
                    <tr>
                        <td><?= e((string) ($offset + $index + 1)) ?></td>
                        <td>
                            <div class="leaderboard-player">
                                <?php
                                $avatar = $leader['avatar_path'] ?? '';
                                $avatarUrl = $avatar ? base_url($avatar) : base_url('assets/img/avatar-placeholder.svg');
                                ?>
                                <img class="leaderboard-avatar" src="<?= e($avatarUrl) ?>" alt="">
                                <span><?= e($leader['username']) ?></span>
                            </div>
                        </td>
                        <td><?= e((string) $leader['score']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagine classifica">
            <?php if ($page > 1): ?>
                <a class="pagination-link" href="<?= e(base_url('leaderboard.php?page=' . ($page - 1))) ?>">Precedente</a>
            <?php else: ?>
                <span class="pagination-link disabled">Precedente</span>
            <?php endif; ?>

            <span class="pagination-status">Pagina <?= e((string) $page) ?> di <?= e((string) $totalPages) ?></span>

            <?php if ($page < $totalPages): ?>
                <a class="pagination-link" href="<?= e(base_url('leaderboard.php?page=' . ($page + 1))) ?>">Successiva</a>
            <?php else: ?>
                <span class="pagination-link disabled">Successiva</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>