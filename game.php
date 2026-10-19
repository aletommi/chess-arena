<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$timeControl = max(30, (int) ($_GET['tc'] ?? 600));
$increment = max(0, (int) ($_GET['inc'] ?? 0));
$modeLabel = null;
if ($timeControl === 60 && $increment === 1) {
    $modeLabel = '1 min +1';
} elseif ($timeControl === 180 && $increment === 2) {
    $modeLabel = '3 min +2';
} elseif ($timeControl === 600 && $increment === 0) {
    $modeLabel = '10 min';
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <?php
    $user = current_user();
    $userScore = (int) ($user['score'] ?? 1200);
    $stockfishElo = max(1350, min(2850, $userScore));
    ?>
    <h1><?= e($user['username']) ?> (<?= $userScore ?>) vs Stockfish (<?= $stockfishElo ?>)</h1>
    <?php if ($modeLabel): ?>
        <p style="margin-top: -0.5rem; color: var(--text-muted);">Modalità: <?= e($modeLabel) ?></p>
    <?php endif; ?>
    <div class="game-layout">
        <div id="board" class="board" aria-label="Scacchiera"></div>
        <div class="game-panel">
            <h2>Stato partita</h2>
            <div class="game-timers">
                <div class="game-timer">
                    <span class="label first-label">Tu</span>
                    <span id="clock-white" class="value">--:--</span>
                </div>
                <div class="game-timer">
                    <span class="label second-label">Stockfish</span>
                    <span id="clock-black" class="value">--:--</span>
                </div>
            </div>
            <p id="game-status">Tocca un pezzo per iniziare.</p>

            <!-- Captured Pieces -->
            <div class="captured-section">
                <h3>Pezzi catturati</h3>
            </div>
            <div class="captured-area">
                <div id="captured-black" class="captured-row"></div>
                <div id="captured-white" class="captured-row"></div>
            </div>

            <div class="section">
                <h3>Mosse</h3>
                <div id="move-log" class="move-log"></div>
            </div>
            <div class="form-actions">
                <button id="new-game" class="button primary" type="button">Nuova partita</button>

            </div>
            <div id="engine-status">
                <p id="engine-status-text" class="status-banner">Motore: in caricamento...</p>
                <p id="engine-status-note" class="section" style="color: var(--muted);">Nota: se Stockfish non è
                    disponibile, le mosse saranno scelte casualmente.</p>
            </div>
        </div>
    </div>
</section>

<!-- Side Selection Modal -->
<div id="side-selection-modal" class="modal-overlay" style="display: flex;">
    <div class="modal-content">
        <h2>Scegli il tuo colore</h2>
        <div class="modal-body">
            <p>Se scegli Nero, Stockfish inizia per primo.</p>
        </div>
        <div class="modal-actions side-selection-actions">
            <button class="button side-btn" data-color="w">
                <span class="icon">♚</span> Bianco
            </button>
            <button class="button side-btn" data-color="random">
                <span class="icon">?</span> Casuale
            </button>
            <button class="button side-btn" data-color="b">
                <span class="icon">♔</span> Nero
            </button>
        </div>
    </div>
</div>

<!-- Game Over Modal -->
<div id="game-over-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <span id="modal-close" class="modal-close">&times;</span>
        <h2 id="modal-title">HAI VINTO!</h2>
        <div class="modal-body">
            <p id="modal-winner-text"></p>
            <p id="modal-points-text"></p>
        </div>
        <div class="modal-actions">
            <button id="modal-play-again" class="button primary large">Gioca Ancora</button>
            <a href="<?= e(base_url('index.php')) ?>" class="button large">Home</a>
        </div>
    </div>
</div>

<script src="<?= e(base_url('assets/js/stockfish-adapter.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/chess-game.js')) ?>"></script>
<script>
    new GameController({
        boardId: 'board',
        statusId: 'game-status',
        logId: 'move-log',
        engineStatusId: 'engine-status-text',
        capturedWhiteId: 'captured-white',
        capturedBlackId: 'captured-black',
        clockWhiteId: 'clock-white',
        clockBlackId: 'clock-black',
        timeControlSeconds: <?= e((string) $timeControl) ?>,
        incrementSeconds: <?= e((string) $increment) ?>,
        recordUrl: '<?= e(base_url('api/record_game.php')) ?>',
        enginePath: '<?= e(base_url('assets/engine/stockfish.js')) ?>',
        homeUrl: '<?= e(base_url('index.php')) ?>',
        userElo: <?= e((string) ($user['score'] ?? 1200)) ?>
    });
</script>