<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../lib/UserRepository.php';
require_once __DIR__ . '/../lib/GameService.php';
require_once __DIR__ . '/../lib/BadgeService.php';

header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Non autenticato.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload non valido.']);
    exit;
}

$result = $payload['result'] ?? '';
$moves = (string) ($payload['moves'] ?? '');

if (!in_array($result, ['win', 'loss', 'draw'], true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Risultato non valido.']);
    exit;
}

$userRating = (int) ($user['score'] ?? 1200); // Default to 1200 if not set
// Stessa forza usata da Stockfish in partita (vedi game.php e stockfish-adapter.js)
$engineElo = max(1350, min(2850, $userRating));
$kFactor = 32;

// Calculate Expected Score for User
// Formula: E_a = 1 / (1 + 10 ^ ((R_b - R_a) / 400))
$exponent = ($engineElo - $userRating) / 400;
$expectedScore = 1 / (1 + pow(10, $exponent));

// Determine Actual Score
$actualScore = 0;
if ($result === 'win') {
    $actualScore = 1;
} elseif ($result === 'draw') {
    $actualScore = 0.5;
} else {
    $actualScore = 0;
}

// Calculate Rating Change
// Formula: R_a' = R_a + K * (S_a - E_a)
$newRating = $userRating + $kFactor * ($actualScore - $expectedScore);
$scoreDelta = (int) round($newRating - $userRating);

$pdo = Database::connection();

$gameService = new GameService($pdo);
$repo = new UserRepository($pdo);
$badgeService = new BadgeService($pdo);

$gameService->record((int) $user['id'], $result, $moves, $scoreDelta);
$newScore = $repo->updateScore((int) $user['id'], $scoreDelta);

$newBadges = [];
if ($result === 'win') {
    if ($badgeService->awardBadge((int) $user['id'], 'Winner')) {
        $badge = $badgeService->get('Winner');
        if ($badge)
            $newBadges[] = $badge;
    }
} elseif ($result === 'loss') {
    if ($badgeService->awardBadge((int) $user['id'], 'Loser')) {
        $badge = $badgeService->get('Loser');
        if ($badge)
            $newBadges[] = $badge;
    }
}

$message = 'Partita salvata. Nuovo punteggio: ' . $newScore;
if ($newBadges) {
    $names = array_map(function ($b) {
        return $b['name']; }, $newBadges);
    $message .= '. Nuovi badge: ' . implode(', ', $names);
}

echo json_encode([
    'message' => $message,
    'score' => $newScore,
    'delta' => $scoreDelta,
    'badges' => $newBadges
]);
